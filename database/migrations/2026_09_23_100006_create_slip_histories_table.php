<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pengiriman slip gaji, padanan tabel slip_histories yang
 * disebut di komentar slip-common.js / halaman detail-riwayat.blade.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slip_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->nullable()
                ->constrained('payrolls')->nullOnDelete();

            $table->string('karyawan_nik', 8)->index();
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('wa')->nullable();
            $table->string('divisi')->nullable();
            $table->enum('channel', ['email', 'wa']);
            $table->string('file')->nullable();
            $table->enum('status', ['Berhasil', 'Gagal']);
            $table->text('keterangan')->nullable(); // alasan gagal, dsb.

            $table->foreignId('dikirim_oleh')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slip_histories');
    }
};
