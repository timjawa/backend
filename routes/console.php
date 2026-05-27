<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('weather:ringkas', function () {
    $this->info('Memulai ringkasan data historis cuaca...');
    try {
        Illuminate\Support\Facades\DB::statement('CALL ringkas_historical_cuaca_lama()');
        $this->info('Ringkasan data historis cuaca berhasil dijalankan!');
    } catch (\Exception $e) {
        $this->error('Gagal menjalankan ringkasan: ' . $e->getMessage());
    }
})->purpose('Menjalankan procedure ringkas_historical_cuaca_lama secara manual');
