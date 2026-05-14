<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPengungsian extends Model
{
    use HasUuids;

    protected $table = 'pos_pengungsian';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'kecamatan_id',
        'alamat',
        'latitude',
        'longitude',
        'kapasitas',
        'terisi',
        'fasilitas',
        'status',
        'penanggung_jawab',
        'telepon',
        'is_active',
    ];

    protected $casts = [
        'fasilitas'  => 'array',
        'is_active'  => 'boolean',
        'latitude'   => 'float',
        'longitude'  => 'float',
        'kapasitas'  => 'integer',
        'terisi'     => 'integer',
        'updated_at' => 'datetime',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
