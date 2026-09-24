<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Padanan KEY_SETTINGS di slip-common.js (halaman pengaturan.blade.php).
 * Didesain single-row: aplikasi hanya membaca/menulis baris id=1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('retention_months')->default(1);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('wa_enabled')->default(true);
            $table->timestamps();
        });

        // Baris pengaturan default (single-row config).
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
