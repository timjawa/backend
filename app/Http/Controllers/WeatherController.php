<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kecamatan;
use App\Models\CuacaRealtime;
use App\Models\PerkiraanCuaca;
use App\Models\HistoricalCuaca;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WeatherController extends Controller
{
    public function getHistorical(Request $request)
    {
        $kecamatanId = $request->query('kecamatan_id');
        
        $query = HistoricalCuaca::with('kecamatan:id,nama')
            ->where('waktu', '>=', Carbon::now()->subDays(7));

        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        $data = $query->orderBy('waktu', 'asc')->get();

        // Kelompokkan per kecamatan
        $grouped = $data->groupBy(function($item) {
            return $item->kecamatan->nama;
        });

        // Hitung rata-rata per hari per kecamatan
        $result = [];
        foreach ($grouped as $kecamatan => $records) {
            $dailyAvg = [];
            foreach ($records as $record) {
                $date = Carbon::parse($record->waktu)->format('Y-m-d');
                if (!isset($dailyAvg[$date])) {
                    $dailyAvg[$date] = [
                        'suhu_total' => 0,
                        'hujan_total' => 0,
                        'count' => 0,
                    ];
                }
                $dailyAvg[$date]['suhu_total'] += $record->suhu;
                $dailyAvg[$date]['hujan_total'] += $record->curah_hujan ?? 0;
                $dailyAvg[$date]['count']++;
            }

            $formattedDaily = [];
            foreach ($dailyAvg as $date => $stats) {
                $formattedDaily[] = [
                    'tanggal' => $date,
                    'suhu_avg' => round($stats['suhu_total'] / $stats['count'], 1),
                    'hujan_avg' => round($stats['hujan_total'] / $stats['count'], 1),
                ];
            }
            $result[$kecamatan] = $formattedDaily;
        }

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    /**
     * Smart endpoint: returns weather data for a specific date.
     * - If date >= today → uses perkiraan_cuaca (BMKG forecast)
     * - If date < today → uses historical_cuaca (recorded history)
     * Returns data grouped by kecamatan in a unified format.
     */
    public function getWeatherByDate(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $today = now()->format('Y-m-d');

        if ($date >= $today) {
            // === FORECAST MODE: use perkiraan_cuaca ===
            $data = PerkiraanCuaca::with('kecamatan:id,nama')
                ->whereDate('waktu_lokal', $date)
                ->orderBy('waktu_lokal')
                ->get();

            $grouped = $data->groupBy(fn($item) => $item->kecamatan->nama ?? 'Unknown');

            return response()->json([
                'status' => 'success',
                'source' => 'forecast',
                'date' => $date,
                'data' => $grouped
            ]);
        } else {
            // === HISTORICAL MODE: use historical_cuaca ===
            $data = HistoricalCuaca::with('kecamatan:id,nama')
                ->whereDate('waktu', $date)
                ->orderBy('waktu')
                ->get();

            // Normalize to match forecast format
            $grouped = $data->groupBy(fn($item) => $item->kecamatan->nama ?? 'Unknown');

            $normalizedGrouped = [];
            foreach ($grouped as $kecName => $records) {
                $normalizedGrouped[$kecName] = $records->map(function ($record) {
                    // Derive weather description from cloud_cover and curah_hujan
                    $cloudCover = $record->cloud_cover ?? 0;
                    $curahHujan = $record->curah_hujan ?? 0;
                    $deskripsi = 'Cerah';
                    if ($curahHujan > 5) {
                        $deskripsi = 'Hujan Sedang';
                    } elseif ($curahHujan > 0) {
                        $deskripsi = 'Hujan Ringan';
                    } elseif ($cloudCover >= 75) {
                        $deskripsi = 'Berawan';
                    } elseif ($cloudCover >= 40) {
                        $deskripsi = 'Cerah Berawan';
                    }

                    return [
                        'waktu_lokal' => $record->waktu,
                        'suhu' => $record->suhu,
                        'kelembapan' => $record->kelembapan,
                        'curah_hujan' => $record->curah_hujan,
                        'cloud_cover' => $record->cloud_cover,
                        'kecepatan_angin' => null,
                        'deskripsi_cuaca' => $deskripsi,
                    ];
                })->values();
            }

            return response()->json([
                'status' => 'success',
                'source' => 'historical',
                'date' => $date,
                'data' => $normalizedGrouped
            ]);
        }
    }
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

    public function refreshRealtime()
    {
        // Force fetch from API and archive old data
        $this->fetchOpenWeather();

        // Ambil data terbaru yang baru di-fetch
        $data = CuacaRealtime::with('kecamatan:id,nama')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data cuaca berhasil diperbarui secara manual dan data lama telah diarsipkan.',
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

    public function refreshForecast()
    {
        // Force fetch from BMKG API
        $this->fetchBMKG();

        // Ambil data terbaru yang baru di-fetch
        $data = PerkiraanCuaca::with('kecamatan:id,nama')->orderBy('waktu_lokal')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data prakiraan cuaca berhasil diperbarui dari BMKG.',
            'data' => $data
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
            // Arsipkan data lama sebelum dihapus
            $oldData = CuacaRealtime::all();
            $historyRecords = [];
            foreach ($oldData as $old) {
                $historyRecords[] = [
                    'id' => Str::uuid()->toString(),
                    'kecamatan_id' => $old->kecamatan_id,
                    'waktu' => $old->fetched_at ?? now(),
                    'suhu' => $old->suhu,
                    'kelembapan' => $old->kelembapan,
                    'curah_hujan' => $old->curah_hujan,
                    'cloud_cover' => $old->cloud_cover,
                    'created_at' => now(),
                ];
            }

            if (!empty($historyRecords)) {
                HistoricalCuaca::insert($historyRecords);
            }

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
                            $waktuLokal = isset($cuaca['local_datetime']) ? date('Y-m-d H:i:s', strtotime($cuaca['local_datetime'])) : $now;
                            
                            $records[] = [
                                'kecamatan_id' => $kecamatanId,
                                'waktu_lokal' => $waktuLokal,
                                'suhu' => $cuaca['t'] ?? null,
                                'kelembapan' => $cuaca['hu'] ?? null,
                                'curah_hujan' => $cuaca['tp'] ?? null,
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
            // Gunakan upsert: update data yang sudah ada, insert yang baru
            // Key unik: kecamatan_id + waktu_lokal
            $chunks = array_chunk($records, 500);
            foreach ($chunks as $chunk) {
                foreach ($chunk as $record) {
                    PerkiraanCuaca::updateOrCreate(
                        [
                            'kecamatan_id' => $record['kecamatan_id'],
                            'waktu_lokal' => $record['waktu_lokal'],
                        ],
                        $record
                    );
                }
            }

            // Hapus data yang sudah lewat lebih dari 3 hari agar tidak menumpuk
            PerkiraanCuaca::where('waktu_lokal', '<', Carbon::now()->subDays(3))->delete();
        }
    }
}
