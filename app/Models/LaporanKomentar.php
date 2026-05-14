<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKomentar extends Model
{
    use HasUuids;

    protected $table = 'laporan_komentar';

    public $timestamps = false;

    protected $fillable = [
        'laporan_id',
        'user_id',
        'isi',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanBencana::class, 'laporan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
