<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

/**
 * Role default sesuai opsi pada data-user.blade.php
 * (<option value="superadmin">Superadmin</option>, <option value="admin">Admin</option>).
 * Hak akses memakai format "modul.aksi" dari App\Support\Permissions
 * dan bisa disesuaikan lewat halaman Role Management.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['slug' => 'superadmin'], [
            'nama' => 'Superadmin',
            'deskripsi' => 'Akses penuh ke seluruh fitur aplikasi.',
            'permissions' => Permissions::all(),
        ]);

        // Admin: semua menu operasional (tanpa Role Management, Pengaturan, Log Aktivitas).
        $admin = [];
        foreach (
            [
                'dashboard',
                'demografi',
                'master-user',
                'master-divisi',
                'master-jabatan',
                'master-karyawan',
                'kirim-slip',
                'detail-riwayat',
                'template-email'
            ] as $modul
        ) {
            $admin = array_merge($admin, Permissions::forModule($modul));
        }

        Role::updateOrCreate(['slug' => 'admin'], [
            'nama' => 'Admin',
            'deskripsi' => 'Mengelola data karyawan dan pengiriman slip gaji.',
            'permissions' => $admin,
        ]);
    }
}
