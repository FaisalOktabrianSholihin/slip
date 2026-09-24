<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: Setting
 * Tabel: settings (database default: gajii)
 * Konfigurasi aplikasi single-row (id=1), padanan KEY_SETTINGS.
 */
class Setting extends Model
{
    protected $fillable = [
        'retention_months',
        'email_enabled',
        'wa_enabled',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'wa_enabled' => 'boolean',
        ];
    }

    /** Ambil (atau buat) baris pengaturan tunggal. */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'retention_months' => 1,
            'email_enabled' => true,
            'wa_enabled' => true,
        ]);
    }
}
