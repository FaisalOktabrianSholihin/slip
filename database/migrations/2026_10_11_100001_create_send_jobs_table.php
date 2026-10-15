<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('send_jobs')) {
            return;
        }

        Schema::create('send_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('channel', 10)->default('email');
            $table->string('label', 150)->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('berhasil')->default(0);
            $table->string('status', 20)->default('berjalan'); // berjalan | selesai | dibatalkan
            $table->boolean('dilihat')->default(false);
            $table->longText('payload')->nullable();   // {ids:[payroll id]}
            $table->longText('hasil')->nullable();     // {terkirim:[], gagal:[]}
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('send_jobs');
    }
};
