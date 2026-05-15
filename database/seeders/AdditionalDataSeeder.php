<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdditionalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =============================================
        // SEED CUACA_REALTIME & PERKIRAAN_CUACA
        // =============================================
        $kecamatans = DB::table('kecamatan')->get();
        if ($kecamatans->isEmpty()) {
            $this->command->warn('Kecamatan table is empty. Please run DatabaseSeeder first.');
            return;
        }

        foreach ($kecamatans as $kec) {
            // Realtime
            DB::table('cuaca_realtime')->insert([
                'id' => (string) Str::uuid(),
                'kecamatan_id' => $kec->id,
                'suhu' => rand(24, 32),
                'kelembapan' => rand(60, 90),
                'weather_code' => 1000,
                'deskripsi' => 'Cerah Berawan',
                'fetched_at' => now(),
            ]);

            // Perkiraan (next 5 hours)
            for ($i = 1; $i <= 5; $i++) {
                DB::table('perkiraan_cuaca')->insert([
                    'id' => (string) Str::uuid(),
                    'kecamatan_id' => $kec->id,
                    'waktu_lokal' => now()->addHours($i),
                    'suhu' => rand(24, 32),
                    'weather_code' => 1000,
                    'deskripsi_cuaca' => 'Cerah',
                    'dibuat_pada' => now(),
                ]);
            }
        }

        // =============================================
        // SEED LAPORAN BENCANA
        // =============================================
        $user = DB::table('users')->where('role', 'masyarakat')->first();
        if ($user) {
            $laporanId = (string) Str::uuid();
            DB::table('laporan_bencana')->insert([
                'id' => $laporanId,
                'user_id' => $user->id,
                'kecamatan_id' => $kecamatans->first()->id,
                'jenis_bencana' => 'banjir',
                'deskripsi' => 'Terjadi genangan air setinggi mata kaki di depan pasar akibat hujan lebat.',
                'alamat_lengkap' => 'Jl. Raya No. 12, Jember',
                'status' => 'baru',
                'dibuat_pada' => now(),
            ]);
        }
    }
}
