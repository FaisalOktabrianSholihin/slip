<?php

namespace App\Http\Controllers;

use App\Models\MasterData\Divisi;
use App\Models\MasterData\Jabatan;
use App\Models\MasterData\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * KaryawanController
 *
 * Menyambungkan public/js/data-karyawan.js ke database db_indukk.
 * Bentuk JSON request/response SENGAJA dibuat identik dengan objek
 * `data[]` yang dipakai JS (camelCase, bank[]/anak[] nested), supaya
 * perubahan di sisi JS bisa seminimal mungkin - lihat komentar
 * "BACKEND" pada file tersebut.
 */
class KaryawanController extends Controller
{
    /**
     * Field karyawan_pribadi, karyawan_pendidikan, karyawan_keluarga
     * dipetakan 1-ke-1 dari kolom DB (snake_case) ke key JS (camelCase).
     */
    protected function toJson(Karyawan $k): array
    {
        $k->loadMissing(['divisi', 'jabatan', 'pribadi', 'pendidikan', 'keluarga', 'bank', 'anak']);

        return [
            'id' => $k->id,
            'nik' => $k->nik,
            'noAbsen' => $k->no_absen,
            'golongan' => $k->golongan,
            'nama' => $k->nama,
            'namaPanggilan' => $k->nama_panggilan,
            'jabatan' => $k->jabatan?->nama_jabatan,
            'divisi' => $k->divisi?->nama_divisi,
            'statusPegawai' => $k->status_pegawai,
            'aktif' => (bool) $k->status_aktif,
            'masaKerja' => $k->masa_kerja,
            'tglMasuk' => optional($k->tanggal_masuk)->format('Y-m-d'),
            'tglAngkat' => optional($k->tanggal_pengangkatan)->format('Y-m-d'),
            'tglPensiun' => optional($k->tanggal_pensiun)->format('Y-m-d'),
            'jatahCuti' => $k->jatah_cuti,
            'sisaCuti' => $k->sisa_cuti,
            'email' => $k->email,
            'ktp' => $k->no_ktp,
            'hp' => $k->no_hp,
            'alamat' => $k->alamat,

            'jenisKelamin' => $k->jenis_kelamin,
            'agama' => $k->pribadi?->agama,
            'suku' => $k->pribadi?->suku,
            'tempatLahir' => $k->pribadi?->tempat_lahir,
            'tglLahir' => optional($k->tanggal_lahir)->format('Y-m-d'),
            'statusNikah' => $k->pribadi?->status_nikah,
            'hobby' => $k->pribadi?->hobby,

            'sd' => $k->pendidikan?->sd,
            'sltp' => $k->pendidikan?->sltp,
            'slta' => $k->pendidikan?->slta,
            'pt' => $k->pendidikan?->pt,
            'pendidikanTerakhir' => $k->pendidikan?->pendidikan_terakhir,
            'jurusan' => $k->pendidikan?->jurusan,
            'tahunMasuk' => $k->pendidikan?->tahun_masuk,
            'tahunKeluar' => $k->pendidikan?->tahun_keluar,

            'namaAyah' => $k->keluarga?->nama_ayah,
            'namaIbu' => $k->keluarga?->nama_ibu,
            'namaPasangan' => $k->keluarga?->nama_pasangan,
            'tempatLahirPasangan' => $k->keluarga?->tempat_lahir_pasangan,
            'tglLahirPasangan' => optional($k->keluarga?->tanggal_lahir_pasangan)->format('Y-m-d'),
            'pekerjaanPasangan' => $k->keluarga?->pekerjaan_pasangan,
            'jkPasangan' => $k->keluarga?->jenis_kelamin_pasangan,
            'pendidikanPasangan' => $k->keluarga?->pendidikan_pasangan,
            'jaminanKesehatan' => $k->keluarga?->jaminan_kesehatan,

            'bank' => $k->bank->map(fn($b) => [
                'namaBank' => $b->bank,
                'noRekening' => $b->no_rekening,
                'atasNama' => $b->atas_nama,
            ])->values(),

            'anak' => $k->anak->map(fn($a) => [
                'nama' => $a->nama,
                'tempatLahir' => $a->tempat_lahir,
                'tglLahir' => optional($a->tanggal_lahir)->format('Y-m-d'),
                'pekerjaan' => $a->pekerjaan,
                'jenisKelamin' => $a->jenis_kelamin,
                'pendidikan' => $a->pendidikan,
            ])->values(),
        ];
    }

    public function index()
    {
        return Karyawan::query()
            ->with(['divisi', 'jabatan', 'pribadi', 'pendidikan', 'keluarga', 'bank', 'anak'])
            ->orderBy('nama')
            ->get()
            ->map(fn(Karyawan $k) => $this->toJson($k))
            ->values();
    }

