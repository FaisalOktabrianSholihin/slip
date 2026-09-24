<?php

namespace App\Http\Controllers;

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
        return SlipHistory::latest()->limit(1000)->get()->map(fn(SlipHistory $h) => [
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
            'tanggal' => $h->created_at->format('d-m-Y'),
            'jam' => $h->created_at->format('H:i'),
        ]);
    }
}
