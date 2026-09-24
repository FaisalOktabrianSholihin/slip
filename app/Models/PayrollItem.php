<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: PayrollItem
 * Tabel: payroll_items (database default: gajii)
 * Baris rincian Tambahan / Potongan untuk satu Payroll.
 */
class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'payroll_component_id',
        'tipe',
        'nama',
        'urutan',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}
