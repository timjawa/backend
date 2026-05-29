<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Donasi extends Model
{
    use HasUuids;

    protected $table = 'donasi';

    protected $fillable = [
        'kampanye_id',
        'user_id',
        'nama_donatur',
        'email_donatur',
        'telepon_donatur',
        'nominal',
        'pesan',
        'anonim',
        'status',
        'tanggal_bayar',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'anonim' => 'boolean',
            'tanggal_bayar' => 'datetime',
        ];
    }

    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(KampanyeDonasi::class, 'kampanye_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(PembayaranDonasi::class, 'donasi_id');
    }
}
