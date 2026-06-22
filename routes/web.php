<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CodeController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SebController;




//Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/loginForm', [UserController::class, 'loginForm'])->name('loginForm');
Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');
Route::get('/registerForm', [UserController::class, 'registerForm'])->name('registerForm');

//Auth routes
Route::middleware(['check.auth'])->group(function () {

    Route::get('/landing', [HomeController::class, 'landing'])->name('landing');

    //Test
    Route::resource('tests', TestController::class);

    //Safe exam Browser 
    Route::get('/tests/{test}/seb-config', [SebController::class, 'show'])->name('seb.config');
    Route::post('/tests/{test}/seb/download', [SebController::class, 'download'])->name('seb.download');


    //Question Routes
    Route::get('/test/{test}/questions/create', [QuestionController::class, 'create'])->name('questions.create');   
    Route::post('/test/{test_id}/questions/store', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/test/{test_id}/question/{question_id}', [QuestionController::class, 'show'])->name('questions.show');


    //code excetution routes
    Route::post('/run-code', [CodeController::class, 'runCode'])->name('run-code');
    Route::post('/run-single-test', [CodeController::class, 'runSingleTest'])->name('run-single-test');
    Route::post('/run-all-tests', [CodeController::class, 'runAllTests'])->name('run-all-tests');

    //Submission Routes
    Route::post('/question/{question_id}/submit', [SubmissionController::class, 'submitCode'])->name('submit-code');
    Route::get('/question/{question_id}/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/question/{question_id}/submissions/{submission_id}', [SubmissionController::class, 'show'])->name('submissions.show');

});


// Phase 0 spike: in-browser interactive Python runner (Pyodide worker).
// Throwaway page to de-risk blocking input() + SharedArrayBuffer. Not wired
// into the real playground yet. The worker is served via a route (not from
// public/) so the cross-origin-isolation middleware can stamp it with a COEP
// header — a cross-origin-isolated page may only spawn a worker whose script
// response also carries COEP.
Route::middleware('cross-origin-isolation')->group(function () {
    Route::get('/spike/python', function () {
        return view('spike.python');
    })->name('spike.python');

    Route::get('/spike/python-worker.js', function () {
        return response(file_get_contents(resource_path('spike/python-worker.js')))
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    });
});


Route::get('/test-judge0', function() {
    try {
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => env('JUDGE_API_KEY'),
            'X-RapidAPI-Host' => env('JUDGE_API_HOST'),
        ])->get(env('JUDGE_API_URL') . '/languages');

        return response()->json([
            'success' => true,
            'status' => $response->status(),
            'can_connect' => $response->successful(),
            'languages_count' => count($response->json() ?? [])
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});


