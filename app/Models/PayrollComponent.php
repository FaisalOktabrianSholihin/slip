<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: PayrollComponent
 * Tabel: payroll_components (database default: gajii)
 * Master komponen Tambahan / Potongan (maks. 20 per tipe, mengikuti
 * batas MAX_COMPONENTS di kirim-slip.js).
 */
class PayrollComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipe',
        'nama',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function scopeTambahan($query)
    {
        return $query->where('tipe', 'tambahan')->orderBy('urutan');
    }

    public function scopePotongan($query)
    {
        return $query->where('tipe', 'potongan')->orderBy('urutan');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
