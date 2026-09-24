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
        if ($s === '') {
            return null;
        }
        if (str_contains($s, 'pkwt') || str_contains($s, 'kontrak')) {
            return 'pkwt';
        }
        if (str_contains($s, 'penugasan') || str_contains($s, 'tugas')) {
            return 'penugasan';
        }
        if (str_contains($s, 'honor') || str_contains($s, 'harian') || preg_match('/^hl\b/', $s)) {
            return 'honorer';
        }
        if (str_contains($s, 'tetap')) {
            return 'tetap';
        }

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
