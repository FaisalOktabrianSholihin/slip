<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: ImportJob
 * Tabel: import_jobs (database default: gajii)
 *
 * Antrian import gaji yang diproses bertahap (per batch kecil) sehingga tetap
 * berlanjut walaupun pengguna berpindah menu. Statusnya disimpan di server,
 * bukan di halaman browser.
 */
class ImportJob extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'hasil' => 'array',
            'dilihat' => 'boolean',
            'periode' => 'date',
            'locked_until' => 'datetime',
        ];
    }

    public function aktif(): bool
    {
        return $this->status === 'berjalan';
    }
}
