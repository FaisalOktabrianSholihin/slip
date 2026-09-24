<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: KaryawanBank
 * Tabel: karyawan_bank (database: db_induk / db_indukk)
 */
class KaryawanBank extends Model
{
    use HasFactory;

    protected $connection = 'db_induk';

    protected $table = 'karyawan_bank';

    protected $fillable = [
        'karyawan_id',
        'bank',
        'no_rekening',
        'atas_nama',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }
}
