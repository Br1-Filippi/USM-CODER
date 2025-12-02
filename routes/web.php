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
Route::get('/', [HomeController::class, 'index'])->name('index');
Route::get('/loginForm', [UserController::class, 'loginForm'])->name('loginForm');
Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');
Route::get('/registerForm', [UserController::class, 'registerForm'])->name('registerForm');



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

//Submission Routes
Route::post('/question/{question_id}/submit', [SubmissionController::class, 'submitCode'])->name('submit-code');
Route::get('/question/{question_id}/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
Route::get('/question/{question_id}/submissions/{submission_id}', [SubmissionController::class, 'show'])->name('submissions.show');


