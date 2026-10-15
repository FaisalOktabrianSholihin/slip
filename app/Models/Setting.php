<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Pastikan semua kolom pengaturan ada. Bila migrasi pernah macet di tengah
     * (mis. "Duplicate column name"), kolom yang hilang dibuat di sini supaya
     * menu Demografi & Pengaturan tetap bisa menyimpan data.
     */
    public static function pastikanKolom(): void
    {
        static $sudah = false;
        if ($sudah) {
            return;
        }
        $sudah = true;

        $kolom = [
            'sender_name' => fn(Blueprint $t) => $t->string('sender_name')->default('E-Slip Gaji Mitratani'),
            'sender_email' => fn(Blueprint $t) => $t->string('sender_email')->nullable(),
            'demography_age_ranges' => fn(Blueprint $t) => $t->json('demography_age_ranges')->nullable(),
            'demography_education_levels' => fn(Blueprint $t) => $t->json('demography_education_levels')->nullable(),
            'dashboard_kpis' => fn(Blueprint $t) => $t->json('dashboard_kpis')->nullable(),
            'payroll_component_additions' => fn(Blueprint $t) => $t->json('payroll_component_additions')->nullable(),
            'payroll_component_deductions' => fn(Blueprint $t) => $t->json('payroll_component_deductions')->nullable(),
        ];

        try {
            foreach ($kolom as $nama => $tambah) {
                if (! Schema::hasColumn('settings', $nama)) {
                    Schema::table('settings', fn(Blueprint $t) => $tambah($t));
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Ambil (atau buat) baris pengaturan tunggal. */
    public static function current(): self
    {
        static::pastikanKolom();

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
