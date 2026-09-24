<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master komponen "Tambahan" & "Potongan" slip gaji, padanan dari
 * popup "Atur Komponen Slip Gaji" di kirim-slip.js (maks. 20 baris
 * per tipe). Disimpan di tabel terpisah (bukan kolom tambahan1..20)
 * supaya jumlah & nama komponen bisa diubah tanpa migration baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['tambahan', 'potongan']);
            $table->string('nama');
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
