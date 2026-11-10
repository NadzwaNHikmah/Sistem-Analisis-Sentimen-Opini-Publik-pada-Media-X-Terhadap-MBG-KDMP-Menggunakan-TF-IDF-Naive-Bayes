<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SentimenController;
use App\Http\Controllers\AuthController;

// ================ ROUTES TANPA AUTH (PUBLIC) ================
Route::get('/about', [SentimenController::class, 'about'])->name('about');

Route::get('/', function () {
    return redirect()->route('about');
});

// ================ ROUTES DENGAN AUTH ================
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/mbg', [SentimenController::class, 'mbg'])->name('dashboard.mbg');
    Route::get('/dashboard/kdmp', [SentimenController::class, 'kdmp'])->name('dashboard.kdmp');
    Route::get('/dashboard/history', [SentimenController::class, 'history'])->name('dashboard.history');
    Route::delete('/dashboard/history/clear-all', [SentimenController::class, 'clearAllHistory'])->name('dashboard.history.clear-all');
    Route::get('/dashboard/history/{id}/tweets', [SentimenController::class, 'historyTweets'])->name('dashboard.history.tweets');
    Route::delete('/dashboard/history/{id}', [SentimenController::class, 'destroyHistory'])->name('dashboard.history.destroy');
    Route::get('/dashboard/filter-data', [SentimenController::class, 'filterData'])->name('dashboard.filter');
    Route::get('/dashboard/export-csv', [SentimenController::class, 'exportCsv'])->name('dashboard.export.csv');
    Route::get('/dashboard/twitter-cookies', [SentimenController::class, 'getTwitterCookies'])->name('dashboard.cookies.get');
    Route::post('/dashboard/twitter-cookies', [SentimenController::class, 'updateTwitterCookies'])->name('dashboard.cookies.update');
    Route::post('/proses', [SentimenController::class, 'proses']);
    Route::get('/status', [SentimenController::class, 'status']);
    Route::get('/progress', [SentimenController::class, 'progress']);
    Route::get('/logs', [SentimenController::class, 'logs']);
});

// ================ AUTHENTICATION ================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.proses');
});

Route::middleware('auth')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});