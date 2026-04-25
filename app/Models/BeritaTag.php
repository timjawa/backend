<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaTag extends Model
{
    use HasUuids;

    protected $table = 'berita_tags';

    public $timestamps = false;

    protected $fillable = [
        'berita_id',
        'tag',
    ];

    public function berita(): BelongsTo
    {
        return $this->belongsTo(Berita::class, 'berita_id');
    }
}
