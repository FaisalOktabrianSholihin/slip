<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Jabatan
 * Tabel: jabatans (database: db_induk / db_indukk)
 */
class Jabatan extends Model
{
    use HasFactory;

    protected $connection = 'db_induk';

    protected $table = 'jabatans';

    protected $fillable = [
        'nama_jabatan',
    ];

    /**
     * Satu jabatan punya banyak karyawan.
     */
    public function karyawans(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'jabatan_id');
    }
}
