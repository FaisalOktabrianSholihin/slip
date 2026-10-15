<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Antrian pengiriman slip (email/WA) yang diproses bertahap di latar belakang.
 * Tabel: send_jobs
 */
class SendJob extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'hasil' => 'array',
            'dilihat' => 'boolean',
            'locked_until' => 'datetime',
        ];
    }
}
