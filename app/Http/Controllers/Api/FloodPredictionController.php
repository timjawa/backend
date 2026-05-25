<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FloodPredictionController extends Controller
{
    public function realtime(): JsonResponse
    {
        $rows = $this->fetchRealtimeRows();

        if ($rows->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'source' => 'database',
                'generated_at' => now()->toIso8601String(),
                'threshold' => null,
                'summary' => $this->summary([]),
                'data' => [],
            ]);
        }

        $payload = $rows->map(fn ($row) => $this->toPredictionPayload($row))->values();
        $predictionBaseUrl = rtrim(env('PREDICTION_API_URL', 'https://abinugroh00-prediksi-banjir.hf.space'), '/');

        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post("{$predictionBaseUrl}/predict-batch", [
                'data' => $payload,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Gagal meminta prediksi banjir.',
                'detail' => $response->json() ?? $response->body(),
            ], 502);
        }

        $predictions = collect($response->json('hasil', []));
        $data = $rows->values()->map(function ($row, int $index) use ($predictions) {
            $prediction = $predictions->get($index, []);
            $modelProbability = (float) ($prediction['probabilitas_banjir'] ?? 0);
            $calibrated = $this->calibratePrediction($row, $modelProbability);

            return [
                'cuaca_id' => $row->cuaca_id,
                'kecamatan_id' => $row->kecamatan_id,
                'kecamatan_nama' => $row->kecamatan_nama,
                'fetched_at' => $row->fetched_at ? Carbon::parse($row->fetched_at)->toIso8601String() : null,
                'suhu' => $this->numberOrNull($row->suhu),
                'kelembapan' => $this->numberOrNull($row->kelembapan),
                'curah_hujan' => $this->numberOrZero($row->curah_hujan),
                'tekanan_udara' => $this->numberOrNull($row->tekanan_udara),
                'kecepatan_angin' => $this->numberOrNull($row->kecepatan_angin),
                'cloud_cover' => $this->numberOrNull($row->cloud_cover),
                'elevasi' => $this->numberOrNull($row->elevasi),
                'level_rawan' => $row->level_rawan,
                'jumlah_laporan_banjir_7_hari' => (int) ($row->jumlah_laporan_banjir_7_hari ?? 0),
                'probabilitas_banjir' => $calibrated['probability'],
                'probabilitas_tidak_banjir' => 1 - $calibrated['probability'],
                'probabilitas_model' => $modelProbability,
                'threshold' => $calibrated['threshold'],
                'prediksi' => $calibrated['prediction'],
                'label' => $calibrated['label'],
                'status_operasional' => $calibrated['status'],
                'kategori_risiko' => $calibrated['risk'],
                'alasan_kalibrasi' => $calibrated['reason'],
                'fitur_model' => $prediction['fitur_model'] ?? null,
            ];
        })->values()->all();

        return response()->json([
            'status' => 'success',
            'source' => 'database+prediction_api',
            'generated_at' => now()->toIso8601String(),
            'threshold' => $data[0]['threshold'] ?? null,
            'summary' => $this->summary($data),
            'data' => $data,
        ]);
    }

    private function fetchRealtimeRows()
    {
        return DB::table('cuaca_realtime as cr')
            ->join('kecamatan as k', 'k.id', '=', 'cr.kecamatan_id')
            ->select([
                'cr.id as cuaca_id',
                'cr.kecamatan_id',
                'k.nama as kecamatan_nama',
                'k.elevasi',
                'k.level_rawan',
                'cr.suhu',
                'cr.kelembapan',
                DB::raw('COALESCE(cr.curah_hujan, 0) as curah_hujan'),
                'cr.tekanan_udara',
                'cr.kecepatan_angin',
                'cr.cloud_cover',
                'cr.fetched_at',
                DB::raw("(
                    SELECT COUNT(*)
                    FROM laporan_bencana lb
                    WHERE lb.kecamatan_id = k.id
                      AND lb.is_draft = 0
                      AND lb.status <> 'ditolak'
                      AND LOWER(lb.jenis_bencana) LIKE '%banjir%'
                      AND lb.dibuat_pada >= DATE_SUB(COALESCE(cr.fetched_at, NOW()), INTERVAL 7 DAY)
                      AND lb.dibuat_pada <= COALESCE(cr.fetched_at, NOW())
                ) as jumlah_laporan_banjir_7_hari"),
            ])
            ->orderBy('k.nama')
            ->get();
    }

    private function toPredictionPayload(object $row): array
    {
        $date = $row->fetched_at ? Carbon::parse($row->fetched_at) : now();

        return [
            'tanggal' => $date->toDateString(),
            'kecamatan_id' => (string) $row->kecamatan_id,
            'curah_hujan' => $this->numberOrZero($row->curah_hujan),
            'kelembapan' => $this->numberOrZero($row->kelembapan),
            'suhu' => $this->numberOrZero($row->suhu),
            'tekanan_udara' => $this->numberOrZero($row->tekanan_udara),
            'kecepatan_angin' => $this->numberOrZero($row->kecepatan_angin),
            'cloud_cover' => $this->numberOrZero($row->cloud_cover),
            'elevasi' => $this->numberOrZero($row->elevasi),
            'level_rawan' => $row->level_rawan ?? 'rendah',
            'jumlah_laporan_banjir_7_hari' => (float) ($row->jumlah_laporan_banjir_7_hari ?? 0),
        ];
    }

    private function summary(array $data): array
    {
        $total = count($data);
        $riskCounts = [
            'rendah' => 0,
            'sedang' => 0,
            'tinggi' => 0,
            'kritis' => 0,
        ];
        $statusCounts = [
            'aman' => 0,
            'waspada' => 0,
            'banjir' => 0,
        ];

        foreach ($data as $item) {
            $risk = $item['kategori_risiko'] ?? 'rendah';
            $riskCounts[$risk] = ($riskCounts[$risk] ?? 0) + 1;

            $status = $item['status_operasional'] ?? $item['label'] ?? 'aman';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        $probabilities = array_column($data, 'probabilitas_banjir');

        return [
            'total_kecamatan' => $total,
            'prediksi_banjir' => array_sum(array_column($data, 'prediksi')),
            'probabilitas_rata_rata' => $total ? round(array_sum($probabilities) / $total, 4) : 0,
            'probabilitas_maksimum' => $probabilities ? round(max($probabilities), 4) : 0,
            'risiko' => $riskCounts,
            'status_operasional' => $statusCounts,
            'terakhir_diperbarui' => collect($data)->pluck('fetched_at')->filter()->max(),
        ];
    }

    private function riskCategory(float $probability): string
    {
        if ($probability <= 0.25) {
            return 'rendah';
        }
        if ($probability <= 0.50) {
            return 'sedang';
        }
        if ($probability <= 0.70) {
            return 'tinggi';
        }

        return 'kritis';
    }

    private function calibratePrediction(object $row, float $modelProbability): array
    {
        $rain = $this->numberOrZero($row->curah_hujan);
        $humidity = $this->numberOrZero($row->kelembapan);
        $cloudCover = $this->numberOrZero($row->cloud_cover);
        $windSpeed = $this->numberOrZero($row->kecepatan_angin);
        $reports = (int) ($row->jumlah_laporan_banjir_7_hari ?? 0);
        $rawan = strtolower((string) ($row->level_rawan ?? 'rendah'));

        $triggerScore = 0;
        if ($rain >= 20) {
            $triggerScore += 3;
        } elseif ($rain >= 10) {
            $triggerScore += 2;
        } elseif ($rain >= 2) {
            $triggerScore += 1;
        }

        if ($reports >= 3) {
            $triggerScore += 3;
        } elseif ($reports >= 1) {
            $triggerScore += 2;
        }

        if ($humidity >= 90 && $cloudCover >= 80) {
            $triggerScore += 1;
        }

        if ($windSpeed >= 10) {
            $triggerScore += 1;
        }

        if ($rawan === 'tinggi') {
            $triggerScore += 1;
        }

        $probability = $modelProbability;
        $reason = 'model_dengan_pemicu_lapangan';

        // Kalau tidak ada hujan dan tidak ada laporan, skor model dibatasi supaya
        // wilayah rawan tidak otomatis dilabeli banjir hanya karena elevasi/kerawanan.
        if ($rain < 1 && $reports === 0) {
            $cap = match ($rawan) {
                'tinggi' => 0.42,
                'sedang' => 0.32,
                default => 0.24,
            };

            if ($humidity >= 90 && $cloudCover >= 80) {
                $cap += 0.08;
            }

            $probability = min($modelProbability, $cap);
            $reason = 'dibatasi_karena_tidak_ada_hujan_dan_laporan';
        }

        $threshold = 0.65;
        $prediction = ($probability >= $threshold && $triggerScore >= 3) ? 1 : 0;

        if ($prediction === 1) {
            $status = 'banjir';
        } elseif ($probability >= 0.45 || $triggerScore >= 2) {
            $status = 'waspada';
        } else {
            $status = 'aman';
        }

        return [
            'probability' => round($probability, 4),
            'threshold' => $threshold,
            'prediction' => $prediction,
            'label' => $status,
            'status' => $status,
            'risk' => $this->riskCategory($probability),
            'reason' => $reason,
        ];
    }

    private function numberOrNull($value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function numberOrZero($value): float
    {
        return $value === null ? 0.0 : (float) $value;
    }
}
