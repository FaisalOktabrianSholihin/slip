<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: KaryawanAnak
 * Tabel: karyawan_anak (database: db_induk / db_indukk)
 */
class KaryawanAnak extends Model
{
    use HasFactory;

    protected $connection = 'db_induk';

    protected $table = 'karyawan_anak';

    protected $fillable = [
        'karyawan_id',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'pekerjaan',
        'jenis_kelamin',
        'pendidikan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }
}
