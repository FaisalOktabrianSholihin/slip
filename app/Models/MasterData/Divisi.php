<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Divisi
 * Tabel: divisis (database: db_induk / db_indukk)
 */
class Divisi extends Model
{
    use HasFactory;

    /**
     * Koneksi database kedua (Data Master).
     */
    protected $connection = 'db_induk';

    protected $table = 'divisis';

    protected $fillable = [
        'nama_divisi',
    ];

    /**
     * Satu divisi punya banyak karyawan.
     */
    public function karyawans(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'divisi_id');
    }
}
