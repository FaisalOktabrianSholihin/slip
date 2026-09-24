<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel payrolls: hasil import Excel gaji (padanan KEY_PAYROLL /
 * eslip_payroll_imports di slip-common.js) sebelum & sesudah dikirim.
 *
 * karyawan_nik TIDAK diberi foreign key fisik karena tabel karyawans
 * berada di database lain (db_indukk / koneksi db_induk) - lintas
 * database berbeda skema tidak didukung foreign key MySQL. Relasi
 * dilakukan di level Eloquent (lihat App\Models\Payroll::karyawan()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            // Referensi manual ke karyawans.nik di database db_indukk
            $table->string('karyawan_nik', 8)->index();

            // Snapshot data master pada saat import, supaya riwayat tetap
            // konsisten walau data karyawan berubah di kemudian hari.
            $table->string('nama_snapshot');
            $table->string('divisi_snapshot')->nullable();
            $table->string('jabatan_snapshot')->nullable();
            $table->string('golongan_snapshot')->nullable();
            $table->string('rekening_snapshot')->nullable();

            $table->date('periode'); // tanggal periode gaji (biasanya awal bulan)
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('total_tambahan', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('gaji_bersih', 15, 2)->default(0);

            $table->enum('status', ['draft', 'siap_kirim', 'terkirim', 'gagal'])->default('draft');
            $table->enum('channel', ['email', 'wa'])->nullable();
            $table->string('file_slip')->nullable();
            $table->timestamp('dikirim_pada')->nullable();

            $table->foreignId('dibuat_oleh')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['karyawan_nik', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
