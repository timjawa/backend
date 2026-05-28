<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotifikasiMidtrans extends Model
{
    use HasUuids;

    protected $table = 'notifikasi_midtrans';

    protected $fillable = [
        'order_id',
        'transaction_id',
        'status_transaksi',
        'metode_bayar',
        'payload',
        'diterima_pada',
        'diproses_pada',
        'status_proses',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'diterima_pada' => 'datetime',
            'diproses_pada' => 'datetime',
        ];
    }
}
