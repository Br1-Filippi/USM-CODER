<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Question;

class CodeController extends Controller
{

    private function submitToJudge0(array $payload, bool $wait = false)
    {
        return Http::timeout(30)
            ->withHeaders([
                'X-Auth-Token' => env('JUDGE_API_KEY'),
                'Content-Type' => 'application/json',
            ])
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
                ->withHeaders([
                    'X-Auth-Token' => env('JUDGE_API_KEY'),
                ])
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
            'stdin' => 'required|string',
            'expected_output' => 'required|string',
        ]);

        try {
            $response = $this->submitToJudge0([
                'source_code' => $request->source_code,
                'language_id' => $request->language_id,
                'stdin' => $request->stdin,
            ], true);

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

        foreach ($tests as $i => $test) {
            try {
                $response = $this->submitToJudge0([
                    'source_code' => $request->source_code,
                    'language_id' => $request->language_id,
                    'stdin' => $test->stdin,
                ], true);

                if (!$response->successful()) {
                    throw new \Exception('Error en Judge0');
                }

                $data = $response->json();
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

        return $data['summary']['percentage'] ?? 0;
    }
}