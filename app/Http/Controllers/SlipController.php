<?php

namespace App\Http\Controllers;

use App\Mail\SlipGajiMail;
use App\Models\ActivityLog;
use App\Models\MasterData\Karyawan;
use App\Models\ImportJob;
use App\Models\SendJob;
use App\Support\MailEnv;
use App\Models\Payroll;
use App\Models\SlipHistory;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        // Pembuatan PDF per karyawan cukup lambat. Tanpa ini PHP berhenti di
        // max_execution_time (default 30 detik) dan hanya ~19 data yang masuk.
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        ignore_user_abort(true);

        $data = $request->validate([
            'tanpaLog' => ['nullable', 'boolean'],
            'periode' => ['required', 'date'],
            'componentConfig' => ['nullable', 'array'],
            'componentConfig.additions' => ['nullable', 'array', 'max:20'],
            'componentConfig.additions.*' => ['required', 'string', 'max:100'],
            'componentConfig.deductions' => ['nullable', 'array', 'max:20'],
            'componentConfig.deductions.*' => ['required', 'string', 'max:150'],
            'rows' => ['required', 'array', 'min:1', 'max:200'],
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
            $hasil = $this->simpanSatuBaris($row, $i + 1, $periode, $config);
            if (isset($hasil['ok'])) {
                $disimpan[] = $hasil['ok'];
            } else {
                $gagal[] = $hasil['gagal'];
            }
        }

        // Import dari browser dikirim per batch; log ringkasan dicatat sekali
        // oleh klien di akhir proses supaya log aktivitas tidak penuh.
        if (! $request->boolean('tanpaLog')) {
            ActivityLog::catat(
                'Import payroll',
                'Mengimpor ' . count($disimpan) . ' data payroll periode ' . $periode . '.',
                ['total' => count($disimpan), 'gagal' => count($gagal)]
            );
        }

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
                fn($q) => $q->where('periode', $request->query('periode'))
            )
            ->latest('id')
            ->get();

        // Hasil kirim terakhir per payroll. Hanya dianggap bila terjadi SETELAH
        // payroll terakhir diperbarui (import ulang = mulai dari "Menunggu" lagi).
        $riwayatTerakhir = $payrolls->isEmpty()
            ? collect()
            : SlipHistory::whereIn('payroll_id', $payrolls->pluck('id'))
                ->orderByDesc('id')
                ->get()
                ->groupBy('payroll_id')
                ->map(fn($g) => $g->first());

        return $payrolls->map(function (Payroll $p) use ($riwayatTerakhir) {
            $karyawan = $p->karyawan;
            $h = $riwayatTerakhir->get($p->id);
            $hasilTerakhir = ($h && $p->updated_at && $h->created_at->gte($p->updated_at)) ? $h : null;
            $tambahan = $p->items->where('tipe', 'tambahan')->sortBy('urutan')->values();
            $potongan = $p->items->where('tipe', 'potongan')->sortBy('urutan')->values();

            $config = $p->component_config ?: [
                'additions' => [
                    'Tunjangan Jabatan',
                    'Tunjangan Bansos',
                    'Tunjangan Managerial',
                    'Tambahan 4',
                    'Tambahan 5',
                    'Tambahan 6',
                    'Tambahan 7',
                    'Tambahan 8',
                    'Tambahan 9',
                    'Tambahan 10',
                    'Tambahan 11',
                    'Tambahan 12',
                    'Tambahan 13',
                    'Tambahan 14',
                    'Tambahan 15',
                    'Tambahan 16',
                    'Tambahan 17',
                    'Tambahan 18',
                    'Tambahan 19',
                    'Tambahan 20',
                ],
                'deductions' => [
                    'BP JAMSOSTEK (JHT: 2%); (JP: 1%)',
                    'BPJS-Kshtn (JK:1%)',
                    'BNI (DPLK Simponi)',
                    'KSU Kel. Mitratani',
                    'Iuran Anggota SPA',
                    'BTN (KPR)',
                    'BRI Cab Jember (Briguna)',
                    'BRI Unit Serut (Golbertap)',
                    'Bank BSM (Pembiayaan Syariah)',
                    'Pengembalian Gaji (Kary Renbang)',
                    'Iuran IIKB',
                    'Potongan Kopkar PTPN I R5',
                    'Pengembalian Bonus Thn 2023',
                    'Potongan Dapenbun',
                    'Potongan gaji (Masjid)',
                    'Potongan Waring',
                    'Pembelian Pengembalian DPU',
                    'Potongan 18',
                    'Potongan 19',
                    'Potongan 20',
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
                'file' => $p->file_slip ?: 'SLIP_' . $p->karyawan_nik . '_' . optional($p->periode)->format('Y-m') . '.pdf',
                'pdfUrl' => route('api.slip.pdf', $p),
                'lastStatus' => $hasilTerakhir?->status,
                'lastReason' => $hasilTerakhir?->keterangan,
                'channel' => $p->channel ?: ($karyawan?->email ? 'email' : ($karyawan?->no_hp ? 'wa' : '')),
                'componentConfig' => [
                    'additions' => array_values($config['additions'] ?? []),
                    'deductions' => array_values($config['deductions'] ?? []),
                ],
            ];

            // Daftar komponen apa adanya (nama + nominal), sama dengan isi PDF.
            // Dipakai tampilan detail slip supaya nama komponen tidak bergeser.
            $row['tambahanItems'] = $tambahan->map(fn($it) => ['nama' => $it->nama, 'jumlah' => (float) $it->jumlah])->values()->all();
            $row['potonganItems'] = $potongan->map(fn($it) => ['nama' => $it->nama, 'jumlah' => (float) $it->jumlah])->values()->all();

            // Nomor tambahanN / potonganN dicocokkan lewat NAMA komponen pada konfigurasi
            // (nomor urutan tersimpan sudah rapat karena komponen kosong dibuang saat import).
            foreach (['tambahan' => [$tambahan, $row['componentConfig']['additions']], 'potongan' => [$potongan, $row['componentConfig']['deductions']]] as $kunci => [$daftar, $namaKomponen]) {
                $terpakai = [];
                foreach ($daftar as $item) {
                    $no = null;
                    foreach ($namaKomponen as $k => $nama) {
                        if ($nama === $item->nama && ! isset($terpakai[$k])) {
                            $no = $k + 1;
                            break;
                        }
                    }
                    $no ??= (int) $item->urutan;
                    $terpakai[$no - 1] = true;
                    $row[$kunci . $no] = (float) $item->jumlah;
                }
            }

            return $row;
        })->values();
    }

    /**
     * Simpan satu baris payroll (cocokkan NIK, simpan item, buat PDF).
     * Mengembalikan ['ok' => [...]] atau ['gagal' => [...]].
     */
    public function simpanSatuBaris(array $row, int $baris, string $periode, array $config): array
    {
        $karyawan = Karyawan::with(['divisi', 'jabatan', 'bank'])
            ->where('nik', $row['nik'])
            ->first();

        if (! $karyawan) {
            return ['gagal' => [
                'baris' => $baris,
                'nik' => $row['nik'],
                'alasan' => 'NIK tidak ditemukan di data master karyawan',
            ]];
        }

        if (! trim((string) $karyawan->email) && ! trim((string) $karyawan->no_hp)) {
            return ['gagal' => [
                'baris' => $baris,
                'nik' => $row['nik'],
                'alasan' => 'Email dan No. HP pada master karyawan kosong',
            ]];
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
                        'dikirim_pada' => null,
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
            // Tandai waktu import terbaru walau datanya identik, supaya hasil kirim
            // sebelumnya tidak ikut terbawa ke status preview.
            $payroll->touch();

            return ['ok' => [
                'id' => $payroll->id,
                'nik' => $payroll->karyawan_nik,
                'nama' => $payroll->nama_snapshot,
                'email' => $karyawan->email,
                'wa' => $karyawan->no_hp,
                'divisi' => $payroll->divisi_snapshot ?: '-',
            ]];
        } catch (\Throwable $e) {
            report($e);
            return ['gagal' => [
                'baris' => $baris,
                'nik' => $row['nik'],
                'alasan' => 'Data gagal disimpan/generate PDF: ' . $e->getMessage(),
            ]];
        }
    }

    /**
     * Hapus satu slip yang masih berstatus preview (siap_kirim).
     */
    public function hapusPreview(Payroll $payroll)
    {
        if ($payroll->status !== 'siap_kirim') {
            return response()->json(['message' => 'Hanya slip berstatus preview yang dapat dihapus.'], 422);
        }

        $nama = $payroll->nama_snapshot;
        $this->hapusPayrollPreview($payroll);

        ActivityLog::catat('Hapus preview slip', 'Menghapus preview slip ' . $nama . '.');

        return response()->json(['dihapus' => 1]);
    }

    /**
     * Hapus seluruh slip berstatus preview (siap_kirim), mis. saat import dibatalkan.
     */
    public function hapusSemuaPreview(Request $request)
    {
        @set_time_limit(0);

        $query = Payroll::where('status', 'siap_kirim')
            ->when($request->filled('periode'), fn($q) => $q->where('periode', $request->query('periode')));

        $total = 0;
        $query->chunkById(100, function ($payrolls) use (&$total) {
            foreach ($payrolls as $payroll) {
                $this->hapusPayrollPreview($payroll);
                $total++;
            }
        });

        ActivityLog::catat('Hapus preview slip', 'Menghapus ' . $total . ' preview slip.', ['total' => $total]);

        return response()->json(['dihapus' => $total]);
    }

    protected function hapusPayrollPreview(Payroll $payroll): void
    {
        // Jangan hapus file PDF bila slip ini pernah tercatat di riwayat pengiriman.
        $punyaRiwayat = SlipHistory::where('payroll_id', $payroll->id)->exists();

        DB::transaction(function () use ($payroll) {
            $payroll->items()->delete();
            $payroll->delete();
        });

        if (! $punyaRiwayat && $payroll->file_slip && Storage::disk('local')->exists('slip/' . $payroll->file_slip)) {
            Storage::disk('local')->delete('slip/' . $payroll->file_slip);
        }
    }

    public function pdf(Payroll $payroll)
    {
        $payroll->load(['items', 'karyawan']);

        if (! $payroll->file_slip || ! Storage::disk('local')->exists('slip/' . $payroll->file_slip)) {
            $filename = $this->generatePdf($payroll);
            $payroll->update(['file_slip' => $filename]);
        }

        return response()->file(
            Storage::disk('local')->path('slip/' . $payroll->file_slip),
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function generatePdf(Payroll $payroll): string
    {
        $payroll->load(['items', 'karyawan']);

        $filename = 'SLIP_' . $payroll->karyawan_nik . '_' . optional($payroll->periode)->format('Ym') . '.pdf';

        $pdf = Pdf::loadView('pdf.slip-gaji', [
            'payroll' => $payroll,
            'karyawan' => $payroll->karyawan,
        ])->setPaper('a4', 'portrait');

        Storage::disk('local')->makeDirectory('slip');
        Storage::disk('local')->put('slip/' . $filename, $pdf->output());

        return $filename;
    }

    protected function kirimSatuSlip(Payroll $payroll): bool
    {
        // Pakai akun email terbaru dari .env (mis. baru diganti di menu Pengaturan).
        MailEnv::terapkan();

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

        // PDF wajib berpassword tanggal lahir (DDMMYYYY). Tanpa tanggal lahir,
        // slip TIDAK dikirim supaya tidak pernah terkirim tanpa proteksi.
        if (! $karyawan->tanggal_lahir) {
            $alasan = 'Tanggal lahir karyawan kosong, PDF tidak dapat diberi password. Lengkapi di Data Karyawan.';
            $payroll->update(['status' => 'gagal', 'catatan' => $alasan]);

            return $this->catatHasilKirim($payroll, $karyawan, false, $alasan, 'email');
        }

        // Alamat email salah format: catat gagal dengan alasan yang jelas.
        $alamat = trim((string) $karyawan->email);
        if (! filter_var($alamat, FILTER_VALIDATE_EMAIL)) {
            $alasan = 'Alamat email tidak valid: "' . $alamat . '". Perbaiki di Data Karyawan lalu kirim ulang.';
            $payroll->update(['status' => 'gagal', 'catatan' => $alasan]);

            return $this->catatHasilKirim($payroll, $karyawan, false, $alasan, 'email');
        }

        try {
            $pdf = $this->buatPdfBerpassword($payroll, $karyawan);

            Mail::to($alamat)->send(
                new SlipGajiMail($payroll->fresh(['items']), $karyawan->nama, $pdf['binary'], $pdf['nama'])
            );

            if (! $payroll->file_slip) {
                $payroll->update(['file_slip' => $pdf['nama']]);
            }

            $payroll->update([
                'status' => 'terkirim',
                'channel' => 'email',
                'dikirim_pada' => now(),
                'dibuat_oleh' => auth()->id(),
            ]);

            return $this->catatHasilKirim($payroll, $karyawan, true, null, 'email');
        } catch (\Throwable $e) {
            report($e);
            $alasan = $this->ramahkanErrorEmail($e->getMessage());
            $payroll->update([
                'status' => 'gagal',
                'catatan' => $alasan,
            ]);

            return $this->catatHasilKirim($payroll, $karyawan, false, $alasan, 'email');
        }
    }

    /**
     * Buat PDF slip yang dienkripsi dengan password = tanggal lahir karyawan
     * berformat DDMMYYYY (mis. lahir 15 Agustus 1990 -> 15081990).
     *
     * Hasilnya hanya berupa isi (binary) untuk dilampirkan ke email; salinan
     * di storage (dipakai preview admin) tetap tanpa password. Password
     * pemilik (owner) diacak sehingga penerima tidak bisa mencabut proteksi;
     * yang diizinkan hanya membuka dan mencetak.
     *
     * @return array{binary: string, nama: string}
     */
    protected function buatPdfBerpassword(Payroll $payroll, Karyawan $karyawan): array
    {
        $payroll->load(['items']);

        $password = $karyawan->tanggal_lahir->format('dmY');

        $pdf = Pdf::loadView('pdf.slip-gaji', [
            'payroll' => $payroll,
            'karyawan' => $karyawan,
        ])->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->get_cpdf()->setEncryption($password, Str::random(32), ['print']);

        $binary = $dompdf->output();

        if (! is_string($binary) || ! str_starts_with($binary, '%PDF')) {
            throw new \RuntimeException('Gagal membuat PDF berpassword.');
        }

        return [
            'binary' => $binary,
            'nama' => $payroll->file_slip
                ?: 'SLIP_' . $payroll->karyawan_nik . '_' . optional($payroll->periode)->format('Ym') . '.pdf',
        ];
    }

    /**
     * Ubah pesan teknis SMTP menjadi alasan yang mudah dibaca di Riwayat.
     * Pesan aslinya tetap disertakan di belakang agar mudah ditelusuri.
     */
    protected function ramahkanErrorEmail(string $pesan): string
    {
        $m = strtolower($pesan);
        $ringkas = null;

        if (preg_match('/\b550\b|5\.1\.1|user unknown|no such user|does not exist|mailbox unavailable|address not found|recipient.*(rejected|invalid)/', $m)) {
            $ringkas = 'Alamat email tujuan tidak ditemukan / ditolak server penerima';
        } elseif (preg_match('/domain.*(not found|invalid)|host.*not found|could not resolve|name or service not known/', $m)) {
            $ringkas = 'Domain email tujuan tidak ditemukan';
        } elseif (preg_match('/authenticat|username and password|535/', $m)) {
            $ringkas = 'Login akun pengirim email ditolak, cek akun email di Pengaturan';
        } elseif (preg_match('/timed out|timeout|connection could not be established|connection refused/', $m)) {
            $ringkas = 'Koneksi ke server email gagal / waktu habis';
        }

        $asli = Str::limit(trim(preg_replace('/\s+/', ' ', $pesan)), 220);

        return $ringkas ? $ringkas . ' (' . $asli . ')' : $asli;
    }

    protected function ensurePdf(Payroll $payroll): string
    {
        if ($payroll->file_slip && Storage::disk('local')->exists('slip/' . $payroll->file_slip)) {
            return Storage::disk('local')->path('slip/' . $payroll->file_slip);
        }

        $filename = $this->generatePdf($payroll->fresh(['items']));
        $payroll->update(['file_slip' => $filename]);

        return Storage::disk('local')->path('slip/' . $filename);
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
            ->filter(
                fn(Payroll $p) =>
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
            ->filter(
                fn(Payroll $p) =>
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
            "{$labelLog}: " . count($terkirim) . ' berhasil, ' . count($gagal) . ' gagal.',
            ['berhasil' => count($terkirim), 'gagal' => count($gagal)]
        );

        return ['terkirim' => $terkirim, 'gagal' => $gagal];
    }

    public function kirimSatu(Request $request, Payroll $payroll)
    {
        if ($payroll->status === 'terkirim') {
            return response()->json(['message' => 'Slip ini sudah terkirim. Import ulang bila ada revisi.'], 422);
        }

        $payroll->load('karyawan');
        $ok = $this->kirimSatuSlip($payroll);
        $payroll = $payroll->fresh();

        return response()->json([
            'ok' => $ok,
            'status' => $payroll->status,
            'reason' => $ok ? null : ($payroll->catatan ?: 'Gagal dikirim, lihat Riwayat untuk detail.'),
        ]);
    }

    /* =========================================================
     * Import di latar belakang (antrian bertahap)
     *
     * Alur: klien membuat job (POST import-jobs), lalu skrip global
     * public/js/import-runner.js (aktif di SEMUA halaman) memanggil
     * "proses" berulang kali. Status tersimpan di database, sehingga
     * pindah menu tidak menghentikan proses - halaman berikutnya
     * langsung melanjutkan dari baris terakhir.
     * ========================================================= */

    private const JOB_BATCH = 10;

    protected function ringkasJob(ImportJob $job, bool $denganHasil = false): array
    {
        $hasil = $job->hasil ?: ['gagal' => [], 'data' => []];

        $out = [
            'id' => $job->id,
            'status' => $job->status,
            'total' => (int) $job->total,
            'processed' => (int) $job->processed,
            'berhasil' => (int) $job->berhasil,
            'gagalCount' => count($hasil['gagal'] ?? []),
            'dilihat' => (bool) $job->dilihat,
            'periode' => optional($job->periode)->format('Y-m-d'),
        ];

        if ($denganHasil || $job->status !== 'berjalan') {
            $out['gagal'] = $hasil['gagal'] ?? [];
            $out['data'] = $hasil['data'] ?? [];
        }

        return $out;
    }

    /** Job milik user: yang sedang berjalan, atau yang sudah selesai tetapi belum dilihat. */
    public function jobAktif(Request $request)
    {
        $job = ImportJob::where('user_id', auth()->id())
            ->where(fn($q) => $q->where('status', 'berjalan')->orWhere('dilihat', false))
            ->latest('id')
            ->first();

        return response()->json(['job' => $job ? $this->ringkasJob($job) : null]);
    }

    public function jobBuat(Request $request)
    {
        $data = $request->validate([
            'periode' => ['required', 'date'],
            'componentConfig' => ['nullable', 'array'],
            'componentConfig.additions' => ['nullable', 'array', 'max:20'],
            'componentConfig.additions.*' => ['required', 'string', 'max:100'],
            'componentConfig.deductions' => ['nullable', 'array', 'max:20'],
            'componentConfig.deductions.*' => ['required', 'string', 'max:150'],
            'rows' => ['required', 'array', 'min:1', 'max:3000'],
            'parseErrors' => ['nullable', 'array', 'max:3000'],
            'parseErrors.*.row' => ['nullable', 'integer'],
            'parseErrors.*.msg' => ['nullable', 'string', 'max:300'],
            'rows.*.row' => ['nullable', 'integer'],
            'rows.*.nik' => ['required', 'digits:8'],
            'rows.*.gajiPokok' => ['required', 'numeric', 'min:0'],
            'rows.*.tambahan' => ['nullable', 'array', 'max:20'],
            'rows.*.tambahan.*.nama' => ['required', 'string', 'max:100'],
            'rows.*.tambahan.*.jumlah' => ['required', 'numeric', 'min:0'],
            'rows.*.potongan' => ['nullable', 'array', 'max:20'],
            'rows.*.potongan.*.nama' => ['required', 'string', 'max:150'],
            'rows.*.potongan.*.jumlah' => ['required', 'numeric', 'min:0'],
        ]);

        $berjalan = ImportJob::where('user_id', auth()->id())->where('status', 'berjalan')->first();
        if ($berjalan) {
            return response()->json([
                'message' => 'Masih ada import gaji yang sedang diproses. Tunggu sampai selesai atau batalkan terlebih dahulu.',
                'job' => $this->ringkasJob($berjalan),
            ], 409);
        }

        $config = [
            'additions' => array_values(array_slice(array_filter($data['componentConfig']['additions'] ?? []), 0, self::MAX_COMPONENTS)),
            'deductions' => array_values(array_slice(array_filter($data['componentConfig']['deductions'] ?? []), 0, self::MAX_COMPONENTS)),
        ];

        $job = ImportJob::create([
            'user_id' => auth()->id(),
            'periode' => $data['periode'],
            'total' => count($data['rows']),
            'processed' => 0,
            'berhasil' => 0,
            'status' => 'berjalan',
            'dilihat' => false,
            'payload' => ['config' => $config, 'rows' => array_values($data['rows'])],
            // Baris yang sudah ditolak saat membaca Excel (mis. NIK bukan 8 digit)
            // ikut tercatat supaya tetap tampil di hasil import.
            'hasil' => ['gagal' => array_map(fn($e) => [
                'baris' => (int) ($e['row'] ?? 0),
                'nik' => '',
                'alasan' => (string) ($e['msg'] ?? 'Baris tidak valid'),
            ], $data['parseErrors'] ?? []), 'data' => []],
        ]);

        return response()->json(['job' => $this->ringkasJob($job)], 201);
    }

    /** Proses batch berikutnya dari job. Aman dipanggil dari beberapa tab (ada kunci). */
    public function jobProses(ImportJob $job)
    {
        abort_unless((int) $job->user_id === (int) auth()->id(), 403);

        if ($job->status !== 'berjalan') {
            return response()->json(['job' => $this->ringkasJob($job), 'sibuk' => false]);
        }

        // Kunci: hanya satu proses pada satu waktu untuk job yang sama.
        $dikunci = ImportJob::where('id', $job->id)
            ->where(fn($q) => $q->whereNull('locked_until')->orWhere('locked_until', '<', now()))
            ->update(['locked_until' => now()->addSeconds(180)]);

        if (! $dikunci) {
            return response()->json(['job' => $this->ringkasJob($job->fresh()), 'sibuk' => true]);
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        ignore_user_abort(true);

        try {
            $job = $job->fresh();
            if ($job->status !== 'berjalan') {
                return response()->json(['job' => $this->ringkasJob($job), 'sibuk' => false]);
            }

            $payload = $job->payload ?: ['config' => ['additions' => [], 'deductions' => []], 'rows' => []];
            $hasil = $job->hasil ?: ['gagal' => [], 'data' => []];
            $periode = $job->periode->format('Y-m-d');
            $rows = $payload['rows'];
            $mulai = (int) $job->processed;
            $batch = array_slice($rows, $mulai, self::JOB_BATCH, true);
            $berhasil = (int) $job->berhasil;

            foreach ($batch as $idx => $row) {
                $baris = (int) ($row['row'] ?? ($idx + 2));
                $res = $this->simpanSatuBaris($row, $baris, $periode, $payload['config']);
                if (isset($res['ok'])) {
                    $berhasil++;
                    $hasil['data'][] = $res['ok'];
                } else {
                    $hasil['gagal'][] = $res['gagal'];
                }
                $mulai++;

                // Simpan kemajuan tiap baris supaya tidak hilang bila proses terputus.
                $job->update(['processed' => $mulai, 'berhasil' => $berhasil, 'hasil' => $hasil]);
            }

            if ($mulai >= (int) $job->total) {
                $job->update(['status' => 'selesai', 'payload' => null]);
                ActivityLog::catat(
                    'Import payroll',
                    'Mengimpor ' . $berhasil . ' data payroll periode ' . $periode . '.',
                    ['total' => $berhasil, 'gagal' => count($hasil['gagal'])]
                );
            }
        } finally {
            ImportJob::where('id', $job->id)->update(['locked_until' => null]);
        }

        return response()->json(['job' => $this->ringkasJob($job->fresh()), 'sibuk' => false]);
    }

    public function jobBatal(ImportJob $job)
    {
        abort_unless((int) $job->user_id === (int) auth()->id(), 403);

        if ($job->status === 'berjalan') {
            $job->update(['status' => 'dibatalkan', 'payload' => null, 'dilihat' => true]);
            ActivityLog::catat('Batal import payroll', 'Import gaji dibatalkan pada ' . $job->processed . ' dari ' . $job->total . ' baris.');
        }

        return response()->json(['job' => $this->ringkasJob($job->fresh())]);
    }

    public function jobDilihat(ImportJob $job)
    {
        abort_unless((int) $job->user_id === (int) auth()->id(), 403);
        if ($job->status !== 'berjalan') {
            $job->update(['dilihat' => true]);
        }

        return response()->json(['ok' => true]);
    }

    /* =========================================================
     * Pengiriman slip di latar belakang (antrian bertahap)
     *
     * Sama seperti import: klien membuat job (POST kirim-jobs), skrip
     * global js/import-runner.js (SendRunner) memanggil "proses" berulang
     * kali (beberapa slip per panggilan). Status tersimpan di database,
     * jadi pindah menu tidak menghentikan pengiriman.
     * ========================================================= */

    private const SEND_BATCH = 4;

    protected function ringkasSend(SendJob $job): array
    {
        $hasil = $job->hasil ?: ['terkirim' => [], 'gagal' => []];
        $payload = $job->payload ?: [];

        $out = [
            'id' => $job->id,
            'status' => $job->status,
            'channel' => $job->channel,
            'label' => $job->label,
            'total' => (int) $job->total,
            'processed' => (int) $job->processed,
            'berhasil' => (int) $job->berhasil,
            'gagalCount' => count($hasil['gagal'] ?? []),
            'dilihat' => (bool) $job->dilihat,
        ];

        if ($job->status !== 'berjalan') {
            $out['terkirim'] = $hasil['terkirim'] ?? [];
            $out['gagal'] = $hasil['gagal'] ?? [];
        } else {
            // Untuk animasi persen di tombol per divisi (halaman Preview Slip).
            $out['semua'] = (bool) ($payload['semua'] ?? true);
            $out['divisi'] = $payload['divisiTarget'] ?? null;

            $perDivisi = [];
            foreach (($payload['divisiList'] ?? []) as $idx => $nama) {
                $perDivisi[$nama] ??= ['total' => 0, 'selesai' => 0];
                $perDivisi[$nama]['total']++;
                if ($idx < (int) $job->processed) {
                    $perDivisi[$nama]['selesai']++;
                }
            }
            $out['perDivisi'] = (object) $perDivisi;
        }

        return $out;
    }

    public function sendAktif()
    {
        $job = SendJob::where('user_id', auth()->id())
            ->where(fn($q) => $q->where('status', 'berjalan')->orWhere('dilihat', false))
            ->latest('id')
            ->first();

        return response()->json(['job' => $job ? $this->ringkasSend($job) : null]);
    }

    public function sendBuat(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', 'in:email,wa'],
            'all' => ['nullable', 'boolean'],
            'divisi' => ['nullable', 'string', 'max:150', 'required_without:all'],
        ]);

        $all = (bool) ($data['all'] ?? false);
        $channel = $data['channel'];

        $settings = Setting::current();
        if ($channel === 'email' && ! $settings->email_enabled) {
            return response()->json(['message' => 'Pengiriman Email sedang dinonaktifkan di Pengaturan.'], 422);
        }
        if ($channel === 'wa' && ! $settings->wa_enabled) {
            return response()->json(['message' => 'Pengiriman WhatsApp sedang dinonaktifkan di Pengaturan.'], 422);
        }

        $berjalan = SendJob::where('user_id', auth()->id())->where('status', 'berjalan')->first();
        if ($berjalan) {
            return response()->json([
                'message' => 'Masih ada pengiriman slip yang sedang diproses. Tunggu sampai selesai atau batalkan terlebih dahulu.',
                'job' => $this->ringkasSend($berjalan),
            ], 409);
        }

        $terpilih = Payroll::with('karyawan')
            ->where('status', 'siap_kirim')
            ->when(! $all, fn($q) => $q->where('divisi_snapshot', $data['divisi']))
            ->orderBy('id')
            ->get()
            ->filter(fn(Payroll $p) => $this->resolveChannel($p) === $channel)
            ->values();

        $ids = $terpilih->pluck('id')->all();
        $divisiList = $terpilih->map(fn(Payroll $p) => $p->divisi_snapshot ?: '-')->all();

        if (! $ids) {
            return response()->json(['message' => 'Tidak ada slip pada channel ini yang menunggu dikirim.'], 422);
        }

        $labelChannel = $channel === 'email' ? 'Email' : 'WhatsApp';
        $job = SendJob::create([
            'user_id' => auth()->id(),
            'channel' => $channel,
            'label' => ($all ? 'Semua divisi' : 'Divisi ' . $data['divisi']) . ' via ' . $labelChannel,
            'total' => count($ids),
            'status' => 'berjalan',
            'dilihat' => false,
            'payload' => [
                'ids' => $ids,
                'divisiList' => $divisiList,
                'semua' => $all,
                'divisiTarget' => $all ? null : $data['divisi'],
            ],
            'hasil' => ['terkirim' => [], 'gagal' => []],
        ]);

        return response()->json(['job' => $this->ringkasSend($job)], 201);
    }

    public function sendProses(SendJob $job)
    {
        abort_unless((int) $job->user_id === (int) auth()->id(), 403);

        if ($job->status !== 'berjalan') {
            return response()->json(['job' => $this->ringkasSend($job), 'sibuk' => false]);
        }

        $dikunci = SendJob::where('id', $job->id)
            ->where(fn($q) => $q->whereNull('locked_until')->orWhere('locked_until', '<', now()))
            ->update(['locked_until' => now()->addSeconds(240)]);

        if (! $dikunci) {
            return response()->json(['job' => $this->ringkasSend($job->fresh()), 'sibuk' => true]);
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        ignore_user_abort(true);

        try {
            $job = $job->fresh();
            if ($job->status !== 'berjalan') {
                return response()->json(['job' => $this->ringkasSend($job), 'sibuk' => false]);
            }

            $ids = ($job->payload ?: [])['ids'] ?? [];
            $hasil = $job->hasil ?: ['terkirim' => [], 'gagal' => []];
            $mulai = (int) $job->processed;
            $berhasil = (int) $job->berhasil;

            foreach (array_slice($ids, $mulai, self::SEND_BATCH) as $id) {
                $payroll = Payroll::with('karyawan')->find($id);
                $mulai++;

                // Slip sudah dihapus / sudah diproses di tempat lain: lewati.
                if ($payroll && $payroll->status === 'siap_kirim') {
                    $karyawan = $payroll->karyawan;
                    $snapshot = [
                        'nik' => $payroll->karyawan_nik,
                        'nama' => $payroll->nama_snapshot,
                        'email' => $karyawan?->email,
                        'wa' => $karyawan?->no_hp,
                    ];

                    if ($this->kirimSatuSlip($payroll)) {
                        $berhasil++;
                        $hasil['terkirim'][] = $snapshot;
                    } else {
                        $hasil['gagal'][] = [
                            'row' => $snapshot,
                            'reason' => $payroll->fresh()->catatan ?: 'Gagal dikirim, lihat Riwayat untuk detail.',
                        ];
                    }
                }

                $job->update(['processed' => $mulai, 'berhasil' => $berhasil, 'hasil' => $hasil]);
            }

            if ($mulai >= (int) $job->total) {
                $job->update(['status' => 'selesai', 'payload' => null]);
                ActivityLog::catat(
                    'Pengiriman slip',
                    ($job->label ?: 'Kirim slip') . ': ' . $berhasil . ' berhasil, ' . count($hasil['gagal']) . ' gagal.',
                    ['berhasil' => $berhasil, 'gagal' => count($hasil['gagal'])]
                );
            }
        } finally {
            SendJob::where('id', $job->id)->update(['locked_until' => null]);
        }

        return response()->json(['job' => $this->ringkasSend($job->fresh()), 'sibuk' => false]);
    }

    public function sendBatal(SendJob $job)
    {
        abort_unless((int) $job->user_id === (int) auth()->id(), 403);

        if ($job->status === 'berjalan') {
            $job->update(['status' => 'dibatalkan', 'payload' => null, 'dilihat' => true]);
            ActivityLog::catat('Batal kirim slip', 'Pengiriman slip dibatalkan pada ' . $job->processed . ' dari ' . $job->total . ' slip.');
        }

        return response()->json(['job' => $this->ringkasSend($job->fresh())]);
    }

    public function sendDilihat(SendJob $job)
    {
        abort_unless((int) $job->user_id === (int) auth()->id(), 403);
        if ($job->status !== 'berjalan') {
            $job->update(['dilihat' => true]);
        }

        return response()->json(['ok' => true]);
    }
}
