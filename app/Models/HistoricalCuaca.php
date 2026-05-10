<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalCuaca extends Model
{
    use HasUuids;

    protected $table = 'historical_cuaca';
    
    // Only created_at is managed, no updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'kecamatan_id',
        'waktu',
        'suhu',
        'kelembapan',
        'curah_hujan',
        'cloud_cover',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'suhu' => 'integer',
            'kelembapan' => 'integer',
            'curah_hujan' => 'decimal:2',
            'cloud_cover' => 'integer',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
