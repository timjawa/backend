<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanMedia extends Model
{
    use HasUuids;

    protected $table = 'laporan_media';

    public $timestamps = false;

    protected $fillable = [
        'laporan_id',
        'url',
        'tipe',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanBencana::class, 'laporan_id');
    }
}
