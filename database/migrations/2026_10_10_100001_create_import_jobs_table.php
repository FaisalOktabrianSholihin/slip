<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrian import gaji yang berjalan di latar belakang (lanjut walau pindah menu).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('import_jobs')) {
            return;
        }

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->date('periode');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('berhasil')->default(0);
            $table->string('status', 20)->default('berjalan'); // berjalan | selesai | dibatalkan
            $table->boolean('dilihat')->default(false);
            $table->longText('payload')->nullable();   // baris Excel + konfigurasi komponen
            $table->longText('hasil')->nullable();     // {gagal:[...], data:[...]}
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
