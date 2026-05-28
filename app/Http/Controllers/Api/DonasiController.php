<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\KampanyeDonasi;
use App\Models\NotifikasiMidtrans;
use App\Models\PembayaranDonasi;
use App\Services\MidtransDonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DonasiController extends Controller
{
    public function __construct(private readonly MidtransDonationService $midtrans)
    {
    }

    public function kampanyeIndex(Request $request): JsonResponse
    {
        $query = KampanyeDonasi::with('kecamatan:id,nama')
            ->whereIn('status', ['aktif', 'ditutup'])
            ->orderByRaw("CASE status WHEN 'aktif' THEN 0 WHEN 'ditutup' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_mulai');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('jenis_bencana', 'like', "%{$search}%")
                    ->orWhereHas('kecamatan', fn ($k) => $k->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('jenis_bencana')) {
            $query->where('jenis_bencana', $request->jenis_bencana);
        }

        if ($request->filled('kecamatan_id')) {
            $query->where('kecamatan_id', $request->kecamatan_id);
        }

        $perPage = min((int) $request->input('per_page', 10), 30);

        return response()->json($query->paginate($perPage));
    }

    public function kampanyeShow(string $id): JsonResponse
    {
        $kampanye = KampanyeDonasi::with([
            'kecamatan:id,nama',
            'laporanBencana:id,jenis_bencana,status,alamat_lengkap',
            'penyaluran' => fn ($q) => $q->where('status', 'publish')->orderByDesc('tanggal_penyaluran'),
        ])
            ->whereIn('status', ['aktif', 'ditutup'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $kampanye,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kampanye_id' => ['required', 'exists:kampanye_donasi,id'],
            'nominal' => ['required', 'numeric', 'min:10000'],
            'pesan' => ['nullable', 'string', 'max:1000'],
            'anonim' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $kampanye = KampanyeDonasi::where('status', 'aktif')->find($request->kampanye_id);
        if (!$kampanye) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kampanye donasi tidak aktif atau tidak ditemukan.',
            ], 422);
        }

        $user = $request->user();
        $orderId = $this->midtrans->createOrderId();

        try {
            $result = DB::transaction(function () use ($request, $kampanye, $user, $orderId) {
                $donasi = Donasi::create([
                    'kampanye_id' => $kampanye->id,
                    'user_id' => $user?->id,
                    'nama_donatur' => $user?->name,
                    'email_donatur' => $user?->email,
                    'telepon_donatur' => $user?->no_telepon,
                    'nominal' => $request->nominal,
                    'pesan' => $request->pesan,
                    'anonim' => $request->boolean('anonim', false),
                    'status' => 'menunggu',
                ]);

                $snap = $this->midtrans->createSnapTransaction($donasi->load('kampanye', 'user'), $orderId);

                $pembayaran = PembayaranDonasi::create([
                    'donasi_id' => $donasi->id,
                    'order_id' => $orderId,
                    'snap_token' => $snap['token'] ?? null,
                    'redirect_url' => $snap['redirect_url'] ?? null,
                    'status_transaksi' => 'pending',
                    'gross_amount' => $donasi->nominal,
                    'raw_response' => $snap,
                ]);

                return [$donasi, $pembayaran];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 502);
        }

        [$donasi, $pembayaran] = $result;

        return response()->json([
            'status' => 'success',
            'message' => 'Donasi berhasil dibuat. Lanjutkan pembayaran melalui Midtrans.',
            'data' => [
                'donasi' => $donasi->load('kampanye:id,judul'),
                'pembayaran' => $pembayaran,
                'snap_token' => $pembayaran->snap_token,
                'redirect_url' => $pembayaran->redirect_url,
            ],
        ], 201);
    }

    public function riwayat(Request $request): JsonResponse
    {
        $donasi = Donasi::with(['kampanye:id,judul,jenis_bencana,gambar', 'pembayaran'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 10), 30));

        return response()->json($donasi);
    }

    public function riwayatShow(Request $request, string $id): JsonResponse
    {
        $donasi = Donasi::with(['kampanye:id,judul,jenis_bencana,gambar', 'pembayaran'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $donasi,
        ]);
    }

    public function midtransNotification(Request $request): JsonResponse
    {
        $payload = $request->all();

        $log = NotifikasiMidtrans::create([
            'order_id' => $payload['order_id'] ?? '-',
            'transaction_id' => $payload['transaction_id'] ?? null,
            'status_transaksi' => $payload['transaction_status'] ?? 'unknown',
            'metode_bayar' => $payload['payment_type'] ?? null,
            'payload' => $payload,
            'diterima_pada' => now(),
            'status_proses' => 'diterima',
        ]);

        if (!$this->midtrans->isSignatureValid($payload)) {
            $log->update([
                'status_proses' => 'gagal',
                'diproses_pada' => now(),
            ]);

            return response()->json(['message' => 'Signature Midtrans tidak valid.'], 403);
        }

        try {
            DB::transaction(function () use ($payload, $log) {
                $pembayaran = PembayaranDonasi::where('order_id', $payload['order_id'] ?? null)
                    ->lockForUpdate()
                    ->firstOrFail();

                $donasi = Donasi::where('id', $pembayaran->donasi_id)->lockForUpdate()->firstOrFail();
                $kampanye = KampanyeDonasi::where('id', $donasi->kampanye_id)->lockForUpdate()->firstOrFail();

                $statusTransaksi = (string) ($payload['transaction_status'] ?? 'pending');
                $fraudStatus = $payload['fraud_status'] ?? null;
                $statusDonasi = $this->midtrans->mapDonationStatus($statusTransaksi, $fraudStatus);
                $wasSuccessful = $donasi->status === 'berhasil';

                $pembayaran->update([
                    'transaction_id' => $payload['transaction_id'] ?? $pembayaran->transaction_id,
                    'metode_bayar' => $payload['payment_type'] ?? $pembayaran->metode_bayar,
                    'status_transaksi' => $statusTransaksi,
                    'fraud_status' => $fraudStatus,
                    'gross_amount' => $payload['gross_amount'] ?? $pembayaran->gross_amount,
                    'waktu_settlement' => $payload['settlement_time'] ?? $pembayaran->waktu_settlement,
                    'raw_response' => $payload,
                ]);

                $donasi->update([
                    'status' => $statusDonasi,
                    'tanggal_bayar' => $statusDonasi === 'berhasil'
                        ? ($payload['settlement_time'] ?? now())
                        : $donasi->tanggal_bayar,
                ]);

                if (!$wasSuccessful && $statusDonasi === 'berhasil') {
                    $kampanye->increment('total_terkumpul', (float) $donasi->nominal);
                }

                $log->update([
                    'status_proses' => 'diproses',
                    'diproses_pada' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            $log->update([
                'status_proses' => 'gagal',
                'diproses_pada' => now(),
            ]);

            return response()->json([
                'message' => 'Webhook diterima, tetapi gagal diproses.',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['message' => 'Notifikasi Midtrans berhasil diproses.']);
    }
}
