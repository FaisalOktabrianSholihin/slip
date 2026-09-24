<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: KaryawanPendidikan
 * Tabel: karyawan_pendidikan (database: db_induk / db_indukk)
 */
class KaryawanPendidikan extends Model
{
    use HasFactory;

    protected $connection = 'db_induk';

    protected $table = 'karyawan_pendidikan';

    protected $fillable = [
        'karyawan_id',
        'sd',
        'sltp',
        'slta',
        'pt',
        'pendidikan_terakhir',
        'jurusan',
        'tahun_masuk',
        'tahun_keluar',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }
}
