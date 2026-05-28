<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyaluranDonasi extends Model
{
    use HasUuids;

    protected $table = 'penyaluran_donasi';

    protected $fillable = [
        'kampanye_id',
        'judul',
        'deskripsi',
        'nominal',
        'penerima',
        'tanggal_penyaluran',
        'bukti',
        'status',
        'dibuat_oleh',
    ];

    protected $appends = ['bukti_url'];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal_penyaluran' => 'datetime',
        ];
    }

    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(KampanyeDonasi::class, 'kampanye_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    protected function buktiUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->bukti) {
                    return null;
                }

                if (str_starts_with($this->bukti, 'http')) {
                    return $this->bukti;
                }

                return asset('storage/' . ltrim($this->bukti, '/'));
            }
        );
    }
}
