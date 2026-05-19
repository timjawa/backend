<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetaMarker extends Model
{
    use HasUuids;

    protected $table = 'peta_marker';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'latitude',
        'longitude',
        'tipe_marker',
        'path_data',
        'label',
        'kategori',
        'tingkat_bahaya',
        'radius',
        'dibuat_oleh',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude'    => 'float',
            'longitude'   => 'float',
            'radius'      => 'integer',
            'path_data'   => 'array',
            'is_active'   => 'boolean',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