    protected function rules(?int $ignoreId = null): array
    {
        return [
            'nik' => ['required', 'digits:8', Rule::unique('db_induk.karyawans', 'nik')->ignore($ignoreId)],
            'nama' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email', Rule::unique('db_induk.karyawans', 'email')->ignore($ignoreId)],
            'ktp' => ['required', 'digits:16', Rule::unique('db_induk.karyawans', 'no_ktp')->ignore($ignoreId)],
            'hp' => ['required', 'string', 'min:8'],
            'alamat' => ['required', 'string'],
            'jabatan' => ['required', 'string'],
            'divisi' => ['required', 'string'],
            'jenisKelamin' => ['required', 'in:L,P'],
            'tglLahir' => ['required', 'date'],
            'bank' => ['array'],
            'bank.*.namaBank' => ['nullable', 'string'],
            'bank.*.noRekening' => ['nullable', 'string'],
            'bank.*.atasNama' => ['nullable', 'string'],
            'anak' => ['array'],
            'anak.*.nama' => ['nullable', 'string'],
        ];
    }

    /**
     * Bersihkan nilai dari Excel secara rekursif: teks di-trim, lalu sel kosong
     * atau placeholder ("-", "–", "—") dijadikan null. Tanpa ini, "-" pada kolom
     * tanggal membuat Carbon error, dan "-" pada Bank/Anak dianggap ada isinya.
     */
    protected function normalizeImportValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn($v) => $this->normalizeImportValue($v), $value);
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || in_array($value, ['-', '–', '—'], true)) {
                return null;
            }
        }

        return $value;
    }

    /**
     * Kolom masa_kerja bertipe angka (tahun), tetapi Excel berisi teks seperti
     * "5 Tahun" atau "8 Bulan". Ambil angkanya saja; bulan dikonversi ke tahun.
     */
    protected function parseMasaKerja(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        if (!preg_match('/\d+/', (string) $value, $m)) {
            return null;
        }
        $angka = (int) $m[0];

        return stripos((string) $value, 'bulan') !== false ? intdiv($angka, 12) : $angka;
    }

    /**
     * Simpan/perbarui satu baris karyawan beserta seluruh tabel anak
     * (pribadi/pendidikan/keluarga = hasOne, bank/anak = hasMany).
     */
    protected function persist(array $data, ?Karyawan $karyawan = null): Karyawan
    {
        return DB::connection('db_induk')->transaction(function () use ($data, $karyawan) {
            // Cocokkan divisi/jabatan tanpa sensitif terhadap huruf besar-kecil
            // atau spasi di Excel. Nama master yang sudah ada tetap dipakai,
            // sehingga 'Teknologi Informasi', 'teknologi informasi' dan
            // ' Teknologi Informasi ' tidak membuat master baru.
            $namaDivisi = trim((string) $data['divisi']);
            $divisi = Divisi::whereRaw('LOWER(TRIM(nama_divisi)) = ?', [mb_strtolower($namaDivisi)])
                ->first();
            if (!$divisi) {
                $divisi = Divisi::create(['nama_divisi' => $namaDivisi]);
            }

            $namaJabatan = trim((string) $data['jabatan']);
            $jabatan = Jabatan::whereRaw('LOWER(TRIM(nama_jabatan)) = ?', [mb_strtolower($namaJabatan)])
                ->first();
            if (!$jabatan) {
                $jabatan = Jabatan::create(['nama_jabatan' => $namaJabatan]);
            }

            $payload = [
                'nik' => $data['nik'],
                'no_absen' => $data['noAbsen'] ?? null,
                'golongan' => $data['golongan'] ?? null,
                'nama' => $data['nama'],
                'nama_panggilan' => $data['namaPanggilan'] ?? null,
                'jabatan_id' => $jabatan->id,
                'divisi_id' => $divisi->id,
                'status_pegawai' => Karyawan::normalisasiStatus($data['statusPegawai'] ?? null) ?? ($data['statusPegawai'] ?? null),
                'status_aktif' => (bool) ($data['aktif'] ?? true),
                'masa_kerja' => $this->parseMasaKerja($data['masaKerja'] ?? null),
                'tanggal_masuk' => $data['tglMasuk'] ?? null,
                'tanggal_pengangkatan' => $data['tglAngkat'] ?? null,
                'tanggal_pensiun' => $data['tglPensiun'] ?? null,
                'jatah_cuti' => $data['jatahCuti'] ?? 12,
                'sisa_cuti' => $data['sisaCuti'] ?? 12,
                'email' => $data['email'],
                'no_ktp' => $data['ktp'],
                'no_hp' => $data['hp'],
                'alamat' => $data['alamat'],
                'jenis_kelamin' => $data['jenisKelamin'],
                'tanggal_lahir' => $data['tglLahir'],
            ];

            if ($karyawan) {
                $karyawan->update($payload);
            } else {
                $karyawan = Karyawan::create($payload);
            }

            $karyawan->pribadi()->updateOrCreate([], [
                'agama' => $data['agama'] ?? null,
                'suku' => $data['suku'] ?? null,
                'tempat_lahir' => $data['tempatLahir'] ?? null,
                'status_nikah' => $data['statusNikah'] ?? null,
                'hobby' => $data['hobby'] ?? null,
            ]);

            $karyawan->pendidikan()->updateOrCreate([], [
                'sd' => $data['sd'] ?? null,
                'sltp' => $data['sltp'] ?? null,
                'slta' => $data['slta'] ?? null,
                'pt' => $data['pt'] ?? null,
                'pendidikan_terakhir' => Karyawan::normalisasiPendidikan($data['pendidikanTerakhir'] ?? null) ?? ($data['pendidikanTerakhir'] ?? null),
                'jurusan' => $data['jurusan'] ?? null,
                'tahun_masuk' => $data['tahunMasuk'] ?? null,
                'tahun_keluar' => $data['tahunKeluar'] ?? null,
            ]);

            $karyawan->keluarga()->updateOrCreate([], [
                'nama_ayah' => $data['namaAyah'] ?? null,
                'nama_ibu' => $data['namaIbu'] ?? null,
                'nama_pasangan' => $data['namaPasangan'] ?? null,
                'tempat_lahir_pasangan' => $data['tempatLahirPasangan'] ?? null,
                'tanggal_lahir_pasangan' => $data['tglLahirPasangan'] ?? null,
                'pekerjaan_pasangan' => $data['pekerjaanPasangan'] ?? null,
                'jenis_kelamin_pasangan' => $data['jkPasangan'] ?? null,
                'pendidikan_pasangan' => $data['pendidikanPasangan'] ?? null,
                'jaminan_kesehatan' => $data['jaminanKesehatan'] ?? null,
            ]);

            // bank & anak: hasMany -> ganti seluruh baris (sederhana & konsisten
            // dengan cara frontend mengirim array penuh setiap kali submit).
            $karyawan->bank()->delete();
            foreach (($data['bank'] ?? []) as $b) {
                if (empty($b['namaBank']) && empty($b['noRekening']) && empty($b['atasNama'])) {
                    continue;
                }
                $karyawan->bank()->create([
                    'bank' => $b['namaBank'] ?? null,
                    'no_rekening' => $b['noRekening'] ?? null,
                    'atas_nama' => $b['atasNama'] ?? null,
                ]);
            }

            $karyawan->anak()->delete();
            foreach (($data['anak'] ?? []) as $a) {
                if (empty($a['nama'])) {
                    continue;
                }
                $karyawan->anak()->create([
                    'nama' => $a['nama'],
                    'tempat_lahir' => $a['tempatLahir'] ?? null,
                    'tanggal_lahir' => $a['tglLahir'] ?? null,
                    'pekerjaan' => $a['pekerjaan'] ?? null,
                    'jenis_kelamin' => $a['jenisKelamin'] ?? null,
                    'pendidikan' => $a['pendidikan'] ?? null,
                ]);
            }

            return $karyawan;
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $karyawan = $this->persist($data);

        \App\Models\ActivityLog::catat('Tambah karyawan', "Menambahkan karyawan {$karyawan->nama} (NIK {$karyawan->nik}).");

        return response()->json($this->toJson($karyawan), 201);
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $data = $request->validate($this->rules($karyawan->id));
        $karyawan = $this->persist($data, $karyawan);

        \App\Models\ActivityLog::catat('Ubah karyawan', "Memperbarui data karyawan {$karyawan->nama} (NIK {$karyawan->nik}).");

        return response()->json($this->toJson($karyawan));
    }

    public function destroy(Karyawan $karyawan)
    {
        DB::connection('db_induk')->transaction(function () use ($karyawan) {
            $karyawan->bank()->delete();
            $karyawan->anak()->delete();
            $karyawan->pribadi()->delete();
            $karyawan->pendidikan()->delete();
            $karyawan->keluarga()->delete();
            $karyawan->delete();
        });

        \App\Models\ActivityLog::catat('Hapus karyawan', "Menghapus karyawan {$karyawan->nama} (NIK {$karyawan->nik}).");

        return response()->json(['ok' => true]);
    }

    /**
     * Import massal hasil parsing Excel dari data-karyawan.js
     * (fungsi importFromSheet), dicocokkan berdasarkan NIK: kalau NIK
     * sudah ada -> update, kalau belum -> insert baru.
     */
    public function import(Request $request)
    {
        // PENTING: validate() hanya mengembalikan field yang ada di aturan
        // validasi (nik & nama). Kalau hasilnya dipakai langsung, kolom
        // lain seperti email, hp, divisi, jabatan, bank, anak ikut terbuang.
        // Jadi validasi dijalankan saja, lalu ambil data lengkap dari request.
        $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.nik' => ['required', 'digits:8'],
            'rows.*.nama' => ['required', 'string'],
        ]);
        $rows = $this->normalizeImportValue($request->input('rows'));

        $hasil = [];
        $gagal = [];

        foreach ($rows as $index => $row) {
            $nik = trim((string) ($row['nik'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? ''));
            $nomorBaris = $index + 2; // header Excel berada di baris 1

            try {
                // Cari data lama berdasarkan NIK. Import bersifat upsert:
                // NIK yang sudah ada diperbarui, NIK baru dibuat.
                $existing = Karyawan::with(['divisi', 'jabatan'])
                    ->where('nik', $nik)
                    ->first();

                // Excel sering berisi cell kosong (""), bukan null. Jangan
                // sampai cell kosong menimpa data master lama atau membuat
                // kolom unique (email/KTP) menjadi string kosong.
                $valueOr = static function ($value, $fallback = null) {
                    if ($value === null) {
                        return $fallback;
                    }
                    if (is_string($value) && trim($value) === '') {
                        return $fallback;
                    }
                    return $value;
                };

                $payload = $row;
                $payload['nik'] = $nik;
                $payload['nama'] = $nama;
                // Jangan pernah membuat email/WA dummy. Untuk import, nilai
                // kontak harus berasal dari Excel; saat update NIK yang sama,
                // hanya gunakan nilai lama jika cell Excel memang kosong.
                $payload['email'] = trim((string) $valueOr($row['email'] ?? null, $existing?->email));
                $payload['hp'] = trim((string) $valueOr($row['hp'] ?? null, $existing?->no_hp));
                $payload['ktp'] = $valueOr($row['ktp'] ?? null, $existing?->no_ktp);
                $payload['alamat'] = $valueOr($row['alamat'] ?? null, $existing?->alamat);
                $payload['jabatan'] = $valueOr($row['jabatan'] ?? null, $existing?->jabatan?->nama_jabatan);
                $payload['divisi'] = $valueOr($row['divisi'] ?? null, $existing?->divisi?->nama_divisi);
                $payload['jenisKelamin'] = $valueOr($row['jenisKelamin'] ?? null, $existing?->jenis_kelamin);
                $payload['tglLahir'] = $valueOr(
                    $row['tglLahir'] ?? null,
                    optional($existing?->tanggal_lahir)->format('Y-m-d')
                );

                // Pastikan status kosong tidak membuat data menjadi nonaktif.
                if (!array_key_exists('aktif', $row) || $row['aktif'] === '' || $row['aktif'] === null) {
                    $payload['aktif'] = $existing?->status_aktif ?? true;
                }

                // Email dan WA adalah kunci pengiriman slip. Jangan simpan
                // data import sebagai berhasil jika keduanya tidak tersedia.
                if (!$payload['email'] || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Email karyawan kosong atau tidak valid.');
                }
                if (!$payload['hp'] || !preg_match('/^[0-9+().\s-]{8,20}$/', (string) $payload['hp'])) {
                    throw new \RuntimeException('No HP / WhatsApp karyawan kosong atau tidak valid.');
                }
                if (!$payload['divisi']) {
                    throw new \RuntimeException('Divisi karyawan kosong.');
                }
                if (!$payload['jabatan']) {
                    throw new \RuntimeException('Jabatan karyawan kosong.');
                }

                $karyawan = $this->persist($payload, $existing);
                $hasil[] = $this->toJson($karyawan);
            } catch (\Throwable $e) {
                // Satu baris rusak tidak boleh menghentikan seluruh import.
                // Baris yang valid tetap masuk database dan baris gagal
                // dikembalikan ke frontend agar dapat diperbaiki.
                report($e);
                $gagal[] = [
                    'index' => $index,
                    'row' => $nomorBaris,
                    'nik' => $nik,
                    'nama' => $nama,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $pesan = count($hasil) . ' data karyawan berhasil diimport';
        if ($gagal) {
            $pesan .= ', ' . count($gagal) . ' data gagal.';
        }

        \App\Models\ActivityLog::catat(
            'Import karyawan',
            $pesan . ' dari Excel.'
        );

        return response()->json([
            'ok' => true,
            'imported' => $hasil,
            'failed' => $gagal,
            'summary' => [
                'total' => count($rows),
                'success' => count($hasil),
                'failed' => count($gagal),
            ],
        ]);
    }
}
