<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sender_name')->default('E-Slip Gaji Mitratani');
            $table->string('sender_email')->nullable();
            $table->json('demography_age_ranges')->nullable();
            $table->json('demography_education_levels')->nullable();
            $table->json('dashboard_kpis')->nullable();
            $table->json('payroll_component_additions')->nullable();
            $table->json('payroll_component_deductions')->nullable();
        });
    }
    public function down(): void {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sender_name','sender_email','demography_age_ranges','demography_education_levels','dashboard_kpis','payroll_component_additions','payroll_component_deductions']);
        });
    }
};
