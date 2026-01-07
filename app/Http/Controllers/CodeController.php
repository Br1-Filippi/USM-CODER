<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Question;

class CodeController extends Controller
{
    public function runCode(Request $request)
    {
        try {
            // Paso 1: Crear submission SIN wait
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
                    'X-RapidAPI-Host' => env('JUDGE_API_HOST'),
                    'Content-Type' => 'application/json',
                ])->post(env('JUDGE_API_URL') . '/submissions?base64_encoded=false', [
                    'source_code' => $request->input('source_code'),
                    'language_id' => $request->input('language_id'),
                    'stdin' => $request->input('stdin', ''),
                ]);

            if (!$response->successful()) {
                \Log::error('Judge0 submission error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'error' => 'Error al enviar código',
                    'details' => $response->body(),
                ], 500);
            }

            $submissionData = $response->json();
            $token = $submissionData['token'];

            \Log::info('Submission created', ['token' => $token]);

            // Paso 2: Poll para obtener resultado
            $maxAttempts = 15;
            $attempt = 0;
            
            while ($attempt < $maxAttempts) {
                sleep(2); // Espera 2 segundos entre intentos
                
                $resultResponse = Http::timeout(15)
                    ->withHeaders([
                        'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
                        'X-RapidAPI-Host' => env('JUDGE_API_HOST'),
                    ])->get(env('JUDGE_API_URL') . '/submissions/' . $token . '?base64_encoded=false');

                if (!$resultResponse->successful()) {
                    \Log::error('Judge0 result error', [
                        'status' => $resultResponse->status(),
                        'body' => $resultResponse->body()
                    ]);
                    
                    return response()->json([
                        'error' => 'Error al obtener resultado',
                        'details' => $resultResponse->body(),
                    ], 500);
                }

                $data = $resultResponse->json();
                $statusId = $data['status']['id'];

                \Log::info('Polling attempt', [
                    'attempt' => $attempt + 1,
                    'status_id' => $statusId,
                    'status_desc' => $data['status']['description'] ?? 'unknown'
                ]);

                // Status IDs: 1=In Queue, 2=Processing
                // Cualquier otro status significa que terminó
                if (!in_array($statusId, [1, 2])) {
                    return response()->json([
                        'stdout' => $data['stdout'] ?? null,
                        'stderr' => $data['stderr'] ?? null,
                        'compile_output' => $data['compile_output'] ?? null,
                        'status' => $data['status'] ?? null,
                    ]);
                }

                $attempt++;
            }

            return response()->json([
                'error' => 'Timeout: El código tardó demasiado en ejecutarse',
                'message' => 'El código excedió el tiempo máximo de ejecución (30 segundos)'
            ], 408);

        } catch (\Exception $e) {
            \Log::error('Exception in runCode', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error inesperado',
                'message' => $e->getMessage()
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
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
                'X-RapidAPI-Host' => env('JUDGE_API_HOST'),
                'Content-Type' => 'application/json',
            ])->post(env('JUDGE_API_URL') . '/submissions?base64_encoded=false&wait=true', [
                'source_code' => $request->input('source_code'),
                'language_id' => $request->input('language_id'),
                'stdin' => $request->input('stdin'),
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al ejecutar código',
                    'details' => $response->body(),
                ], 500);
            }

            $data = $response->json();
            $output = trim($data['stdout'] ?? '');
            $expected = trim($request->input('expected_output'));
            
            return response()->json([
                'success' => true,
                'passed' => $output === $expected,
                'output' => $output,
                'expected' => $expected,
                'stderr' => $data['stderr'] ?? null,
                'compile_output' => $data['compile_output'] ?? null,
                'status' => $data['status'] ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al ejecutar test',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function runAllTests(Request $request)
    {
        $request->validate([
            'source_code' => 'required|string',
            'language_id' => 'required|integer',
            'question_id' => 'required|integer'
        ]);

        $question = Question::findOrFail($request->question_id);
        $unitests = $question->unitests;

        if ($unitests->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay tests para esta pregunta'
            ]);
        }

        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($unitests as $index => $unitest) {
            try {
                $response = Http::withHeaders([
                    'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
                    'X-RapidAPI-Host' => env('JUDGE_API_HOST'),
                    'Content-Type' => 'application/json',
                ])->post(env('JUDGE_API_URL') . '/submissions?base64_encoded=false&wait=true', [
                    'source_code' => $request->input('source_code'),
                    'language_id' => $request->input('language_id'),
                    'stdin' => $unitest->stdin,
                ]);

                if (!$response->successful()) {
                    $failed++;
                    $results[] = [
                        'test_number' => $index + 1,
                        'stdin' => $unitest->stdin,
                        'expected' => $unitest->expected_output,
                        'output' => null,
                        'passed' => false,
                        'error' => 'Error en la API: ' . $response->body()
                    ];
                    continue;
                }

                $data = $response->json();
                $output = trim($data['stdout'] ?? '');
                $expected = trim($unitest->expected_output);
                
                $isCorrect = $output === $expected;
                
                if ($isCorrect) {
                    $passed++;
                } else {
                    $failed++;
                }

                $results[] = [
                    'test_number' => $index + 1,
                    'stdin' => $unitest->stdin,
                    'expected' => $expected,
                    'output' => $output,
                    'passed' => $isCorrect,
                    'stderr' => $data['stderr'] ?? null,
                    'compile_output' => $data['compile_output'] ?? null,
                    'status' => $data['status']['description'] ?? 'Unknown'
                ];

            } catch (\Exception $e) {
                $failed++;
                $results[] = [
                    'test_number' => $index + 1,
                    'stdin' => $unitest->stdin,
                    'expected' => $unitest->expected_output,
                    'output' => null,
                    'passed' => false,
                    'error' => 'Error al ejecutar test: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => count($unitests),
                'passed' => $passed,
                'failed' => $failed,
                'percentage' => round(($passed / count($unitests)) * 100, 2)
            ],
            'results' => $results
        ]);
    }

    public function getScore($sourceCode, $languageId, $questionId)
    {
        $request = new Request([
            'source_code' => $sourceCode,
            'language_id' => $languageId,
            'question_id' => $questionId
        ]);

        $response = $this->runAllTests($request);
        $data = $response->getData(true);

        if ($data['success'] ?? false) {
            return $data['summary']['percentage'];
        }

        return 0;
    }
}