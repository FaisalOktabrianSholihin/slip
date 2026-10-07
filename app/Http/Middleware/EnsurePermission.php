<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * EnsurePermission
 *
 * Menolak akses (403) jika role user yang login tidak punya izin "modul.aksi"
 * yang diminta (lihat App\Support\Permissions). Dipasang per-route dengan
 * ->middleware('permission:master-karyawan.view'), dst.
 *
 * Request JSON (dipanggil dari public/js/*.js lewat Api.*) menerima 403 JSON
 * biasa. Request halaman penuh menerima halaman 403 yang menjelaskan menu
 * mana yang tidak diizinkan, supaya pindah menu via link lama tidak terasa
 * seperti error polos.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                abort(403, 'Anda tidak memiliki hak akses untuk melakukan aksi ini.');
            }

            abort(403, 'Role Anda ('.($user?->role?->nama ?? '-').') tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
