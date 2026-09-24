<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: KaryawanPribadi
 * Tabel: karyawan_pribadi (database: db_induk / db_indukk)
 */
class KaryawanPribadi extends Model
{
    use HasFactory;

    protected $connection = 'db_induk';

    protected $table = 'karyawan_pribadi';

    protected $fillable = [
        'karyawan_id',
        'agama',
        'suku',
        'tempat_lahir',
        'status_nikah',
        'hobby',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }
}
