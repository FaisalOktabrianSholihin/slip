<?php

namespace App\Models;

use App\Models\MasterData\Karyawan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Payroll
 * Tabel: payrolls (database default: gajii)
 *
 * Model ini hidup di koneksi 'mysql' (database gajii), sedangkan
 * data karyawan hidup di koneksi 'db_induk' (database db_indukk).
 * Karena keduanya adalah SKEMA/DATABASE FISIK yang berbeda, Laravel
 * tidak bisa membuat JOIN SQL biasa atau foreign key lintas database
 * secara otomatis. Relasi `karyawan()` di bawah tetap bisa dipakai
 * ($payroll->karyawan) karena Eloquent akan menjalankan QUERY KEDUA
 * secara terpisah ke koneksi db_induk (bukan JOIN satu query), lalu
 * menyimpan hasilnya sebagai objek Karyawan pada instance ini.
 */
class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_nik',
        'nama_snapshot',
        'divisi_snapshot',
        'jabatan_snapshot',
        'golongan_snapshot',
        'rekening_snapshot',
        'periode',
        'gaji_pokok',
        'total_tambahan',
        'total_potongan',
        'gaji_bersih',
        'status',
        'channel',
        'file_slip',
        'dikirim_pada',
        'dibuat_oleh',
        'catatan',
        'component_config',
    ];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'dikirim_pada' => 'datetime',
            'gaji_pokok' => 'decimal:2',
            'total_tambahan' => 'decimal:2',
            'total_potongan' => 'decimal:2',
            'gaji_bersih' => 'decimal:2',
            'component_config' => 'array',
        ];
    }

    /* =========================================================
     * Relasi lintas database (contoh belongsTo manual)
     * ========================================================= */

    /**
     * Karyawan pemilik payroll ini, diambil dari db_indukk.
     * foreignKey  = kolom di tabel LOKAL (payrolls.karyawan_nik)
     * ownerKey    = kolom di tabel TUJUAN (karyawans.nik, di db_induk)
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nik', 'nik');
    }

    /* =========================================================
     * Relasi biasa, masih di database gajii
     * ========================================================= */

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function tambahan(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->where('tipe', 'tambahan')->orderBy('urutan');
    }

    public function potongan(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->where('tipe', 'potongan')->orderBy('urutan');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(SlipHistory::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Hitung ulang total_tambahan, total_potongan, gaji_bersih
     * dari relasi items(). Dipanggil setelah item disimpan.
     */
    public function hitungUlangTotal(): void
    {
        $this->loadMissing('items');
        $totalTambahan = $this->items->where('tipe', 'tambahan')->sum('jumlah');
        $totalPotongan = $this->items->where('tipe', 'potongan')->sum('jumlah');

        $this->update([
            'total_tambahan' => $totalTambahan,
            'total_potongan' => $totalPotongan,
            'gaji_bersih' => $this->gaji_pokok + $totalTambahan - $totalPotongan,
        ]);
    }
}
