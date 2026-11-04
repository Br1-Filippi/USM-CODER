<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Question;

class CodeController extends Controller
{

    // El Judge0 propio lee X-Auth-Token y RapidAPI lee X-RapidAPI-*; cada uno ignora las del otro.
    private function judge0Headers(): array
    {
        return [
            'X-Auth-Token' => env('JUDGE_API_KEY'),
            'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
            'X-RapidAPI-Host' => env('JUDGE_API_HOST'),
        ];
    }

    private function submitToJudge0(array $payload, bool $wait = false)
    {
        return Http::timeout(30)
            ->withHeaders($this->judge0Headers() + ['Content-Type' => 'application/json'])
            ->post(
                env('JUDGE_API_URL')
                . '/submissions?base64_encoded=false'
                . ($wait ? '&wait=true' : ''),
                $payload
            );
    }

    private function pollSubmission(string $token, int $maxAttempts = 15)
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(1); 

            $response = Http::timeout(15)
                ->withHeaders($this->judge0Headers())
                ->get(
                    env('JUDGE_API_URL')
                    . "/submissions/{$token}?base64_encoded=false"
                );

            if (!$response->successful()) {
                throw new \Exception('Error al obtener resultado desde Judge0');
            }

            $data = $response->json();
            $statusId = $data['status']['id'] ?? null;

            if (!in_array($statusId, [1, 2])) {
                return $data;
            }
        }

        throw new \Exception('Timeout: el código tardó demasiado en ejecutarse');
    }

    public function runCode(Request $request)
    {
        $request->validate([
            'source_code' => 'required|string',
            'language_id' => 'required|integer',
            'stdin' => 'nullable|string',
        ]);

        try {
            $response = $this->submitToJudge0([
                'source_code' => $request->source_code,
                'language_id' => $request->language_id,
                'stdin' => $request->stdin ?? '',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Error al enviar código a Judge0. Status: ' . $response->status());
            }

            $token = $response->json()['token'];
            $result = $this->pollSubmission($token);

            return response()->json([
                'stdout' => $result['stdout'] ?? null,
                'stderr' => $result['stderr'] ?? null,
                'compile_output' => $result['compile_output'] ?? null,
                'status' => $result['status'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function runSingleTest(Request $request)
    {
        $request->validate([
            'source_code' => 'required|string',
            'language_id' => 'required|integer',
            'question_id' => 'required|integer',
            'stdin' => 'required|string',
            'expected_output' => 'required|string',
        ]);

        $question = Question::findOrFail($request->question_id);

        try {
            $response = $this->submitToJudge0(
                $this->gradingPayload($question, $request->source_code, $request->language_id) + ['stdin' => $request->stdin],
                true
            );

            if (!$response->successful()) {
                throw new \Exception('Error al ejecutar el test');
            }

            $data = $response->json();
            $output = trim($data['stdout'] ?? '');
            $expected = trim($request->expected_output);

            return response()->json([
                'passed' => $output === $expected,
                'output' => $output,
                'expected' => $expected,
                'stderr' => $data['stderr'] ?? null,
                'compile_output' => $data['compile_output'] ?? null,
                'status' => $data['status'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* =========================================================
     |  EJECUTAR TODOS LOS TESTS
     | ========================================================= */

    public function runAllTests(Request $request)
    {
        $request->validate([
            'source_code' => 'required|string',
            'language_id' => 'required|integer',
            'question_id' => 'required|integer',
        ]);

        $question = Question::findOrFail($request->question_id);
        $tests = $question->unitests;

        if ($tests->isEmpty()) {
            return response()->json([
                'error' => 'No hay tests para esta pregunta'
            ], 400);
        }

        $results = [];
        $passed = 0;

        $payload = $this->gradingPayload($question, $request->source_code, $request->language_id);

        foreach ($tests as $i => $test) {
            try {
                $response = $this->submitToJudge0($payload + ['stdin' => $test->stdin], true);

                if (!$response->successful()) {
                    throw new \Exception('Error en Judge0');
                }

                $data = $response->json();
                // 1-2: no terminó; 13: falla interna de Judge0. No es culpa del alumno.
                if (in_array($data['status']['id'] ?? null, [null, 1, 2, 13], true)) {
                    throw new \Exception('Judge0 no pudo evaluar: ' . ($data['status']['description'] ?? 'sin estado'));
                }

                $output = trim($data['stdout'] ?? '');
                $expected = trim($test->expected_output);

                $ok = $output === $expected;
                if ($ok) $passed++;

                $results[] = [
                    'test' => $i + 1,
                    'stdin' => $test->stdin,
                    'expected' => $expected,
                    'output' => $output,
                    'passed' => $ok,
                    'status' => $data['status']['description'] ?? null,
                    'stderr' => $data['stderr'] ?? null,
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'test' => $i + 1,
                    'stdin' => $test->stdin,
                    'expected' => $test->expected_output,
                    'passed' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'summary' => [
                'total' => count($tests),
                'passed' => $passed,
                'failed' => count($tests) - $passed,
                'percentage' => count($tests) > 0 ? round(($passed / count($tests)) * 100, 2) : 0,
            ],
            'results' => $results,
        ]);
    }

    public function getScore(string $sourceCode, int $languageId, int $questionId): float
    {
        $request = new Request([
            'source_code' => $sourceCode,
            'language_id' => $languageId,
            'question_id' => $questionId,
        ]);

        $response = $this->runAllTests($request);
        $data = $response->getData(true);

        // Sin evaluación completa no hay nota: el alumno reintenta en vez de quedar con un puntaje falso.
        if ($response->status() !== 200 || collect($data['results'])->contains(fn ($r) => isset($r['error']))) {
            throw new \Exception($data['error'] ?? 'Judge0 no pudo evaluar el código, envíalo de nuevo');
        }

        return $data['summary']['percentage'];
    }

    // La corrección (nota y botones del profesor) corre el código tal como lo vería la pregunta.
    private function gradingPayload(Question $question, string $sourceCode, int $languageId): array
    {
        $payload = ['source_code' => $sourceCode, 'language_id' => $languageId];
        if ($question->files) {
            // Judge0 los descomprime al lado del script, igual que en la consola Pyodide.
            $payload['additional_files'] = base64_encode($this->zip($question->files));
        }
        if ($question->execution_mode === 'interactive') {
            // En la consola el prompt de input() se ve aparte; en Judge0 caería en stdout y rompería la comparación.
            $payload['source_code'] = "import builtins; builtins.input = lambda p='', _i=builtins.input: _i()\n" . $payload['source_code'];
        }

        return $payload;
    }

    // Zip sin compresión armado a mano: la imagen de PHP no trae ext-zip y el unzip de Judge0 lo lee igual.
    private function zip(array $files): string
    {
        $body = $index = '';
        foreach ($files as $name => $content) {
            // versión 1.0, flag 0x0800 = nombre en UTF-8, método 0 = sin comprimir, fecha 2020-01-01
            $meta = pack('vvvvvVVVv', 10, 0x0800, 0, 0, 0x5021, crc32($content), strlen($content), strlen($content), strlen($name));
            // Hecho en Unix (0x0314) con permisos 0644: isolate corre unzip sin locale y con origen DOS rompe "años.csv".
            $index .= pack('Vv', 0x02014b50, 0x0314) . $meta . pack('vvvvVV', 0, 0, 0, 0, 0x81A40000, strlen($body)) . $name;
            $body .= pack('V', 0x04034b50) . $meta . pack('v', 0) . $name . $content;
        }

        return $body . $index . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($index), strlen($body), 0);
    }
}