<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan role_id & status_aktif pada tabel users bawaan Laravel,
 * dipakai oleh halaman data-user.blade.php (Superadmin / Admin, dst).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')
                ->constrained('roles')->nullOnDelete();
            $table->boolean('status_aktif')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('status_aktif');
        });
    }
};
