<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Konversi izin lama (per menu, mis. "master-karyawan", atau kunci seeder
 * "karyawan.view") ke format baru "modul.aksi". Role Superadmin otomatis
 * mendapat seluruh izin, termasuk menu baru (Template Email).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $lama = json_decode($role->permissions ?? '[]', true) ?: [];

            $baru = $role->slug === 'superadmin'
                ? Permissions::all()
                : Permissions::normalize($lama);

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($baru),
            ]);
        }
    }

    public function down(): void
    {
        // Tidak dapat dikembalikan ke format lama.
    }
};
