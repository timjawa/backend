<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\KecamatanController;
use App\Http\Controllers\Api\KontakDaruratController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Jember Siaga
|--------------------------------------------------------------------------
*/

// =============================================
// AUTH ROUTES (Public)
// =============================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Mobile auth endpoints (token-based)
Route::post('/mobile/login', [AuthController::class, 'loginMobile']);

// =============================================
// AUTH ROUTES (Protected)
// =============================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    [AuthController::class, 'user']);
    
    // Mobile auth endpoints (protected)
    Route::post('/mobile/logout', [AuthController::class, 'logoutMobile']);
    Route::post('/mobile/logout-all', [AuthController::class, 'logoutAllDevices']);
});

// =============================================
// BERITA ROUTES
// =============================================
// Public: list & show
Route::get('/berita',      [BeritaController::class, 'index']);
Route::get('/berita/{id}', [BeritaController::class, 'show']);

// Admin only: create, update, delete
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::post('/berita',          [BeritaController::class, 'store']);
    Route::put('/berita/{id}',      [BeritaController::class, 'update']);
    Route::delete('/berita/{id}',   [BeritaController::class, 'destroy']);
});

// =============================================
// KECAMATAN ROUTES
// =============================================
// Public: list, show, & stats
Route::get('/kecamatan',      [KecamatanController::class, 'index']);
Route::get('/kecamatan/stats', [KecamatanController::class, 'stats']);
Route::get('/kecamatan/{id}', [KecamatanController::class, 'show']);

// Admin only: create, update, delete
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::post('/kecamatan',          [KecamatanController::class, 'store']);
    Route::put('/kecamatan/{id}',      [KecamatanController::class, 'update']);
    Route::delete('/kecamatan/{id}',   [KecamatanController::class, 'destroy']);
});

// =============================================
// KONTAK DARURAT ROUTES
// =============================================
// Public: list & show
Route::get('/kontak-darurat',      [KontakDaruratController::class, 'index']);
Route::get('/kontak-darurat/{id}', [KontakDaruratController::class, 'show']);

// Admin only: create, update, delete
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::post('/kontak-darurat',          [KontakDaruratController::class, 'store']);
    Route::put('/kontak-darurat/{id}',      [KontakDaruratController::class, 'update']);
    Route::delete('/kontak-darurat/{id}',   [KontakDaruratController::class, 'destroy']);
});

// =============================================
// WEATHER ROUTES
// =============================================
Route::get('/weather/realtime', [\App\Http\Controllers\WeatherController::class, 'getRealtime']);
Route::get('/weather/forecast', [\App\Http\Controllers\WeatherController::class, 'getForecast']);

// =============================================
// FIREBASE AUTH ROUTES (Public)
// =============================================
Route::post('/auth/firebase', [\App\Http\Controllers\Api\AuthFirebaseController::class, 'verifyFirebase']);
