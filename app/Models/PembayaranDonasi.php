<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranDonasi extends Model
{
    use HasUuids;

    protected $table = 'pembayaran_donasi';

    protected $fillable = [
        'donasi_id',
        'order_id',
        'snap_token',
        'redirect_url',
        'transaction_id',
        'metode_bayar',
        'status_transaksi',
        'fraud_status',
        'gross_amount',
        'waktu_settlement',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'waktu_settlement' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function donasi(): BelongsTo
    {
        return $this->belongsTo(Donasi::class, 'donasi_id');
    }
}
