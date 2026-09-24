<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: SlipGajiMail
 *
 * Dikirim ke field `email` pada tabel karyawans (database db_indukk).
 * Konfigurasi SMTP diambil otomatis oleh Laravel dari .env
 * (MAIL_MAILER=smtp, MAIL_HOST=smtp.gmail.com, dst) lewat config/mail.php,
 * jadi Mailable ini tidak perlu menyebut kredensial SMTP secara eksplisit.
 *
 * Menggunakan ShouldQueue disarankan untuk produksi (supaya pengiriman
 * massal / "Kirim Semua Email" di preview-slip.blade.php tidak
 * memblokir request), tapi di sini dibiarkan sinkron agar mudah diuji.
 */
class SlipGajiMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payroll $payroll,
        public string $namaKaryawan,
        public ?string $pathFileSlip = null,
    ) {}

    public function envelope(): Envelope
    {
        $periode = $this->payroll->periode->translatedFormat('F Y');

        return new Envelope(
            subject: "Slip Gaji {$periode} - {$this->namaKaryawan}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.slip-gaji',
            with: [
                'payroll' => $this->payroll,
                'namaKaryawan' => $this->namaKaryawan,
            ],
        );
    }

    /**
     * Lampirkan file slip (PDF/PNG/JPG) hasil generate, jika tersedia.
     * $pathFileSlip diharapkan berupa path absolut di storage, mis.
     * storage_path('app/private/slip/'.$this->payroll->file_slip).
     */
    public function attachments(): array
    {
        if (! $this->pathFileSlip || ! is_file($this->pathFileSlip)) {
            return [];
        }

        return [
            Attachment::fromPath($this->pathFileSlip)
                ->as($this->payroll->file_slip ?? basename($this->pathFileSlip)),
        ];
    }
}
