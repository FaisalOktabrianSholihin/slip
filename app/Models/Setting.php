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
        'sender_name',
        'sender_email',
        'demography_age_ranges',
        'demography_education_levels',
        'dashboard_kpis',
        'payroll_component_additions',
        'payroll_component_deductions',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'wa_enabled' => 'boolean',
            'demography_age_ranges' => 'array',
            'demography_education_levels' => 'array',
            'dashboard_kpis' => 'array',
            'payroll_component_additions' => 'array',
            'payroll_component_deductions' => 'array',
        ];
    }

    /** Ambil (atau buat) baris pengaturan tunggal. */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'retention_months' => 1,
            'email_enabled' => true,
            'wa_enabled' => true,
            'sender_name' => 'E-Slip Gaji Mitratani',
            'sender_email' => config('mail.from.address'),
            'demography_age_ranges' => [['label'=>'25–35','min'=>25,'max'=>35],['label'=>'36–45','min'=>36,'max'=>45],['label'=>'>45','min'=>46,'max'=>null]],
            'demography_education_levels' => ['S1','DIII','SMA','SMP'],
            'dashboard_kpis' => ['total','status','masaKerja5'],
        ]);
    }
}
