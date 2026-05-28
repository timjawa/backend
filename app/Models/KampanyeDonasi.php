<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KampanyeDonasi extends Model
{
    use HasUuids;

    protected $table = 'kampanye_donasi';

    protected $fillable = [
        'judul',
        'deskripsi',
        'jenis_bencana',
        'kecamatan_id',
        'laporan_bencana_id',
        'target_donasi',
        'total_terkumpul',
        'total_disalurkan',
        'gambar',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'dibuat_oleh',
    ];

    protected $appends = ['gambar_url', 'sisa_dana', 'progress_persen'];

    protected function casts(): array
    {
        return [
            'target_donasi' => 'decimal:2',
            'total_terkumpul' => 'decimal:2',
            'total_disalurkan' => 'decimal:2',
            'tanggal_mulai' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function laporanBencana(): BelongsTo
    {
        return $this->belongsTo(LaporanBencana::class, 'laporan_bencana_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function donasi(): HasMany
    {
        return $this->hasMany(Donasi::class, 'kampanye_id');
    }

    public function penyaluran(): HasMany
    {
        return $this->hasMany(PenyaluranDonasi::class, 'kampanye_id');
    }

    protected function gambarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->gambar) {
                    return asset('storage/uploads/donasi/default.jpg');
                }

                if (str_starts_with($this->gambar, 'http')) {
                    return $this->gambar;
                }

                return asset('storage/' . ltrim($this->gambar, '/'));
            }
        );
    }

    protected function sisaDana(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, (float) $this->total_terkumpul - (float) $this->total_disalurkan)
        );
    }

    protected function progressPersen(): Attribute
    {
        return Attribute::make(
            get: function () {
                $target = (float) $this->target_donasi;
                if ($target <= 0) {
                    return 0;
                }

                return min(100, round(((float) $this->total_terkumpul / $target) * 100, 2));
            }
        );
    }
}
