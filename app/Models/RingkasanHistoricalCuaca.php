<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RingkasanHistoricalCuaca extends Model
{
    use HasUuids;

    protected $table = 'ringkasan_historical_cuaca';

    const UPDATED_AT = null;

    protected $fillable = [
        'kecamatan_id',
        'tanggal',
        'suhu_rata',
        'kelembapan_rata',
        'curah_hujan_rata',
        'cloud_cover_rata',
        'jumlah_data',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'suhu_rata' => 'decimal:1',
            'kelembapan_rata' => 'decimal:2',
            'curah_hujan_rata' => 'decimal:2',
            'cloud_cover_rata' => 'decimal:2',
            'jumlah_data' => 'integer',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
