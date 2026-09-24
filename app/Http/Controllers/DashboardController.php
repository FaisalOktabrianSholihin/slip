<?php

namespace App\Http\Controllers;

use App\Models\MasterData\Karyawan;
use Carbon\Carbon;

/**
 * DashboardController
 *
 * Menghitung statistik nyata dari database db_indukk untuk dashboard.blade.php
 * (dashboard.js). Yang dihitung hanya karyawan berstatus AKTIF.
 *
 * Status pegawai dan pendidikan terakhir dinormalkan lebih dulu
 * (Karyawan::normalisasiStatus / normalisasiPendidikan), karena data hasil
 * import Excel bisa berbunyi "Kontrak", "Tetap", "D3", "SMA", dan seterusnya,
 * sedangkan grafik memakai kode baku tetap/pkwt/honorer/penugasan dan
 * SD/SLTP/SLTA/DIII/S1/S2/S3.
 */
class DashboardController extends Controller
{
    public function summary()
    {
        $karyawan = Karyawan::with('pendidikan')
            ->where('status_aktif', true)
            ->get()
            ->each(function (Karyawan $k) {
                $k->status_baku = Karyawan::normalisasiStatus($k->status_pegawai);
                $k->pendidikan_baku = Karyawan::normalisasiPendidikan($k->pendidikan?->pendidikan_terakhir);
                $k->umur = $k->tanggal_lahir ? Carbon::parse($k->tanggal_lahir)->age : null;
            });

        $kelompokUsia = function ($umur): ?int {
            if ($umur === null) return null;
            if ($umur <= 35) return 0;
            if ($umur <= 45) return 1;
            return 2;
        };

        // ---- Pendidikan (SD / SLTP / SLTA / PT) ----
        $sd = $karyawan->where('pendidikan_baku', 'SD')->count();
        $sltp = $karyawan->where('pendidikan_baku', 'SLTP')->count();
        $slta = $karyawan->where('pendidikan_baku', 'SLTA')->count();
        $pt = $karyawan->whereIn('pendidikan_baku', ['DIII', 'S1', 'S2', 'S3'])->count();

        // ---- Usia (25-35 / 36-45 / >45) ----
        $usia = [0, 0, 0];
        foreach ($karyawan as $k) {
            $g = $kelompokUsia($k->umur);
            if ($g !== null) $usia[$g]++;
        }

        // ---- Status kepegawaian untuk donut & kotak KPI ----
        // PKWT = kontrak/PKWT, HL = honorer / harian lepas.
        $pkwt = $karyawan->where('status_baku', 'pkwt')->count();
        $hl = $karyawan->where('status_baku', 'honorer')->count();

        // ---- Masa kerja lebih dari 5 tahun ----
        // Dihitung dari tanggal masuk; kalau kosong, pakai kolom masa_kerja (tahun).
        $batas = now()->subYears(5);
        $masaKerja5 = $karyawan->filter(function (Karyawan $k) use ($batas) {
            if ($k->tanggal_masuk) {
                return $k->tanggal_masuk->lt($batas);
            }
            return (int) $k->masa_kerja > 5;
        })->count();

        // ---- Matriks: baris status pegawai, kolom [25-35,36-45,>45,S1,DIII,SMA,SMP] ----
        $rows = [
            'tetap' => 'Karyawan Tetap',
            'penugasan' => 'Karyawan Penugasan',
            'pkwt' => 'PKWT',
            'honorer' => 'Honorer',
        ];

        $matriks = [];
        foreach ($rows as $key => $label) {
            $subset = $karyawan->where('status_baku', $key);
            $u = [0, 0, 0];
            foreach ($subset as $k) {
                $g = $kelompokUsia($k->umur);
                if ($g !== null) $u[$g]++;
            }

            $matriks[] = [
                'nama' => $label,
                'nilai' => [
                    $u[0],
                    $u[1],
                    $u[2],
                    $subset->where('pendidikan_baku', 'S1')->count(),
                    $subset->where('pendidikan_baku', 'DIII')->count(),
                    $subset->where('pendidikan_baku', 'SLTA')->count(),
                    $subset->where('pendidikan_baku', 'SLTP')->count(),
                ],
            ];
        }

        return response()->json([
            'pendidikan' => ['labels' => ['SD', 'SLTP', 'SLTA', 'PT'], 'jumlah' => [$sd, $sltp, $slta, $pt]],
            'usia' => ['labels' => ['25–35', '36–45', '>45'], 'jumlah' => $usia],
            'status' => ['pkwt' => $pkwt, 'hl' => $hl],
            'matriks' => $matriks,
            'kpi' => [
                'total' => $karyawan->count(),
                'pkwt' => $pkwt,
                'hl' => $hl,
                'masaKerja5' => $masaKerja5,
            ],
            'totalKaryawan' => $karyawan->count(),
            'totalAktif' => $karyawan->count(),
        ]);
    }
}
