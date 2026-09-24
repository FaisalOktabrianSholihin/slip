<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'status_aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status_aktif' => 'boolean',
        ];
    }

    /**
     * Role/peran user ini (Superadmin, Admin, dst), dipakai halaman
     * data-user.blade.php & role-management.blade.php.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Payroll yang dibuat/diimport oleh user ini. */
    public function payrollsDibuat(): HasMany
    {
        return $this->hasMany(Payroll::class, 'dibuat_oleh');
    }

    /** Riwayat slip yang dikirim oleh user ini. */
    public function riwayatDikirim(): HasMany
    {
        return $this->hasMany(SlipHistory::class, 'dikirim_oleh');
    }

    public function hasPermission(string $key): bool
    {
        return $this->role?->hasPermission($key) ?? false;
    }
}
