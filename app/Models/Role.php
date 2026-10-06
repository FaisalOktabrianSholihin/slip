<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Role
 * Tabel: roles (database default: gajii)
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'slug',
        'deskripsi',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Izin dalam format baku "modul.aksi" (izin lama otomatis dikonversi). */
    public function normalizedPermissions(): array
    {
        return \App\Support\Permissions::normalize($this->permissions ?? []);
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->normalizedPermissions(), true);
    }
}
