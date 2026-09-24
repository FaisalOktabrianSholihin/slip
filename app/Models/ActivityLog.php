<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: ActivityLog
 * Tabel: activity_logs (database default: gajii)
 */
class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'aksi',
        'detail',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Helper singkat, padanan SlipStore.addActivity() di frontend. */
    public static function catat(string $aksi, ?string $detail = null, array $meta = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'detail' => $detail,
            'meta' => $meta ?: null,
        ]);
    }
}
