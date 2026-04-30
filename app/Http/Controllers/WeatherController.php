<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kecamatan;
use App\Models\CuacaRealtime;
use App\Models\PerkiraanCuaca;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;

class WeatherController extends Controller
{
    public function getRealtime()
    {
        // Cek data terakhir
        $latest = CuacaRealtime::latest('fetched_at')->first();

        // Jika data kosong atau usianya lebih dari 10 menit
        if (!$latest || $latest->fetched_at->diffInMinutes(now()) >= 10) {
            $this->fetchOpenWeather();
        }

        // Ambil semua data terbaru (join dengan kecamatan untuk nama)
        $data = CuacaRealtime::with('kecamatan:id,nama')->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function getForecast()
    {
        // Cek data terakhir
        $latest = PerkiraanCuaca::latest('dibuat_pada')->first();

        // Jika data kosong atau usianya lebih dari 1 jam
        if (!$latest || $latest->dibuat_pada->diffInHours(now()) >= 1) {
            $this->fetchBMKG();
        }

        // Ambil semua data (join kecamatan)
        $data = PerkiraanCuaca::with('kecamatan:id,nama')->orderBy('waktu_lokal')->get();

        // Mengelompokkan berdasarkan kecamatan untuk kemudahan frontend
        $grouped = $data->groupBy(function($item) {
            return $item->kecamatan->nama;
        });

        return response()->json([
            'status' => 'success',
            'data' => $grouped
        ]);
    }

    private function fetchOpenWeather()
    {
        $kecamatans = Kecamatan::all();
        $apiKey = env('OPENWEATHER_API_KEY');

        if (!$apiKey) {
            return;
        }

        // Fetch secara concurrent (pool)
        $responses = Http::pool(fn (Pool $pool) => $kecamatans->map(function ($kec) use ($pool, $apiKey) {
            return $pool->as($kec->id)->withoutVerifying()->get('https://api.openweathermap.org/data/2.5/weather', [
                'lat' => $kec->latitude,
                'lon' => $kec->longitude,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'id'
            ]);
        }));

        $now = now();
        $records = [];

        foreach ($responses as $kecamatanId => $response) {
            if ($response instanceof \Exception) {
                continue;
            }
            if (method_exists($response, 'ok') && $response->ok()) {
                $data = $response->json();
                
                $records[] = [
                    'id' => Str::uuid()->toString(),
                    'kecamatan_id' => $kecamatanId,
                    'suhu' => $data['main']['temp'] ?? null,
                    'feels_like' => $data['main']['feels_like'] ?? null,
                    'kelembapan' => $data['main']['humidity'] ?? null,
                    'cloud_cover' => $data['clouds']['all'] ?? null,
                    'kecepatan_angin' => $data['wind']['speed'] ?? null,
                    'arah_angin' => $data['wind']['deg'] ?? null,
                    'weather_code' => $data['weather'][0]['id'] ?? null,
                    'deskripsi' => $data['weather'][0]['description'] ?? null,
                    'visibilitas' => $data['visibility'] ?? null,
                    'tekanan_udara' => $data['main']['pressure'] ?? null,
                    'fetched_at' => $now,
                ];
            }
        }

        if (!empty($records)) {
            // Hapus data lama karena ini realtime (hanya butuh 1 row per kecamatan)
            CuacaRealtime::truncate();
            
            // Insert data baru
            CuacaRealtime::insert($records);
        }
    }

    private function fetchBMKG()
    {
        $kecamatans = Kecamatan::all();

        // Fetch secara concurrent
        $responses = Http::pool(fn (Pool $pool) => $kecamatans->map(function ($kec) use ($pool) {
            return $pool->as($kec->id)->withoutVerifying()->get("https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$kec->kode_wilayah}");
        }));

        $now = now();
        $records = [];

        foreach ($responses as $kecamatanId => $response) {
            if ($response instanceof \Exception) {
                continue;
            }
            if (method_exists($response, 'ok') && $response->ok()) {
                $data = $response->json();
                
                if (isset($data['data'][0]['cuaca'])) {
                    $cuacaDays = $data['data'][0]['cuaca'];
                    
                    // cuacaDays adalah array of arrays (dikelompokkan per hari)
                    foreach ($cuacaDays as $dayCuaca) {
                        foreach ($dayCuaca as $cuaca) {
                            $records[] = [
                                'id' => Str::uuid()->toString(),
                                'kecamatan_id' => $kecamatanId,
                                'waktu_lokal' => isset($cuaca['local_datetime']) ? date('Y-m-d H:i:s', strtotime($cuaca['local_datetime'])) : $now,
                                'suhu' => $cuaca['t'] ?? null,
                                'kelembapan' => $cuaca['hu'] ?? null,
                                'cloud_cover' => $cuaca['tcc'] ?? null,
                                'weather_code' => $cuaca['weather'] ?? null,
                                'deskripsi_cuaca' => $cuaca['weather_desc'] ?? null,
                                'kecepatan_angin' => $cuaca['ws'] ?? null,
                                'arah_angin' => $cuaca['wd'] ?? null,
                                'visibilitas' => $cuaca['vs'] ?? null,
                                'dibuat_pada' => $now,
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($records)) {
            // Hapus data lama agar tidak menumpuk, karena trigger akan mengarsipkan
            PerkiraanCuaca::truncate();

            // Insert dalam batch agar memori tidak penuh
            $chunks = array_chunk($records, 500);
            foreach ($chunks as $chunk) {
                PerkiraanCuaca::insert($chunk);
            }
        }
    }
}
