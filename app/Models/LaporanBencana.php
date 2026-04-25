<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LaporanMedia;
use App\Models\LaporanKomentar;

class LaporanBencana extends Model
{
    use HasUuids;

    protected $table = 'laporan_bencana';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'kecamatan_id',
        'jenis_bencana',
        'deskripsi',
        'alamat_lengkap',
        'latitude',
        'longitude',
        'status',
        'is_draft',
    ];

    protected function casts(): array
    {
        return [
            'is_draft' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'dibuat_pada' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =========================================
    // RELATIONSHIPS
    // =========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(LaporanMedia::class, 'laporan_id');
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(LaporanKomentar::class, 'laporan_id');
    }
}
