<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Berita extends Model
{
    use HasUuids;

    protected $table = 'berita';

    // Custom timestamp columns
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'dibuat_oleh',
        'judul',
        'slug',
        'konten',
        'ringkasan',
        'foto_cover',
        'kategori',
        'sumber',
        'status',
        'dipublikasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer',
            'dibuat_pada' => 'datetime',
            'dipublikasi_pada' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Auto-generate slug from judul when creating
     */
    protected static function booted(): void
    {
        static::creating(function (Berita $berita) {
            if (empty($berita->slug)) {
                $berita->slug = Str::slug($berita->judul) . '-' . Str::random(6);
            }
        });
    }

    // =========================================
    // RELATIONSHIPS
    // =========================================

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(BeritaTag::class, 'berita_id');
    }

    // =========================================
    // SCOPES
    // =========================================

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }
}
