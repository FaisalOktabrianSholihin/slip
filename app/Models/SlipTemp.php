<?php

namespace App\Models;

use App\Models\MasterData\Karyawan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: SlipTemp
 * Tabel: slip_temps (database default: gajii)
 * File slip yang diunggah manual (PDF/PNG/JPG), menunggu dikirim.
 */
class SlipTemp extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_nik',
        'file',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nik', 'nik');
    }
}
