<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan tabel settings (pengirim email, demografi, KPI dashboard).
 *
 * Idempoten: tiap kolom hanya ditambahkan bila belum ada. Versi sebelumnya
 * berhenti di tengah dengan "Duplicate column name 'sender_name'" sehingga
 * kolom sesudahnya (demography_*, dashboard_kpis, dst) tidak pernah dibuat
 * dan menu Demografi/Pengaturan gagal menyimpan.
 */
return new class extends Migration {
    public function up(): void
    {
        $kolom = [
            'sender_name' => fn(Blueprint $t) => $t->string('sender_name')->default('E-Slip Gaji Mitratani'),
            'sender_email' => fn(Blueprint $t) => $t->string('sender_email')->nullable(),
            'demography_age_ranges' => fn(Blueprint $t) => $t->json('demography_age_ranges')->nullable(),
            'demography_education_levels' => fn(Blueprint $t) => $t->json('demography_education_levels')->nullable(),
            'dashboard_kpis' => fn(Blueprint $t) => $t->json('dashboard_kpis')->nullable(),
            'payroll_component_additions' => fn(Blueprint $t) => $t->json('payroll_component_additions')->nullable(),
            'payroll_component_deductions' => fn(Blueprint $t) => $t->json('payroll_component_deductions')->nullable(),
        ];

        foreach ($kolom as $nama => $tambah) {
            if (! Schema::hasColumn('settings', $nama)) {
                Schema::table('settings', function (Blueprint $table) use ($tambah) {
                    $tambah($table);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['sender_name', 'sender_email', 'demography_age_ranges', 'demography_education_levels', 'dashboard_kpis', 'payroll_component_additions', 'payroll_component_deductions'] as $n) {
            if (Schema::hasColumn('settings', $n)) {
                Schema::table('settings', fn(Blueprint $t) => $t->dropColumn($n));
            }
        }
    }
};
