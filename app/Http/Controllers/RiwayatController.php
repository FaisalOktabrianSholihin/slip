<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\SlipHistory;

/**
 * RiwayatController
 *
 * Menyambungkan public/js/detail-riwayat.js (lewat SlipStore.getHistory())
 * ke tabel slip_histories (gajii). Bentuk baris disamakan dengan objek
 * riwayat lama: {id, nama, email, wa, divisi, file, status, tanggal,
 * jam, channel, nik}.
 */
class RiwayatController extends Controller
{
    public function index()
    {
        $riwayat = SlipHistory::latest('id')->limit(1000)->get();

        // Status payroll saat ini + riwayat terbaru per payroll, untuk tombol "Kirim ulang".
        $statusPayroll = Payroll::whereIn('id', $riwayat->pluck('payroll_id')->filter()->unique())
            ->pluck('status', 'id');
        $terbaruPerPayroll = $riwayat->whereNotNull('payroll_id')->groupBy('payroll_id')
            ->map(fn($g) => $g->first()->id);

        return $riwayat->map(function (SlipHistory $h) use ($statusPayroll, $terbaruPerPayroll) {
            $gagal = $h->status === 'Gagal';
            $payrollStatus = $h->payroll_id ? ($statusPayroll[$h->payroll_id] ?? null) : null;

            return [
                'id' => (string) $h->id,
                'nik' => $h->karyawan_nik,
                'nama' => $h->nama,
                'email' => $h->email,
                'wa' => $h->wa,
                'divisi' => $h->divisi,
                'file' => $h->file,
                'status' => $h->status,
                'channel' => $h->channel,
                'keterangan' => $h->keterangan,
                // PDF yang sama persis dengan lampiran email (SlipController@pdf).
                'payrollId' => $h->payroll_id,
                'pdfUrl' => $h->payroll_id ? route('api.slip.pdf', $h->payroll_id, false) : null,
                // Kirim ulang hanya untuk baris gagal TERBARU milik payroll yang masih ada dan belum terkirim.
                'bisaKirimUlang' => $gagal
                    && $h->channel === 'email'
                    && $payrollStatus !== null
                    && $payrollStatus !== 'terkirim'
                    && ($terbaruPerPayroll[$h->payroll_id] ?? null) === $h->id,
                'tanggal' => $h->created_at->format('d-m-Y'),
                'jam' => $h->created_at->format('H:i'),
            ];
        })->values();
    }
}
