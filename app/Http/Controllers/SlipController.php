<?php

namespace App\Http\Controllers;

use App\Mail\SlipGajiMail;
use App\Models\ActivityLog;
use App\Models\MasterData\Karyawan;
use App\Models\Payroll;
use App\Models\SlipHistory;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Alur E-Slip:
 * Excel -> validasi NIK terhadap master -> simpan payroll -> preview ->
 * generate PDF -> kirim email.
 */
class SlipController extends Controller
{
    private const MAX_COMPONENTS = 20;

    public function importPayroll(Request $request)
    {
        $data = $request->validate([
            'periode' => ['required', 'date'],
            'componentConfig' => ['nullable', 'array'],
            'componentConfig.additions' => ['nullable', 'array', 'max:20'],
            'componentConfig.additions.*' => ['required', 'string', 'max:100'],
            'componentConfig.deductions' => ['nullable', 'array', 'max:20'],
            'componentConfig.deductions.*' => ['required', 'string', 'max:150'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.nik' => ['required', 'digits:8'],
            'rows.*.gajiPokok' => ['required', 'numeric', 'min:0'],
            'rows.*.tambahan' => ['nullable', 'array', 'max:20'],
            'rows.*.tambahan.*.nama' => ['required', 'string', 'max:100'],
            'rows.*.tambahan.*.jumlah' => ['required', 'numeric', 'min:0'],
            'rows.*.potongan' => ['nullable', 'array', 'max:20'],
            'rows.*.potongan.*.nama' => ['required', 'string', 'max:150'],
            'rows.*.potongan.*.jumlah' => ['required', 'numeric', 'min:0'],
        ]);

        $periode = $data['periode'];
        $config = [
            'additions' => array_values(array_slice(array_filter($data['componentConfig']['additions'] ?? []), 0, self::MAX_COMPONENTS)),
            'deductions' => array_values(array_slice(array_filter($data['componentConfig']['deductions'] ?? []), 0, self::MAX_COMPONENTS)),
        ];

        $disimpan = [];
        $gagal = [];

        foreach ($data['rows'] as $i => $row) {
            $karyawan = Karyawan::with(['divisi', 'jabatan', 'bank'])
                ->where('nik', $row['nik'])
                ->first();

            if (! $karyawan) {
                $gagal[] = [
                    'baris' => $i + 1,
                    'nik' => $row['nik'],
                    'alasan' => 'NIK tidak ditemukan di data master karyawan',
                ];
                continue;
            }

            if (! trim((string) $karyawan->email) && ! trim((string) $karyawan->no_hp)) {
                $gagal[] = [
                    'baris' => $i + 1,
                    'nik' => $row['nik'],
                    'alasan' => 'Email dan No. HP pada master karyawan kosong',
                ];
                continue;
            }

            try {
                $payroll = DB::transaction(function () use ($row, $karyawan, $periode, $config) {
                    $bank = $karyawan->bank->first();

                    $payroll = Payroll::updateOrCreate(
                        ['karyawan_nik' => $karyawan->nik, 'periode' => $periode],
                        [
                            'nama_snapshot' => $karyawan->nama,
                            'divisi_snapshot' => $karyawan->divisi?->nama_divisi,
                            'jabatan_snapshot' => $karyawan->jabatan?->nama_jabatan,
                            'golongan_snapshot' => $karyawan->golongan,
                            'rekening_snapshot' => $bank?->no_rekening,
                            'gaji_pokok' => $row['gajiPokok'],
                            'status' => 'siap_kirim',
                            'channel' => $karyawan->email ? 'email' : 'wa',
                            'component_config' => $config,
                            'catatan' => null,
                            'dibuat_oleh' => auth()->id(),
                        ]
                    );

                    $payroll->items()->delete();

                    foreach (($row['tambahan'] ?? []) as $idx => $item) {
                        if ((float) ($item['jumlah'] ?? 0) <= 0) {
                            continue;
                        }

                        $payroll->items()->create([
                            'tipe' => 'tambahan',
                            'nama' => $item['nama'],
                            'urutan' => $idx + 1,
                            'jumlah' => $item['jumlah'],
                        ]);
                    }

                    foreach (($row['potongan'] ?? []) as $idx => $item) {
                        if ((float) ($item['jumlah'] ?? 0) <= 0) {
                            continue;
                        }

                        $payroll->items()->create([
                            'tipe' => 'potongan',
                            'nama' => $item['nama'],
                            'urutan' => $idx + 1,
                            'jumlah' => $item['jumlah'],
                        ]);
                    }

                    $payroll->hitungUlangTotal();

                    return $payroll->fresh(['items']);
                });

                // PDF dibuat saat import sehingga sudah tersedia saat preview
                // dan siap menjadi lampiran ketika tombol kirim email ditekan.
                $filename = $this->generatePdf($payroll);

                $payroll->update([
                    'file_slip' => $filename,
                    'status' => 'siap_kirim',
                ]);

                $disimpan[] = [
                    'id' => $payroll->id,
                    'nik' => $payroll->karyawan_nik,
                    'nama' => $payroll->nama_snapshot,
                    'email' => $karyawan->email,
                    'wa' => $karyawan->no_hp,
                    'divisi' => $payroll->divisi_snapshot ?: '-',
                ];
            } catch (\Throwable $e) {
                report($e);
                $gagal[] = [
                    'baris' => $i + 1,
                    'nik' => $row['nik'],
                    'alasan' => 'Data gagal disimpan/generate PDF: '.$e->getMessage(),
                ];
            }
        }

        ActivityLog::catat(
            'Import payroll',
            'Mengimpor '.count($disimpan).' data payroll periode '.$periode.'.',
            ['total' => count($disimpan), 'gagal' => count($gagal)]
        );

        return response()->json([
            'berhasil' => count($disimpan),
            'gagal' => $gagal,
            'data' => $disimpan,
        ]);
    }

    public function preview(Request $request)
    {
        $payrolls = Payroll::with(['karyawan', 'items'])
            ->where('status', 'siap_kirim')
            ->when(
                $request->filled('periode'),
                fn ($q) => $q->where('periode', $request->query('periode'))
            )
            ->latest('id')
            ->get();

        return $payrolls->map(function (Payroll $p) {
            $karyawan = $p->karyawan;
            $tambahan = $p->items->where('tipe', 'tambahan')->sortBy('urutan')->values();
            $potongan = $p->items->where('tipe', 'potongan')->sortBy('urutan')->values();

            $config = $p->component_config ?: [
                'additions' => [
                    'Tunjangan Jabatan', 'Tunjangan Bansos', 'Tunjangan Managerial',
                    'Tambahan 4', 'Tambahan 5', 'Tambahan 6', 'Tambahan 7',
                    'Tambahan 8', 'Tambahan 9', 'Tambahan 10', 'Tambahan 11',
                    'Tambahan 12', 'Tambahan 13', 'Tambahan 14', 'Tambahan 15',
                    'Tambahan 16', 'Tambahan 17', 'Tambahan 18', 'Tambahan 19', 'Tambahan 20',
                ],
                'deductions' => [
                    'BP JAMSOSTEK (JHT: 2%); (JP: 1%)', 'BPJS-Kshtn (JK: 1%)',
                    'BNI (DPLK Simponi)', 'KSU Ke. Mitratani', 'Iuran Anggota SPA',
                    'BTN (KPR)', 'BRI Cab Jember', 'Potongan 8', 'Potongan 9',
                    'Potongan 10', 'Potongan 11', 'Potongan 12', 'Potongan 13',
                    'Potongan 14', 'Potongan 15', 'Potongan 16', 'Potongan 17',
                    'Potongan 18', 'Potongan 19', 'Potongan 20',
                ],
            ];

            $row = [
                'payrollId' => $p->id,
                'nik' => $p->karyawan_nik,
                'nama' => $p->nama_snapshot,
                'email' => $karyawan?->email,
                'wa' => $karyawan?->no_hp,
                'divisi' => $p->divisi_snapshot ?: '-',
                'pangkat' => $p->golongan_snapshot ?: '-',
                'jabatan' => $p->jabatan_snapshot ?: '-',
                'rekening' => $p->rekening_snapshot ?: '-',
                'noUrut' => $karyawan?->no_absen,
                'gajiPokok' => (float) $p->gaji_pokok,
                'date' => optional($p->periode)->format('Y-m-d'),
                'file' => $p->file_slip ?: 'SLIP_'.$p->karyawan_nik.'_'.optional($p->periode)->format('Y-m').'.pdf',
                'pdfUrl' => route('api.slip.pdf', $p),
                'channel' => $p->channel ?: ($karyawan?->email ? 'email' : ($karyawan?->no_hp ? 'wa' : '')),
                'componentConfig' => [
                    'additions' => array_values($config['additions'] ?? []),
                    'deductions' => array_values($config['deductions'] ?? []),
                ],
            ];

            foreach ($tambahan as $i => $item) {
                $row['tambahan'.($i + 1)] = (float) $item->jumlah;
            }

            foreach ($potongan as $i => $item) {
                $row['potongan'.($i + 1)] = (float) $item->jumlah;
            }

            return $row;
        })->values();
    }

    public function pdf(Payroll $payroll)
    {
        $payroll->load(['items', 'karyawan']);

        if (! $payroll->file_slip || ! Storage::disk('local')->exists('slip/'.$payroll->file_slip)) {
            $filename = $this->generatePdf($payroll);
            $payroll->update(['file_slip' => $filename]);
        }

        return response()->file(
            Storage::disk('local')->path('slip/'.$payroll->file_slip),
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function generatePdf(Payroll $payroll): string
    {
        $payroll->load(['items', 'karyawan']);

        $filename = 'SLIP_'.$payroll->karyawan_nik.'_'.optional($payroll->periode)->format('Ym').'.pdf';

        $pdf = Pdf::loadView('pdf.slip-gaji', [
            'payroll' => $payroll,
            'karyawan' => $payroll->karyawan,
        ])->setPaper('a4', 'portrait');

        Storage::disk('local')->makeDirectory('slip');
        Storage::disk('local')->put('slip/'.$filename, $pdf->output());

        return $filename;
    }

    protected function kirimSatuSlip(Payroll $payroll): bool
    {
        $settings = Setting::current();
        $karyawan = $payroll->karyawan;

        if (! $karyawan) {
            return $this->catatHasilKirim($payroll, null, false, 'Data master karyawan tidak ditemukan');
        }

        $channel = $karyawan->email ? 'email' : ($karyawan->no_hp ? 'wa' : null);

        if ($channel === 'email' && ! $settings->email_enabled) {
            return $this->catatHasilKirim($payroll, $karyawan, false, 'Pengiriman Email dinonaktifkan di Pengaturan');
        }

        if ($channel === 'wa' && ! $settings->wa_enabled) {
            return $this->catatHasilKirim($payroll, $karyawan, false, 'Pengiriman WhatsApp dinonaktifkan di Pengaturan');
        }

        if (! $channel) {
            return $this->catatHasilKirim($payroll, $karyawan, false, 'Email dan WhatsApp karyawan kosong');
        }

        if ($channel === 'wa') {
            return $this->catatHasilKirim($payroll, $karyawan, false, 'Pengiriman WhatsApp belum diimplementasikan');
        }

        try {
            $pathFileSlip = $this->ensurePdf($payroll);

            Mail::to($karyawan->email)->send(
                new SlipGajiMail($payroll->fresh(['items']), $karyawan->nama, $pathFileSlip)
            );

            $payroll->update([
                'status' => 'terkirim',
                'channel' => 'email',
                'dikirim_pada' => now(),
                'dibuat_oleh' => auth()->id(),
            ]);

            return $this->catatHasilKirim($payroll, $karyawan, true, null, 'email');
        } catch (\Throwable $e) {
            report($e);
            $payroll->update([
                'status' => 'gagal',
                'catatan' => $e->getMessage(),
            ]);

            return $this->catatHasilKirim($payroll, $karyawan, false, $e->getMessage(), 'email');
        }
    }

    protected function ensurePdf(Payroll $payroll): string
    {
        if ($payroll->file_slip && Storage::disk('local')->exists('slip/'.$payroll->file_slip)) {
            return Storage::disk('local')->path('slip/'.$payroll->file_slip);
        }

        $filename = $this->generatePdf($payroll->fresh(['items']));
        $payroll->update(['file_slip' => $filename]);

        return Storage::disk('local')->path('slip/'.$filename);
    }

    protected function catatHasilKirim(
        Payroll $payroll,
        ?Karyawan $karyawan,
        bool $ok,
        ?string $alasan,
        string $channel = 'email'
    ): bool {
        SlipHistory::create([
            'payroll_id' => $payroll->id,
            'karyawan_nik' => $payroll->karyawan_nik,
            'nama' => $payroll->nama_snapshot,
            'email' => $karyawan?->email,
            'wa' => $karyawan?->no_hp,
            'divisi' => $payroll->divisi_snapshot,
            'channel' => $channel,
            'file' => $payroll->file_slip,
            'status' => $ok ? 'Berhasil' : 'Gagal',
            'keterangan' => $alasan,
            'dikirim_oleh' => auth()->id(),
        ]);

        return $ok;
    }

    public function storeDivisi(Request $request, string $divisi)
    {
        $payrolls = Payroll::with('karyawan')
            ->where('status', 'siap_kirim')
            ->where('divisi_snapshot', $divisi)
            ->get()
            ->filter(fn (Payroll $p) =>
                ! $request->filled('channel') ||
                $this->resolveChannel($p) === $request->query('channel')
            );

        return response()->json($this->kirimBanyak($payrolls, "Divisi {$divisi}"));
    }

    public function storeAll(Request $request)
    {
        $payrolls = Payroll::with('karyawan')
            ->where('status', 'siap_kirim')
            ->get()
            ->filter(fn (Payroll $p) =>
                ! $request->filled('channel') ||
                $this->resolveChannel($p) === $request->query('channel')
            );

        return response()->json($this->kirimBanyak($payrolls, 'Kirim semua'));
    }

    protected function resolveChannel(Payroll $payroll): string
    {
        $karyawan = $payroll->karyawan;

        return $karyawan?->email ? 'email' : ($karyawan?->no_hp ? 'wa' : '');
    }

    protected function kirimBanyak($payrolls, string $labelLog): array
    {
        $terkirim = [];
        $gagal = [];

        foreach ($payrolls as $payroll) {
            $karyawan = $payroll->karyawan;
            $ok = $this->kirimSatuSlip($payroll);

            $snapshot = [
                'nik' => $payroll->karyawan_nik,
                'nama' => $payroll->nama_snapshot,
                'email' => $karyawan?->email,
                'wa' => $karyawan?->no_hp,
            ];

            if ($ok) {
                $terkirim[] = $snapshot;
            } else {
                $gagal[] = [
                    'row' => $snapshot,
                    'reason' => $payroll->fresh()->catatan ?: 'Gagal dikirim, lihat Riwayat untuk detail.',
                ];
            }
        }

        ActivityLog::catat(
            'Pengiriman slip',
            "{$labelLog}: ".count($terkirim).' berhasil, '.count($gagal).' gagal.',
            ['berhasil' => count($terkirim), 'gagal' => count($gagal)]
        );

        return ['terkirim' => $terkirim, 'gagal' => $gagal];
    }

    public function kirimSatu(Request $request, Payroll $payroll)
    {
        $ok = $this->kirimSatuSlip($payroll);

        return response()->json([
            'ok' => $ok,
            'status' => $payroll->fresh()->status,
        ]);
    }
}
