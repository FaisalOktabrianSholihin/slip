<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Role default sesuai opsi pada data-user.blade.php
 * (<option value="superadmin">Superadmin</option>, <option value="admin">Admin</option>).
 * Daftar permission di bawah hanya contoh awal; sesuaikan/lengkapi
 * lewat halaman role-management.blade.php setelah aplikasi berjalan.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['slug' => 'superadmin'], [
            'nama' => 'Superadmin',
            'deskripsi' => 'Akses penuh ke seluruh fitur aplikasi.',
            'permissions' => [
                'karyawan.view', 'karyawan.create', 'karyawan.edit', 'karyawan.delete',
                'slip.import', 'slip.kirim', 'riwayat.view',
                'user.manage', 'role.manage', 'pengaturan.manage', 'log.view',
            ],
        ]);

        Role::firstOrCreate(['slug' => 'admin'], [
            'nama' => 'Admin',
            'deskripsi' => 'Mengelola data karyawan dan pengiriman slip gaji.',
            'permissions' => [
                'karyawan.view', 'karyawan.create', 'karyawan.edit',
                'slip.import', 'slip.kirim', 'riwayat.view',
            ],
        ]);
    }
}
