<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;

//Public Routes

Route::get('/', [HomeController::class, 'index'])->name('index');

Route::get('/loginForm', [UserController::class, 'loginForm'])->name('loginForm');
Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');
Route::get('/registerForm', [UserController::class, 'registerForm'])->name('registerForm');











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
