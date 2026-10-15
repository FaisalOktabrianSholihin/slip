<?php

namespace App\Console\Commands;

use App\Support\Retensi;
use Illuminate\Console\Command;

class TerapkanRetensi extends Command
{
    protected $signature = 'slip:retensi';

    protected $description = 'Hapus riwayat & payroll slip yang melewati periode retensi di menu Pengaturan';

    public function handle(): int
    {
        $r = Retensi::jalankan(null, 'terjadwal');
        $this->info("Retensi {$r['bulan']} bulan: {$r['riwayat']} riwayat, {$r['payroll']} payroll dihapus.");

        return self::SUCCESS;
    }
}
