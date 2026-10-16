<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model: Karyawan
 * Tabel: karyawans (database: db_induk / db_indukk)
 *
 * Model inti data master kepegawaian. Field email di tabel ini adalah
 * sumber utama pengiriman slip gaji (lihat App\Mail\SlipGajiMail).
 */
class Karyawan extends Model
{
    use HasFactory;

    protected $connection = 'db_induk';

    protected $table = 'karyawans';

    protected $fillable = [
        'nik',
        'no_absen',
        'nama',
        'alamat',
        'nama_panggilan',
        'email',
        'no_hp',
        'no_ktp',
        'jabatan_id',
        'divisi_id',
        'status_pegawai',
        'status_aktif',
        'golongan',
        'tanggal_masuk',
        'tanggal_pengangkatan',
        'tanggal_pensiun',
        'masa_kerja',
        'jatah_cuti',
        'sisa_cuti',
        'jenis_kelamin',
        'tanggal_lahir',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_pengangkatan' => 'date',
            'tanggal_pensiun' => 'date',
            'tanggal_lahir' => 'date',
            'status_aktif' => 'boolean',
            'jatah_cuti' => 'integer',
            'sisa_cuti' => 'integer',
        ];
    }

    /**
     * Samakan penulisan status pegawai dari Excel/form ke kode baku:
     * tetap | pkwt | honorer | penugasan. Mis. "Kontrak" -> pkwt,
     * "Tetap" -> tetap, "HL" / "Harian Lepas" -> honorer.
     * Mengembalikan null bila tidak dikenali.
     */
    public static function normalisasiStatus(?string $nilai): ?string
    {
        $s = mb_strtolower(trim((string) $nilai));
        if ($s === '') return null;
        $compact = preg_replace('/[^a-z0-9]+/', '', $s);
        if (str_contains($compact, 'pkwthl') || str_contains($compact, 'pkwt-hl')) return 'pkwt_hl';
        if (str_contains($compact, 'pkwtos') || str_contains($compact, 'pkwt-os')) return 'pkwt_os';
        if (str_contains($s, 'penugasan') || str_contains($s, 'tugas')) return 'penugasan';
        if (str_contains($s, 'pkwtt') || str_contains($s, 'tetap')) return 'pkwtt';
        if (str_contains($s, 'pkwt') || str_contains($s, 'kontrak')) return 'pkwt';
        if (str_contains($s, 'honor') || str_contains($s, 'harian') || preg_match('/^hl\b/', $s)) return 'pkwt_hl';
        return null;
    }

    /**
     * Samakan penulisan pendidikan terakhir ke: SD | SLTP | SLTA | DIII | S1 | S2 | S3.
     * Mis. "D3" -> DIII, "SMA"/"SMK" -> SLTA, "SMP" -> SLTP.
     */
    public static function normalisasiPendidikan(?string $nilai): ?string
    {
        $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $nilai));

        return match (true) {
            $s === '' => null,
            $s === 'SD' || $s === 'MI' => 'SD',
            in_array($s, ['SLTP', 'SMP', 'MTS'], true) => 'SLTP',
            in_array($s, ['SLTA', 'SMA', 'SMK', 'MA', 'SLTASMK'], true) => 'SLTA',
            in_array($s, ['D3', 'DIII', 'DIPLOMA3', 'DIPLOMA'], true) => 'DIII',
            in_array($s, ['S1', 'D4', 'SARJANA', 'STRATA1'], true) => 'S1',
            in_array($s, ['S2', 'MAGISTER', 'STRATA2'], true) => 'S2',
            in_array($s, ['S3', 'DOKTOR', 'STRATA3'], true) => 'S3',
            default => null,
        };
    }

    /**
     * Samakan golongan ke: I | II | III | IV (angka 1-4 dan awalan "Gol." ikut dikenali).
     * Nilai dengan sub-golongan (mis. "III/a") tidak dikenali -> null, sehingga
     * pemanggil mempertahankan nilai aslinya dan tidak ada data yang terbuang.
     */
    public static function normalisasiGolongan(?string $nilai): ?string
    {
        $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $nilai));
        $s = preg_replace('/^(GOLONGAN|GOL)/', '', $s);

        return match ($s) {
            'I', '1' => 'I',
            'II', '2' => 'II',
            'III', '3' => 'III',
            'IV', '4' => 'IV',
            default => null,
        };
    }

    /** Samakan agama ke: Islam | Kristen | Katolik | Hindu | Buddha | Konghucu. */
    public static function normalisasiAgama(?string $nilai): ?string
    {
        $s = strtolower(preg_replace('/[^A-Za-z]/', '', (string) $nilai));

        return match (true) {
            $s === '' => null,
            in_array($s, ['islam', 'muslim', 'moslem'], true) => 'Islam',
            in_array($s, ['kristen', 'protestan', 'kristenprotestan'], true) => 'Kristen',
            in_array($s, ['katolik', 'katholik', 'kristenkatolik', 'katolikroma'], true) => 'Katolik',
            $s === 'hindu' => 'Hindu',
            in_array($s, ['buddha', 'budha', 'buddhis', 'budhis'], true) => 'Buddha',
            in_array($s, ['konghucu', 'khonghucu', 'konghuchu', 'khonghuchu'], true) => 'Konghucu',
            default => null,
        };
    }

    /** Samakan status nikah ke: Belum Menikah | Menikah | Cerai Hidup | Cerai Mati. */
    public static function normalisasiStatusNikah(?string $nilai): ?string
    {
        $s = strtolower(preg_replace('/[^A-Za-z]/', '', (string) $nilai));

        return match (true) {
            $s === '' => null,
            in_array($s, ['belummenikah', 'belumkawin', 'belumnikah', 'tidakmenikah', 'tidakkawin', 'lajang', 'single', 'bm', 'tk'], true) => 'Belum Menikah',
            in_array($s, ['menikah', 'kawin', 'nikah', 'sudahmenikah', 'sudahkawin', 'married', 'k'], true) => 'Menikah',
            in_array($s, ['ceraihidup', 'ch'], true) => 'Cerai Hidup',
            in_array($s, ['ceraimati', 'cm'], true) => 'Cerai Mati',
            default => null,
        };
    }

    /** Samakan jenis kelamin (karyawan maupun pasangan) ke: L | P. */
    public static function normalisasiJenisKelamin(?string $nilai): ?string
    {
        $s = strtolower(preg_replace('/[^A-Za-z]/', '', (string) $nilai));

        return match (true) {
            in_array($s, ['l', 'lk', 'laki', 'lakilaki', 'pria', 'male', 'm'], true) => 'L',
            in_array($s, ['p', 'pr', 'perempuan', 'wanita', 'female', 'f'], true) => 'P',
            default => null,
        };
    }

    /** Samakan jaminan kesehatan ke: BPJS Kesehatan | Asuransi Swasta | Tidak Ada. */
    public static function normalisasiJaminanKesehatan(?string $nilai): ?string
    {
        $s = strtolower(trim((string) $nilai));

        return match (true) {
            $s === '' => null,
            (bool) preg_match('/^(tidak|belum|tanpa|none)/', $s) => 'Tidak Ada',
            str_contains($s, 'bpjs') => 'BPJS Kesehatan',
            str_contains($s, 'swasta') || str_contains($s, 'asuransi') => 'Asuransi Swasta',
            default => null,
        };
    }

    /* =========================================================
     * Relasi ke data master lain (masih di db_induk)
     * ========================================================= */

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(KaryawanAnak::class, 'karyawan_id');
    }

    public function bank(): HasMany
    {
        return $this->hasMany(KaryawanBank::class, 'karyawan_id');
    }

    public function keluarga(): HasOne
    {
        return $this->hasOne(KaryawanKeluarga::class, 'karyawan_id');
    }

    public function pendidikan(): HasOne
    {
        return $this->hasOne(KaryawanPendidikan::class, 'karyawan_id');
    }

    public function pribadi(): HasOne
    {
        return $this->hasOne(KaryawanPribadi::class, 'karyawan_id');
    }

    /* =========================================================
     * Relasi lintas database ke database `gajii` (transaksional).
     * Karena karyawans berada di koneksi 'db_induk' dan payrolls
     * berada di koneksi default ('mysql' / database gajii), relasi
     * ini TIDAK bisa memakai foreign key constraint fisik (beda
     * server database secara logika/skema). Relasi dibuat manual
     * berbasis kolom nik, dengan foreignKey di sisi lokal (Payroll)
     * dan ownerKey di sisi Karyawan.
     * ========================================================= */

    public function payrolls(): HasMany
    {
        return $this->hasMany(\App\Models\Payroll::class, 'karyawan_nik', 'nik');
    }

    public function riwayatSlip(): HasMany
    {
        return $this->hasMany(\App\Models\SlipHistory::class, 'karyawan_nik', 'nik');
    }

    /**
     * Accessor: apakah karyawan punya kontak yang valid untuk
     * pengiriman slip (email diprioritaskan, fallback WhatsApp).
     */
    protected function channelPengiriman(): Attribute
    {
        return Attribute::get(fn() => $this->email ? 'email' : ($this->no_hp ? 'wa' : null));
    }
}
