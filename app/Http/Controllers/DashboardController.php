<?php

namespace App\Http\Controllers;

use App\Models\MasterData\Karyawan;
use Carbon\Carbon;

/**
 * DashboardController
 *
 * Menghitung statistik nyata dari database db_indukk untuk grafik di
 * dashboard.blade.php (dashboard.js), menggantikan objek `data` yang
 * sebelumnya di-hardcode di file tersebut. Bentuk response dibuat
 * identik dengan struktur `data` lama supaya dashboard.js hanya perlu
 * diubah pada baris pengambilan data, bukan logika chart-nya.
 */
class DashboardController extends Controller
{
    public function summary()
    {
        $karyawan = Karyawan::with('pendidikan')->get();

        // ---- Pendidikan (SD / SLTP / SLTA / PT) ----
        $pendidikanCount = ['SD' => 0, 'SLTP' => 0, 'SLTA' => 0, 'PT' => ['DIII', 'S1', 'S2', 'S3']];
        $sd = $sltp = $slta = $pt = 0;
        foreach ($karyawan as $k) {
            $p = $k->pendidikan?->pendidikan_terakhir;
            if ($p === 'SD') $sd++;
            elseif ($p === 'SLTP') $sltp++;
            elseif ($p === 'SLTA') $slta++;
            elseif (in_array($p, ['DIII', 'S1', 'S2', 'S3'], true)) $pt++;
        }

        // ---- Usia (25-35 / 36-45 / >45) ----
        $usia1 = $usia2 = $usia3 = 0;
        foreach ($karyawan as $k) {
            if (! $k->tanggal_lahir) continue;
            $umur = Carbon::parse($k->tanggal_lahir)->age;
            if ($umur <= 35) $usia1++;
            elseif ($umur <= 45) $usia2++;
            else $usia3++;
        }

        // ---- Status kepegawaian (PKWT vs lainnya / "HL") ----
        $pkwt = $karyawan->where('status_pegawai', 'pkwt')->count();
        $hl = $karyawan->count() - $pkwt;

        // ---- Matriks: baris status pegawai, kolom [25-35,36-45,>45,S1,DIII,SMA,SMP] ----
        $rows = [
            'tetap' => 'Karyawan Tetap',
            'penugasan' => 'Karyawan Penugasan',
            'pkwt' => 'PKWT',
            'honorer' => 'Honorer',
        ];

        $matriks = [];
        foreach ($rows as $key => $label) {
            $subset = $karyawan->where('status_pegawai', $key);
            $u1 = $u2 = $u3 = 0;
            foreach ($subset as $k) {
                if (! $k->tanggal_lahir) continue;
                $umur = Carbon::parse($k->tanggal_lahir)->age;
                if ($umur <= 35) $u1++;
                elseif ($umur <= 45) $u2++;
                else $u3++;
            }
            $s1 = $subset->filter(fn ($k) => $k->pendidikan?->pendidikan_terakhir === 'S1')->count();
            $d3 = $subset->filter(fn ($k) => $k->pendidikan?->pendidikan_terakhir === 'DIII')->count();
            $sma = $subset->filter(fn ($k) => $k->pendidikan?->pendidikan_terakhir === 'SLTA')->count();
            $smp = $subset->filter(fn ($k) => $k->pendidikan?->pendidikan_terakhir === 'SLTP')->count();

            $matriks[] = ['nama' => $label, 'nilai' => [$u1, $u2, $u3, $s1, $d3, $sma, $smp]];
        }

        return response()->json([
            'pendidikan' => ['labels' => ['SD', 'SLTP', 'SLTA', 'PT'], 'jumlah' => [$sd, $sltp, $slta, $pt]],
            'usia' => ['labels' => ['25–35', '36–45', '>45'], 'jumlah' => [$usia1, $usia2, $usia3]],
            'status' => ['pkwt' => $pkwt, 'hl' => $hl],
            'matriks' => $matriks,
            'totalKaryawan' => $karyawan->count(),
            'totalAktif' => $karyawan->where('status_aktif', true)->count(),
        ]);
    }
}
