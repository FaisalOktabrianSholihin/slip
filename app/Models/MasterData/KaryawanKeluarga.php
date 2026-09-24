<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: KaryawanKeluarga
 * Tabel: karyawan_keluarga (database: db_induk / db_indukk)
 */
class KaryawanKeluarga extends Model
{
    use HasFactory;

    protected $connection = 'db_induk';

    protected $table = 'karyawan_keluarga';

    protected $fillable = [
        'karyawan_id',
        'nama_ayah',
        'nama_ibu',
        'nama_pasangan',
        'tempat_lahir_pasangan',
        'tanggal_lahir_pasangan',
        'pekerjaan_pasangan',
        'jenis_kelamin_pasangan',
        'pendidikan_pasangan',
        'jaminan_kesehatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir_pasangan' => 'date',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }
}
