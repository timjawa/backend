<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeringatanDini extends Model
{
    use HasUuids;

    protected $table = 'peringatan_dini';
    
    // As per schema: created_at exists, but updated_at does not
    const UPDATED_AT = null;

    protected $fillable = [
        'kecamatan_id',
        'dibuat_oleh',
        'deskripsi',
        'tingkat_urgensi',
        'berlaku_hingga',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_hingga' => 'datetime',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
