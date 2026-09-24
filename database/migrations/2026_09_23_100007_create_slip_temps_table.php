<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Padanan tabel slip_temps: penyimpanan sementara file slip
 * (PDF/PNG/JPG) yang diunggah manual sebelum di-generate dari
 * Excel, dengan nama file = NIK karyawan (lihat SlipController@upload
 * di slip-common.js).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slip_temps', function (Blueprint $table) {
            $table->id();
            $table->string('karyawan_nik', 8)->unique();
            $table->string('file');
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slip_temps');
    }
};
