<?php

namespace App\Services;

use App\Models\Donasi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransDonationService
{
    public function createOrderId(): string
    {
        return 'DON-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
    }

    public function createSnapTransaction(Donasi $donasi, string $orderId): array
    {
        $serverKey = config('services.midtrans.server_key');
        $isProduction = (bool) config('services.midtrans.is_production', false);

        if (!$serverKey) {
            return [
                'token' => 'mock-' . $orderId,
                'redirect_url' => config('services.midtrans.mock_redirect_url'),
                'raw_response' => [
                    'message' => 'MIDTRANS_SERVER_KEY belum dikonfigurasi. Gunakan token mock untuk pengujian lokal.',
                ],
            ];
        }

        $baseUrl = $isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $user = $donasi->user;
        $kampanye = $donasi->kampanye;

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) round((float) $donasi->nominal),
            ],
            'item_details' => [
                [
                    'id' => $kampanye->id,
                    'price' => (int) round((float) $donasi->nominal),
                    'quantity' => 1,
                    'name' => Str::limit('Donasi ' . $kampanye->judul, 50, ''),
                ],
            ],
            'customer_details' => [
                'first_name' => $donasi->anonim ? 'Donatur Anonim' : ($donasi->nama_donatur ?: $user?->name),
                'email' => $donasi->email_donatur ?: $user?->email,
                'phone' => $donasi->telepon_donatur ?: $user?->no_telepon,
            ],
            'callbacks' => [
                'finish' => config('services.midtrans.finish_url'),
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
            ->post($baseUrl, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans: ' . $response->body());
        }

        return $response->json();
    }

    public function isSignatureValid(array $payload): bool
    {
        $serverKey = config('services.midtrans.server_key');
        if (!$serverKey || empty($payload['signature_key'])) {
            return true;
        }

        $expected = hash(
            'sha512',
            ($payload['order_id'] ?? '') .
            ($payload['status_code'] ?? '') .
            ($payload['gross_amount'] ?? '') .
            $serverKey
        );

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    public function mapDonationStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        if ($transactionStatus === 'capture') {
            return $fraudStatus === 'challenge' ? 'menunggu' : 'berhasil';
        }

        return match ($transactionStatus) {
            'settlement' => 'berhasil',
            'deny', 'cancel', 'failure' => 'gagal',
            'expire' => 'kedaluwarsa',
            default => 'menunggu',
        };
    }
}
