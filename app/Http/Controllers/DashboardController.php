<?php

namespace App\Http\Controllers;

use App\Models\MasterData\Karyawan;
use App\Models\Setting;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary()
    {
        $setting = Setting::current();
        $ageRanges = $setting->demography_age_ranges ?: [
            ['label' => '25–35', 'min' => 25, 'max' => 35],
            ['label' => '36–45', 'min' => 36, 'max' => 45],
            ['label' => '>45', 'min' => 46, 'max' => null],
        ];
        $educationLevels = $setting->demography_education_levels ?: ['S1', 'DIII', 'SMA', 'SMP'];
        $educationMap = ['SMA' => 'SLTA', 'SMK' => 'SLTA', 'SMP' => 'SLTP', 'S1' => 'S1', 'DIII' => 'DIII', 'D3' => 'DIII', 'S2' => 'S2', 'S3' => 'S3', 'SD' => 'SD', 'SLTA' => 'SLTA', 'SLTP' => 'SLTP'];
        $karyawan = Karyawan::with('pendidikan')->where('status_aktif', true)->get()->each(function (Karyawan $k) {
            $k->status_baku = Karyawan::normalisasiStatus($k->status_pegawai);
            $k->pendidikan_baku = Karyawan::normalisasiPendidikan($k->pendidikan?->pendidikan_terakhir);
            $k->umur = $k->tanggal_lahir ? Carbon::parse($k->tanggal_lahir)->age : null;
        });
        $statusDefs = [
            'pkwtt' => 'PKWTT',
            'pkwt' => 'PKWT',
            'pkwt_hl' => 'PKWT-HL',
            'pkwt_os' => 'PKWT-OS',
            'penugasan' => 'Karyawan Penugasan',
        ];
        $statusCounts = [];
        foreach ($statusDefs as $key => $label) $statusCounts[] = ['key' => $key, 'label' => $label, 'jumlah' => $karyawan->where('status_baku', $key)->count()];
        $ageCounts = [];
        foreach (array_slice($ageRanges, 0, 5) as $range) {
            $count = $karyawan->filter(fn($k) => $k->umur !== null && $k->umur >= (int)$range['min'] && (($range['max'] ?? null) === null || $k->umur <= (int)$range['max']))->count();
            $ageCounts[] = ['label' => $range['label'], 'jumlah' => $count];
        }
        $eduCounts = [];
        foreach (array_slice($educationLevels, 0, 5) as $level) {
            $normal = $educationMap[strtoupper($level)] ?? Karyawan::normalisasiPendidikan($level) ?? strtoupper($level);
            $count = $karyawan->filter(function ($k) use ($normal, $level) {
                if (in_array(strtoupper($level), ['SMA', 'SMK'])) return $k->pendidikan_baku === 'SLTA';
                if (strtoupper($level) === 'SMP') return $k->pendidikan_baku === 'SLTP';
                return $k->pendidikan_baku === $normal;
            })->count();
            $eduCounts[] = ['label' => $level, 'jumlah' => $count];
        }
        $batas = now()->subYears(5);
        $masaKerja5 = $karyawan->filter(fn(Karyawan $k) => $k->tanggal_masuk ? $k->tanggal_masuk->lt($batas) : (int)$k->masa_kerja > 5)->count();
        $matrix = [];
        foreach ($statusDefs as $key => $label) {
            $subset = $karyawan->where('status_baku', $key);
            $vals = [];
            foreach (array_slice($ageRanges, 0, 5) as $range) $vals[] = $subset->filter(fn($k) => $k->umur !== null && $k->umur >= (int)$range['min'] && (($range['max'] ?? null) === null || $k->umur <= (int)$range['max']))->count();
            foreach (array_slice($eduCounts, 0, 5) as $edu) {
                $raw = strtoupper($edu['label']);
                $norm = $educationMap[$raw] ?? Karyawan::normalisasiPendidikan($raw) ?? $raw;
                $vals[] = $subset->filter(function ($k) use ($raw, $norm) {
                    if (in_array($raw, ['SMA', 'SMK'])) return $k->pendidikan_baku === 'SLTA';
                    if ($raw === 'SMP') return $k->pendidikan_baku === 'SLTP';
                    return $k->pendidikan_baku === $norm;
                })->count();
            }
            $matrix[] = ['nama' => $label, 'nilai' => $vals];
        }
        return response()->json([
            'pendidikan' => ['labels' => array_column($eduCounts, 'label'), 'jumlah' => array_column($eduCounts, 'jumlah')],
            'usia' => ['labels' => array_column($ageCounts, 'label'), 'jumlah' => array_column($ageCounts, 'jumlah')],
            'status' => ['labels' => array_column($statusCounts, 'label'), 'jumlah' => array_column($statusCounts, 'jumlah')],
            'matriks' => $matrix,
            'matrixHeaders' => array_merge(array_column($ageCounts, 'label'), array_column($eduCounts, 'label')),
            'kpi' => ['total' => $karyawan->count(), 'status' => $statusCounts, 'masaKerja5' => $masaKerja5, 'dashboardKpis' => $setting->dashboard_kpis ?? ['total', 'status', 'masaKerja5']],
            'totalKaryawan' => $karyawan->count(),
            'totalAktif' => $karyawan->count(),
        ]);
    }
}
