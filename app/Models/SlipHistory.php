<?php

namespace App\Models;

use App\Models\MasterData\Karyawan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: SlipHistory
 * Tabel: slip_histories (database default: gajii)
 */
class SlipHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'karyawan_nik',
        'nama',
        'email',
        'wa',
        'divisi',
        'channel',
        'file',
        'status',
        'keterangan',
        'dikirim_oleh',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /** Relasi lintas database ke db_indukk (lihat catatan di Payroll::karyawan()). */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nik', 'nik');
    }

    public function dikirimOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikirim_oleh');
    }

    public function scopeBerhasil($query)
    {
        return $query->where('status', 'Berhasil');
    }

    public function scopeGagal($query)
    {
        return $query->where('status', 'Gagal');
    }
}
