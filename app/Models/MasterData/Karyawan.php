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
        return Attribute::get(fn () => $this->email ? 'email' : ($this->no_hp ? 'wa' : null));
    }
}
