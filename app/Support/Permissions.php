<?php

namespace App\Support;

/**
 * Katalog hak akses aplikasi (satu-satunya sumber kebenaran).
 *
 * Format kunci: "<modul>.<aksi>", mis. "master-karyawan.view".
 * Halaman Role Management memuat katalog ini lewat GET /api/permissions
 * dan menampilkannya sebagai matriks Lihat / Tambah / Ubah / Hapus / dst.
 */
class Permissions
{
    /** Label aksi yang dipakai di seluruh modul. */
    public const ACTION_LABELS = [
        'view' => 'Lihat',
        'create' => 'Tambah',
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'import' => 'Import',
        'send' => 'Kirim',
    ];

    /** Modul => [label, aksi yang tersedia]. */
    public static function modules(): array
    {
        return [
            'dashboard' => ['Dashboard', ['view']],
            'master-user' => ['Master Data - User', ['view', 'create', 'edit', 'delete']],
            'master-divisi' => ['Master Data - Divisi', ['view', 'create', 'edit', 'delete']],
            'master-jabatan' => ['Master Data - Jabatan', ['view', 'create', 'edit', 'delete']],
            'master-karyawan' => ['Master Data - Karyawan', ['view', 'create', 'edit', 'delete', 'import']],
            'kirim-slip' => ['Kirim Slip Gaji', ['view', 'import', 'send']],
            'detail-riwayat' => ['Detail Riwayat', ['view', 'delete']],
            'template-email' => ['Template Email', ['view', 'edit']],
            'role-management' => ['Role Management', ['view', 'create', 'edit', 'delete']],
            'setting' => ['Pengaturan', ['view', 'edit']],
            'log-aktivitas' => ['Log Aktivitas', ['view', 'delete']],
        ];
    }

    /** Bentuk katalog untuk frontend. */
    public static function catalog(): array
    {
        $out = [];
        foreach (self::modules() as $key => [$label, $actions]) {
            $out[] = [
                'key' => $key,
                'label' => $label,
                'actions' => array_map(
                    fn(string $a) => ['key' => $a, 'label' => self::ACTION_LABELS[$a]],
                    $actions
                ),
            ];
        }

        return $out;
    }

    /** Semua kunci "modul.aksi" yang sah. */
    public static function all(): array
    {
        $keys = [];
        foreach (self::modules() as $module => [, $actions]) {
            foreach ($actions as $a) {
                $keys[] = "{$module}.{$a}";
            }
        }

        return $keys;
    }

    /** Semua kunci untuk satu modul. */
    public static function forModule(string $module): array
    {
        $def = self::modules()[$module] ?? null;

        return $def ? array_map(fn($a) => "{$module}.{$a}", $def[1]) : [];
    }

    /**
     * Kunci lama (seeder awal) => kunci baru.
     * "*.manage" berarti seluruh aksi modul tersebut.
     */
    private const LEGACY = [
        'karyawan.view' => ['master-karyawan.view'],
        'karyawan.create' => ['master-karyawan.create'],
        'karyawan.edit' => ['master-karyawan.edit'],
        'karyawan.delete' => ['master-karyawan.delete'],
        'slip.import' => ['kirim-slip.import'],
        'slip.kirim' => ['kirim-slip.send'],
        'riwayat.view' => ['detail-riwayat.view'],
        'user.manage' => ['master-user'],
        'role.manage' => ['role-management'],
        'pengaturan.manage' => ['setting'],
        'log.view' => ['log-aktivitas.view'],
    ];

    /**
     * Ubah izin versi lama menjadi versi baru:
     *  - kunci datar "master-karyawan" (izin menu lama) -> semua aksi modul itu
     *  - kunci seeder lama ("karyawan.view", "slip.kirim", ...) -> padanan barunya
     *  - aksi turunan (tambah/ubah/hapus/...) otomatis menyertakan "view"
     *  - kunci tak dikenal dibuang
     */
    public static function normalize(?array $keys): array
    {
        $valid = array_flip(self::all());
        $result = [];

        foreach ($keys ?? [] as $key) {
            if (! is_string($key)) {
                continue;
            }

            $expanded = self::LEGACY[$key] ?? [$key];

            foreach ($expanded as $k) {
                if (isset(self::modules()[$k])) {
                    foreach (self::forModule($k) as $full) {
                        $result[$full] = true;
                    }
                } elseif (isset($valid[$k])) {
                    $result[$k] = true;
                }
            }
        }

        // Aksi selain "view" tanpa "view" tidak masuk akal: sertakan view-nya.
        foreach (array_keys($result) as $k) {
            [$module] = explode('.', $k, 2);
            if (isset($valid["{$module}.view"])) {
                $result["{$module}.view"] = true;
            }
        }

        // Urutkan mengikuti urutan katalog.
        return array_values(array_filter(self::all(), fn($k) => isset($result[$k])));
    }
}
