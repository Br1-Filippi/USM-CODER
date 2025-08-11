<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;


Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::post('/run-code', function (\Illuminate\Http\Request $request) {
    $response = Http::withHeaders([
        'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
        'X-RapidAPI-Host' => 'judge0-ce.p.rapidapi.com',
        'Content-Type' => 'application/json'
    ])->post(env('JUDGE_API_URL') . '/submissions?base64_encoded=false&wait=false', [
        'source_code' => $request->source_code,
        'language_id' => $request->language_id,
        'stdin' => $request->stdin ?? ''
    ]);

    if (!$response->successful()) {
        return response()->json([
            'error' => 'Error al enviar código',
            'details' => $response->body()
        ], 500);
    }

    return response()->json(['token' => $response['token'] ?? null]);
});

Route::get('/code-result/{token}', function ($token) {
    $response = Http::withHeaders([
        'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
        'X-RapidAPI-Host' => 'judge0-ce.p.rapidapi.com',
        'Content-Type' => 'application/json'
    ])->get(env('JUDGE_API_URL') . "/submissions/{$token}?base64_encoded=false");

    return response()->json($response->json());
});
