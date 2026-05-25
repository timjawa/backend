<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\KecamatanController;
use App\Http\Controllers\Api\KontakDaruratController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FloodPredictionController;
use App\Http\Controllers\Api\LaporanBencanaController;
use App\Http\Controllers\Api\PenggunaController;
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
Route::post('/mobile/reset-password-force', [AuthController::class, 'forceResetPassword']);

// =============================================
// AUTH ROUTES (Protected)
// =============================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    [AuthController::class, 'user']);
    Route::put('/user',    [AuthController::class, 'updateProfile']);

    // Mobile auth endpoints (protected)
    Route::post('/mobile/logout', [AuthController::class, 'logoutMobile']);
    Route::post('/mobile/logout-all', [AuthController::class, 'logoutAllDevices']);

    // =============================================
    // LAPORAN BENCANA ROUTES (User authenticated)
    // =============================================
    Route::get('/laporan',       [LaporanBencanaController::class, 'index']);
    Route::post('/laporan',      [LaporanBencanaController::class, 'store']);
    Route::get('/laporan/{id}',  [LaporanBencanaController::class, 'show']);
    Route::post('/laporan/{id}', [LaporanBencanaController::class, 'update']);
});

// =============================================
// BERITA ROUTES
// =============================================
// Public: list & show
Route::get('/berita/stats', [BeritaController::class, 'stats']);
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
// LAPORAN BENCANA ROUTES
// =============================================
// User routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/laporan',      [LaporanBencanaController::class, 'index']);
    Route::post('/laporan',      [LaporanBencanaController::class, 'store']);
    Route::get('/laporan/{id}', [LaporanBencanaController::class, 'show']);
    Route::post('/laporan/{id}/comment', [LaporanBencanaController::class, 'addComment']);
});

// Admin only: manage all laporan
Route::middleware(['auth:sanctum', 'isAdmin'])->prefix('admin/laporan')->group(function () {
    Route::get('/',              [LaporanBencanaController::class, 'adminIndex']);
    Route::get('/stats',         [LaporanBencanaController::class, 'adminStats']);
    Route::get('/{id}',          [LaporanBencanaController::class, 'adminShow']);
    Route::put('/{id}/status',  [LaporanBencanaController::class, 'updateStatus']);
    Route::post('/{id}/comment', [LaporanBencanaController::class, 'addComment']);
    Route::delete('/{id}',       [LaporanBencanaController::class, 'adminDestroy']);
});

// Admin only: manage users (pengguna)
Route::middleware(['auth:sanctum', 'isAdmin'])->prefix('admin/pengguna')->group(function () {
    Route::get('/',              [PenggunaController::class, 'index']);
    Route::post('/',             [PenggunaController::class, 'store']);
    Route::get('/stats',         [PenggunaController::class, 'stats']);
    Route::get('/{id}',          [PenggunaController::class, 'show']);
    Route::put('/{id}/toggle-active', [PenggunaController::class, 'toggleActive']);
});

// =============================================
// POS PENGUNGSIAN ROUTES
// =============================================
// Public: list & show
Route::get('/pos-pengungsian',        [\App\Http\Controllers\Api\PosPengungsiController::class, 'index']);
Route::get('/pos-pengungsian/stats',  [\App\Http\Controllers\Api\PosPengungsiController::class, 'stats']);
Route::get('/pos-pengungsian/{id}',   [\App\Http\Controllers\Api\PosPengungsiController::class, 'show']);

// Admin only: create, update, delete
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::post('/pos-pengungsian',          [\App\Http\Controllers\Api\PosPengungsiController::class, 'store']);
    Route::put('/pos-pengungsian/{id}',      [\App\Http\Controllers\Api\PosPengungsiController::class, 'update']);
    Route::delete('/pos-pengungsian/{id}',   [\App\Http\Controllers\Api\PosPengungsiController::class, 'destroy']);
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
// PERINGATAN DINI ROUTES
// =============================================
// Public: list & show
Route::get('/peringatan-dini',      [\App\Http\Controllers\Api\PeringatanDiniController::class, 'index']);
Route::get('/peringatan-dini/{id}', [\App\Http\Controllers\Api\PeringatanDiniController::class, 'show']);

// Admin only: create, update, delete
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::post('/peringatan-dini',          [\App\Http\Controllers\Api\PeringatanDiniController::class, 'store']);
    Route::put('/peringatan-dini/{id}',      [\App\Http\Controllers\Api\PeringatanDiniController::class, 'update']);
    Route::delete('/peringatan-dini/{id}',   [\App\Http\Controllers\Api\PeringatanDiniController::class, 'destroy']);
});

// =============================================
// FAQ ROUTES
// =============================================
// Public: list & show
Route::get('/faq',      [FaqController::class, 'index']);
Route::get('/faq/{id}', [FaqController::class, 'show']);

// Admin only: create, update, delete
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::post('/faq',          [FaqController::class, 'store']);
    Route::put('/faq/{id}',      [FaqController::class, 'update']);
    Route::delete('/faq/{id}',   [FaqController::class, 'destroy']);
});

// =============================================
// WEATHER ROUTES
// =============================================
Route::get('/weather/realtime', [\App\Http\Controllers\WeatherController::class, 'getRealtime']);
Route::get('/weather/forecast', [\App\Http\Controllers\WeatherController::class, 'getForecast']);
Route::get('/weather/historical', [\App\Http\Controllers\WeatherController::class, 'getHistorical']);
Route::get('/weather/by-date', [\App\Http\Controllers\WeatherController::class, 'getWeatherByDate']);
Route::get('/prediksi-banjir/realtime', [FloodPredictionController::class, 'realtime']);

// Admin only: refresh weather
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::post('/weather/refresh', [\App\Http\Controllers\WeatherController::class, 'refreshRealtime']);
    Route::post('/weather/refresh-forecast', [\App\Http\Controllers\WeatherController::class, 'refreshForecast']);
});

// =============================================
// FIREBASE AUTH ROUTES (Public)
// =============================================
Route::post('/auth/firebase', [\App\Http\Controllers\Api\AuthFirebaseController::class, 'verifyFirebase']);

// =============================================
// PETA MARKER ROUTES
// =============================================
// Public: ambil semua marker aktif
Route::get('/peta-marker', [\App\Http\Controllers\Api\PetaMarkerController::class, 'index']);

// Admin only: tambah, edit, hapus marker
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::get('/admin/peta-marker',          [\App\Http\Controllers\Api\PetaMarkerController::class, 'adminIndex']);
    Route::get('/admin/peta-marker/{id}',     [\App\Http\Controllers\Api\PetaMarkerController::class, 'show']);
    Route::post('/admin/peta-marker',         [\App\Http\Controllers\Api\PetaMarkerController::class, 'store']);
    Route::put('/admin/peta-marker/{id}',     [\App\Http\Controllers\Api\PetaMarkerController::class, 'update']);
    Route::delete('/admin/peta-marker/{id}',  [\App\Http\Controllers\Api\PetaMarkerController::class, 'destroy']);
});
