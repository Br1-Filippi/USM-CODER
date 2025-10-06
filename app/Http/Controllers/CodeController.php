<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


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
            'stdout' => $data['stdout'],
            'stderr' => $data['stderr'],
            'compile_output' => $data['compile_output'],
        ]);
    }
    
}
