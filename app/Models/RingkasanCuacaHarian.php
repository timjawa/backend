<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RingkasanCuacaHarian extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ringkasan_cuaca_harian';
    public $timestamps = false;

    protected $fillable = [
        'kecamatan_id',
        'tanggal',
        'suhu_rata',
        'curah_hujan',
        'created_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
