<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerkiraanCuaca extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'perkiraan_cuaca';
    public $timestamps = false;

    protected $fillable = [
        'kecamatan_id',
        'waktu_lokal',
        'suhu',
        'kelembapan',
        'curah_hujan',
        'cloud_cover',
        'weather_code',
        'deskripsi_cuaca',
        'kecepatan_angin',
        'arah_angin',
        'uv_index',
        'visibilitas',
        'dibuat_pada',
    ];

    protected $casts = [
        'waktu_lokal' => 'datetime',
        'dibuat_pada' => 'datetime',
    ];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
