<?php

use App\Http\Controllers\AnniversaryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/anniversaries', [AnniversaryController::class, 'index'])->name('anniversaries.index');
    Route::get('/anniversaries/create', [AnniversaryController::class, 'create'])->name('anniversaries.create');
    Route::post('/anniversaries', [AnniversaryController::class, 'store'])->name('anniversaries.store');
    Route::get('/anniversaries/{anniversary}/edit', [AnniversaryController::class, 'edit'])->name('anniversaries.edit');
    Route::put('/anniversaries/{anniversary}', [AnniversaryController::class, 'update'])->name('anniversaries.update');
    Route::delete('/anniversaries/{anniversary}', [AnniversaryController::class, 'destroy'])->name('anniversaries.destroy');

    Route::get('/diary', [DiaryController::class, 'index'])->name('diary.index');
    Route::post('/diary', [DiaryController::class, 'store'])->name('diary.store');

    Route::post('/miss-you', [HomeController::class, 'missYou'])->name('miss-you');

    Route::get('/questions', [App\Http\Controllers\QuestionController::class, 'index'])->name('questions.index');
    Route::post('/questions', [App\Http\Controllers\QuestionController::class, 'store'])->name('questions.store');
    Route::post('/questions/{question}/answer', [App\Http\Controllers\QuestionController::class, 'answer'])->name('questions.answer');
    Route::delete('/questions/{question}', [App\Http\Controllers\QuestionController::class, 'destroy'])->name('questions.destroy');

    Route::get('/about', [ProfileController::class, 'show'])->name('about');
    Route::post('/about', [ProfileController::class, 'update'])->name('about.update');
    Route::post('/photos', [ProfileController::class, 'uploadPhoto'])->name('photos.store');
    Route::get('/photos/{photo}/file', [ProfileController::class, 'servePhoto'])->name('photos.file');
    Route::delete('/photos/{photo}', [ProfileController::class, 'deletePhoto'])->name('photos.destroy');

    Route::post('/toggle-name-lang', function () {
        $current = session('name_lang', 'en');
        session(['name_lang' => $current === 'en' ? 'cn' : 'en']);
        return redirect()->back();
    })->name('toggle.name-lang');
});
