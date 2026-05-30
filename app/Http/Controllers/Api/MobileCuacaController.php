<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use App\Models\PerkiraanCuaca;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MobileCuacaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'kecamatan_id' => ['nullable', 'uuid', 'exists:kecamatan,id'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'date' => ['nullable', 'date'],
            'limit_hourly' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : Carbon::now()->toDateString();
        $limitHourly = (int) $request->query('limit_hourly', 8);

        $kecamatans = Cache::remember(
            'mobile_cuaca:kecamatan:list',
            now()->addMinutes(10),
            fn () => Kecamatan::select('id', 'nama', 'latitude', 'longitude', 'kode_wilayah', 'level_rawan')
                ->orderBy('nama')
                ->get()
        );

        if ($kecamatans->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data kecamatan belum tersedia.',
            ], 404);
        }

        $selected = $this->resolveSelectedKecamatan($request, $kecamatans);

        $cacheKey = 'mobile_cuaca:data:' . md5(json_encode([
            'kecamatan_id' => $selected->id,
            'date' => $date,
            'limit_hourly' => $limitHourly,
        ]));

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($selected, $kecamatans, $date, $limitHourly) {
            return $this->buildCuacaPayload($selected, $kecamatans, $date, $limitHourly);
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    private function resolveSelectedKecamatan(Request $request, Collection $kecamatans)
    {
        if ($request->filled('kecamatan_id')) {
            $selected = $kecamatans->firstWhere('id', $request->query('kecamatan_id'));
            if ($selected) {
                return $selected;
            }
        }

        if ($request->filled('locality')) {
            $locality = strtolower($request->query('locality'));
            $locality = str_replace(['kecamatan ', 'kec. '], '', $locality);
            $locality = explode(',', $locality)[0];
            $locality = trim($locality);

            $selected = $kecamatans->first(function ($k) use ($locality) {
                $nama = strtolower($k->nama);
                return str_contains($nama, $locality) || str_contains($locality, $nama);
            });
            if ($selected) {
                return $selected;
            }
        }

        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');

            return $kecamatans
                ->filter(fn ($kecamatan) => $kecamatan->latitude !== null && $kecamatan->longitude !== null)
                ->sortBy(function ($kecamatan) use ($lat, $lng) {
                    $dLat = (float) $kecamatan->latitude - $lat;
                    $dLng = (float) $kecamatan->longitude - $lng;
                    return ($dLat * $dLat) + ($dLng * $dLng);
                })
                ->first() ?? $this->defaultKecamatan($kecamatans);
        }

        return $this->defaultKecamatan($kecamatans);
    }

    private function defaultKecamatan(Collection $kecamatans)
    {
        return $kecamatans->first(fn ($kecamatan) => strcasecmp($kecamatan->nama, 'Kaliwates') === 0)
            ?? $kecamatans->first();
    }

    private function buildCuacaPayload($selected, Collection $kecamatans, string $date, int $limitHourly): array
    {
        $now = Carbon::now();
        $windowStart = $now->copy()->subDays(2);
        $windowEnd = $now->copy()->addDays(3);

        $rows = PerkiraanCuaca::whereBetween('waktu_lokal', [$windowStart, $windowEnd])
            ->orderBy('waktu_lokal')
            ->get()
            ->groupBy('kecamatan_id');

        $selectedRows = $rows->get($selected->id, collect());
        $current = $this->nearestWeather($selectedRows, $now);
        $hourly = $this->hourlyForecast($selectedRows, $now, $date, $limitHourly);

        $otherDistricts = $kecamatans
            ->reject(fn ($kecamatan) => $kecamatan->id === $selected->id)
            ->map(function ($kecamatan) use ($rows, $now) {
                $weather = $this->nearestWeather($rows->get($kecamatan->id, collect()), $now);

                return [
                    'kecamatan_id' => $kecamatan->id,
                    'nama' => $kecamatan->nama,
                    'waktu_lokal' => $weather?->waktu_lokal?->format('Y-m-d H:i:s'),
                    'suhu' => $this->numberOrNull($weather?->suhu),
                    'curah_hujan' => $this->numberOrNull($weather?->curah_hujan),
                    'weather_code' => $weather?->weather_code,
                    'deskripsi_cuaca' => $weather?->deskripsi_cuaca,
                    'icon' => $this->weatherIcon($weather?->deskripsi_cuaca),
                ];
            })
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $lastUpdated = $rows
            ->flatten(1)
            ->max(fn ($weather) => $weather->dibuat_pada?->timestamp);

        return [
            'selected_kecamatan' => [
                'id' => $selected->id,
                'nama' => $selected->nama,
                'latitude' => $this->numberOrNull($selected->latitude),
                'longitude' => $this->numberOrNull($selected->longitude),
            ],
            'current' => $current ? $this->formatWeather($current, true) : null,
            'hourly_forecast' => $hourly,
            'other_districts' => $otherDistricts,
            'last_updated' => $lastUpdated ? Carbon::createFromTimestamp($lastUpdated)->format('Y-m-d H:i:s') : null,
        ];
    }

    private function nearestWeather(Collection $records, Carbon $now): ?PerkiraanCuaca
    {
        return $records
            ->sortBy(fn ($weather) => abs($weather->waktu_lokal->diffInSeconds($now, false)))
            ->first();
    }

    private function hourlyForecast(Collection $records, Carbon $now, string $date, int $limit): array
    {
        $forecast = $records
            ->filter(fn ($weather) => $weather->waktu_lokal >= $now && $weather->waktu_lokal->toDateString() === $date)
            ->sortBy('waktu_lokal')
            ->values();

        if ($forecast->count() < $limit) {
            $existingIds = $forecast->pluck('id')->all();
            $fallback = $records
                ->filter(fn ($weather) => $weather->waktu_lokal >= $now && !in_array($weather->id, $existingIds, true))
                ->sortBy('waktu_lokal')
                ->values();

            $forecast = $forecast->concat($fallback);
        }

        return $forecast
            ->take($limit)
            ->map(fn ($weather) => [
                'waktu_lokal' => $weather->waktu_lokal?->format('Y-m-d H:i:s'),
                'jam' => $weather->waktu_lokal?->format('H:i'),
                'tanggal' => $weather->waktu_lokal?->format('Y-m-d'),
                'suhu' => $this->numberOrNull($weather->suhu),
                'kelembapan' => $this->numberOrNull($weather->kelembapan),
                'curah_hujan' => $this->numberOrNull($weather->curah_hujan),
                'cloud_cover' => $this->numberOrNull($weather->cloud_cover),
                'weather_code' => $weather->weather_code,
                'deskripsi_cuaca' => $weather->deskripsi_cuaca,
                'kecepatan_angin' => $this->numberOrNull($weather->kecepatan_angin),
                'arah_angin' => $this->normalDirection($weather->arah_angin),
                'uv_index' => $this->numberOrNull($weather->uv_index),
                'visibilitas' => $this->numberOrNull($weather->visibilitas),
                'icon' => $this->weatherIcon($weather->deskripsi_cuaca),
            ])
            ->values()
            ->all();
    }

    private function formatWeather(PerkiraanCuaca $weather, bool $withDetails = false): array
    {
        $data = [
            'waktu_lokal' => $weather->waktu_lokal?->format('Y-m-d H:i:s'),
            'suhu' => $this->numberOrNull($weather->suhu),
            'kelembapan' => $this->numberOrNull($weather->kelembapan),
            'curah_hujan' => $this->numberOrNull($weather->curah_hujan),
            'cloud_cover' => $this->numberOrNull($weather->cloud_cover),
            'weather_code' => $weather->weather_code,
            'deskripsi_cuaca' => $weather->deskripsi_cuaca,
            'kecepatan_angin' => $this->numberOrNull($weather->kecepatan_angin),
            'arah_angin' => $this->normalDirection($weather->arah_angin),
            'uv_index' => $this->numberOrNull($weather->uv_index),
            'visibilitas' => $this->numberOrNull($weather->visibilitas),
            'icon' => $this->weatherIcon($weather->deskripsi_cuaca),
        ];

        if ($withDetails) {
            $data['dibuat_pada'] = $weather->dibuat_pada?->format('Y-m-d H:i:s');
        }

        return $data;
    }

    private function numberOrNull($value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;
        return floor($number) == $number ? (int) $number : $number;
    }

    private function weatherIcon(?string $description): string
    {
        $desc = strtolower((string) $description);

        return match (true) {
            str_contains($desc, 'petir') || str_contains($desc, 'thunderstorm') => 'hujan-petir',
            str_contains($desc, 'lebat') || str_contains($desc, 'heavy') => 'hujan-lebat',
            str_contains($desc, 'sedang') || str_contains($desc, 'moderate') => 'hujan-sedang',
            str_contains($desc, 'hujan') || str_contains($desc, 'rain') => 'hujan-ringan',
            str_contains($desc, 'cerah berawan') || str_contains($desc, 'few clouds') || str_contains($desc, 'scattered clouds') => 'cerah-berawan',
            str_contains($desc, 'berawan') || str_contains($desc, 'mendung') || str_contains($desc, 'cloud') => 'berawan',
            str_contains($desc, 'cerah') || str_contains($desc, 'clear') => 'cerah',
            default => 'cerah-berawan',
        };
    }

    private function normalDirection($direction): ?string
    {
        if ($direction === null || $direction === '') {
            return null;
        }

        if (is_numeric($direction)) {
            $degrees = ((float) $direction) % 360;
            $labels = ['Utara', 'Timur Laut', 'Timur', 'Tenggara', 'Selatan', 'Barat Daya', 'Barat', 'Barat Laut'];
            return $labels[(int) floor(($degrees + 22.5) / 45) % 8];
        }

        $normalized = strtoupper(trim((string) $direction));
        $map = [
            'N' => 'Utara',
            'NE' => 'Timur Laut',
            'E' => 'Timur',
            'SE' => 'Tenggara',
            'S' => 'Selatan',
            'SW' => 'Barat Daya',
            'W' => 'Barat',
            'NW' => 'Barat Laut',
        ];

        return $map[$normalized] ?? (string) $direction;
    }
}
