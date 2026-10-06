<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Model: EmailTemplate
 * Tabel: email_templates (database default: gajii)
 *
 * Redaksi email slip gaji yang bisa diubah dari menu "Template Email".
 * Teks boleh memakai placeholder dalam kurung kurawal, mis. {periode}.
 */
class EmailTemplate extends Model
{
    public const KEY_SLIP = 'slip_gaji';

    protected $fillable = ['key', 'subject', 'body'];

    /** Placeholder yang tersedia => keterangan (ditampilkan di halaman template). */
    public const PLACEHOLDERS = [
        'nama' => 'Nama karyawan',
        'nik' => 'NIK karyawan',
        'divisi' => 'Divisi karyawan',
        'periode' => 'Periode gaji, mis. September 2026',
    ];

    public const DEFAULT_SUBJECT = 'Slip Gaji {periode}';

    public const DEFAULT_BODY = <<<'TXT'
Yth. Bapak/Ibu Karyawan PT Mitratani Dua Tujuh,

Dengan hormat,

Dalam rangka mendukung penerapan administrasi yang lebih efektif, efisien, dan ramah lingkungan melalui sistem paperless, PT Mitratani Dua Tujuh menyampaikan e-Slip Gaji Bapak/Ibu secara digital melalui email.

Terlampir e-Slip Gaji untuk periode pembayaran gaji bulan {periode} yang dapat diakses dalam bentuk file PDF.

Demi menjaga kerahasiaan dan keamanan informasi penghasilan karyawan, file e-Slip Gaji tersebut dilindungi dengan password berupa tanggal lahir Bapak/Ibu dengan format:

DDMMYYYY
Contoh: tanggal lahir 15 Agustus 1990 → password 15081990

Mohon Bapak/Ibu menjaga kerahasiaan e-Slip Gaji dan password tersebut serta tidak meneruskan file kepada pihak yang tidak berkepentingan.

Demikian disampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.

Hormat kami,

Divisi Sumber Daya Manusia
PT Mitratani Dua Tujuh
TXT;

    /** Ambil (atau buat dengan isi bawaan) template slip gaji. */
    public static function slipGaji(): self
    {
        return static::firstOrCreate(['key' => self::KEY_SLIP], [
            'subject' => self::DEFAULT_SUBJECT,
            'body' => self::DEFAULT_BODY,
        ]);
    }

    /** "2026-09-01" -> "September 2026" (nama bulan Indonesia, tidak bergantung locale). */
    public static function labelPeriode(?CarbonInterface $tanggal): string
    {
        if (! $tanggal) {
            return '';
        }

        $bulan = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];

        return $bulan[(int) $tanggal->format('n')] . ' ' . $tanggal->format('Y');
    }

    /** Ganti {placeholder} dengan nilainya; placeholder tak dikenal dibiarkan apa adanya. */
    public static function isi(string $teks, array $vars): string
    {
        $pasangan = [];
        foreach ($vars as $k => $v) {
            $pasangan['{' . $k . '}'] = (string) $v;
        }

        return strtr($teks, $pasangan);
    }

    public function subjectFor(array $vars): string
    {
        // Subject harus satu baris.
        return trim(preg_replace('/\s+/', ' ', self::isi($this->subject, $vars)));
    }

    public function bodyFor(array $vars): string
    {
        return str_replace(["\r\n", "\r"], "\n", self::isi($this->body, $vars));
    }

    /** Teks -> HTML aman: paragraf dipisah baris kosong, baris tunggal jadi <br>. */
    public static function keHtml(string $teks): string
    {
        $paragraf = preg_split("/\n{2,}/", trim($teks));

        return collect($paragraf)
            ->map(fn($p) => '<p style="margin:0 0 16px;">' . nl2br(e(trim($p))) . '</p>')
            ->implode("\n");
    }
}
