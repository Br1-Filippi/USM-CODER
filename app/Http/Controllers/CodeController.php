<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Question;

class CodeController extends Controller
{
    public function runCode(Request $request)
    {
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
            'X-RapidAPI-Host' => env('JUDGE_API_HOST'),
            'Content-Type' => 'application/json',
        ])->post(env('JUDGE_API_URL') . '/submissions?base64_encoded=false&wait=true', [
            'source_code' => $request->input('source_code'),
            'language_id' => $request->input('language_id'),
            'stdin' => $request->input('stdin', ''),
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Error al ejecutar código',
                'details' => $response->body(),
            ], 500);
        }

        $data = $response->json();

        return response()->json([
            'stdout' => $data['stdout'] ?? null,
            'stderr' => $data['stderr'] ?? null,
            'compile_output' => $data['compile_output'] ?? null,
            'status' => $data['status'] ?? null,
        ]);
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