<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\ImportJob;
use App\Models\Payroll;
use App\Models\SendJob;
use App\Models\Setting;
use App\Models\SlipHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Retensi data slip: hapus riwayat pengiriman, payroll, dan job lama yang
 * melewati periode "Simpan data slip selama" di menu Pengaturan.
 * Log aktivitas TIDAK dihapus.
 */
class Retensi
{
    /** @return array{bulan:int,riwayat:int,payroll:int,job:int} */
    public static function jalankan(?int $bulan = null, string $oleh = 'manual'): array
    {
        @set_time_limit(0);

        $bulan = max(1, $bulan ?? (int) Setting::current()->retention_months);
        $batas = now()->subMonthsNoOverflow($bulan);

        $riwayat = SlipHistory::where('created_at', '<', $batas)->delete();

        $payroll = 0;
        Payroll::where('updated_at', '<', $batas)->chunkById(100, function ($rows) use (&$payroll) {
            foreach ($rows as $p) {
                $file = $p->file_slip;
                DB::transaction(function () use ($p) {
                    $p->items()->delete();
                    SlipHistory::where('payroll_id', $p->id)->update(['payroll_id' => null]);
                    $p->delete();
                });
                if ($file && Storage::disk('local')->exists('slip/' . $file)) {
                    Storage::disk('local')->delete('slip/' . $file);
                }
                $payroll++;
            }
        });

        $job = ImportJob::where('status', '!=', 'berjalan')->where('created_at', '<', $batas)->delete()
            + SendJob::where('status', '!=', 'berjalan')->where('created_at', '<', $batas)->delete();

        ActivityLog::catat(
            'Terapkan retensi',
            "Pembersihan data lebih dari {$bulan} bulan ({$oleh}): {$riwayat} riwayat, {$payroll} payroll.",
            ['riwayat' => $riwayat, 'payroll' => $payroll, 'job' => $job]
        );

        return ['bulan' => $bulan, 'riwayat' => $riwayat, 'payroll' => $payroll, 'job' => $job];
    }
}
