<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\LaporanBencana;
use App\Models\Notifikasi;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasUuids, Notifiable, HasApiTokens;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'firebase_uid',
        'email',
        'alamat',
        'no_telepon',
        'foto',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Override getAuthPassword to fetch password from user_auth table.
     * Laravel's Auth::attempt() calls this method internally.
     */
    public function getAuthPassword(): ?string
    {
        $auth = $this->userAuth()->where('provider', 'local')->first();
        return $auth?->password;
    }

    // =========================================
    // RELATIONSHIPS
    // =========================================

    public function userAuth(): HasMany
    {
        return $this->hasMany(UserAuth::class, 'user_id');
    }

    public function localAuth(): HasOne
    {
        return $this->hasOne(UserAuth::class, 'user_id')->where('provider', 'local');
    }

    public function berita(): HasMany
    {
        return $this->hasMany(Berita::class, 'dibuat_oleh');
    }

    public function laporanBencana(): HasMany
    {
        return $this->hasMany(LaporanBencana::class, 'user_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    // =========================================
    // HELPERS
    // =========================================

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin_bpbd', 'admin_bmkg', 'super_admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
