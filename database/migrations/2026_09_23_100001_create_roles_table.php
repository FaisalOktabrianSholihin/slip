<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel roles, dipakai oleh halaman role-management.blade.php.
 * Permission disimpan sebagai JSON (daftar kunci permission, mis.
 * ["karyawan.view","karyawan.edit","slip.kirim", ...]) supaya jumlah
 * hak akses bisa fleksibel tanpa perlu tabel pivot terpisah.
 *
 * Berjalan di koneksi DEFAULT (mysql -> database `gajii`), karena
 * ini adalah database transaksional aplikasi, bukan data master.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->string('deskripsi')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
