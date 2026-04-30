<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuacaRealtime extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cuaca_realtime';
    public $timestamps = false;

    protected $fillable = [
        'kecamatan_id',
        'suhu',
        'feels_like',
        'kelembapan',
        'curah_hujan',
        'cloud_cover',
        'kecepatan_angin',
        'arah_angin',
        'weather_code',
        'deskripsi',
        'uv_index',
        'visibilitas',
        'tekanan_udara',
        'fetched_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
