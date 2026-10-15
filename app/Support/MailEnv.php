<?php

namespace App\Support;

use Dotenv\Dotenv;

/**
 * Membaca & menulis pengaturan email di file .env
 * (MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS).
 *
 * Dipakai menu Pengaturan supaya mengganti "email pengirim" benar-benar
 * mengganti akun SMTP yang dipakai mengirim slip, bukan sekadar mengubah
 * tulisan "From" (Gmail akan tetap memakai akun MAIL_USERNAME).
 */
class MailEnv
{
    public static function path(): string
    {
        return base_path('.env');
    }

    /** @return array{username:?string,password:?string,from:?string} */
    public static function baca(): array
    {
        $v = [];
        if (is_file(self::path())) {
            try {
                $v = Dotenv::parse((string) file_get_contents(self::path()));
            } catch (\Throwable $e) {
                $v = [];
            }
        }

        return [
            'username' => $v['MAIL_USERNAME'] ?? config('mail.mailers.smtp.username'),
            'password' => $v['MAIL_PASSWORD'] ?? config('mail.mailers.smtp.password'),
            'from' => $v['MAIL_FROM_ADDRESS'] ?? config('mail.from.address'),
        ];
    }

    /**
     * Tulis/ubah kunci di .env. Baris lain tidak disentuh.
     * @param array<string,string> $nilai
     */
    public static function tulis(array $nilai): void
    {
        $path = self::path();
        if (! is_file($path) || ! is_writable($path)) {
            throw new \RuntimeException('File .env tidak ditemukan atau tidak bisa ditulis. Ubah izin file .env lalu coba lagi.');
        }

        $isi = (string) file_get_contents($path);
        $eol = str_contains($isi, "\r\n") ? "\r\n" : "\n";

        foreach ($nilai as $kunci => $val) {
            $baris = $kunci . '=' . self::format($val);
            $pola = '/^[ \t]*' . preg_quote($kunci, '/') . '[ \t]*=.*$/m';

            if (preg_match($pola, $isi)) {
                // callback agar karakter $ dan \ pada nilai tidak dianggap referensi regex
                $isi = preg_replace_callback($pola, fn() => $baris, $isi, 1);
            } else {
                $isi = rtrim($isi, "\r\n") . $eol . $baris . $eol;
            }
        }

        if (file_put_contents($path, $isi, LOCK_EX) === false) {
            throw new \RuntimeException('Gagal menulis file .env.');
        }

        // Config yang sudah di-cache akan mengabaikan .env, hapus cache-nya.
        $cache = app()->getCachedConfigPath();
        if (is_file($cache)) {
            @unlink($cache);
        }
    }

    protected static function format(string $val): string
    {
        if (preg_match('/^[A-Za-z0-9._@+\-]+$/', $val)) {
            return $val;
        }

        return '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $val) . '"';
    }

    /**
     * Pakai nilai .env terbaru pada request yang sedang berjalan, walaupun
     * server (mis. php artisan serve) belum di-restart setelah .env diubah.
     */
    public static function terapkan(): void
    {
        $v = self::baca();

        if (! empty($v['username'])) {
            config(['mail.mailers.smtp.username' => $v['username']]);
        }
        if (! empty($v['password'])) {
            config(['mail.mailers.smtp.password' => $v['password']]);
        }
        if (! empty($v['from'])) {
            config(['mail.from.address' => $v['from']]);
        }

        try {
            app('mail.manager')->purge();
        } catch (\Throwable $e) {
            // abaikan: mailer akan dibuat ulang bila perlu
        }
    }
}
