<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian baris Tambahan/Potongan per payroll (padanan kolom
 * tambahan1..20 & potongan1..20 pada hasil import Excel di frontend).
 * nama disimpan sebagai snapshot dari payroll_components pada saat
 * import, supaya slip lama tidak berubah bila konfigurasi diedit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->nullable()
                ->constrained('payroll_components')->nullOnDelete();
            $table->enum('tipe', ['tambahan', 'potongan']);
            $table->string('nama');
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
