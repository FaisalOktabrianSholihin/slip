<?php

namespace App\Mail;

use App\Models\EmailTemplate;
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
 * Konfigurasi SMTP diambil otomatis oleh Laravel dari .env.
 *
 * Subject & isi email berasal dari menu "Template Email" (tabel email_templates).
 * Lampiran PDF sudah DIENKRIPSI dengan password = tanggal lahir karyawan (DDMMYYYY)
 * oleh SlipController@buatPdfBerpassword, lalu dilampirkan langsung dari memori
 * (tidak ada file terenkripsi yang tersimpan di disk).
 *
 * Catatan: Mailable ini sengaja sinkron (bukan ShouldQueue) karena membawa isi PDF.
 */
class SlipGajiMail extends Mailable
{
    use Queueable, SerializesModels;

    protected EmailTemplate $template;

    public function __construct(
        public Payroll $payroll,
        public string $namaKaryawan,
        public string $pdfBinary,
        public string $pdfName,
    ) {
        $this->template = EmailTemplate::slipGaji();
    }

    /** Nilai untuk placeholder {nama}, {nik}, {divisi}, {periode}. */
    protected function variabel(): array
    {
        return [
            'nama' => $this->namaKaryawan,
            'nik' => $this->payroll->karyawan_nik,
            'divisi' => (string) $this->payroll->divisi_snapshot,
            'periode' => EmailTemplate::labelPeriode($this->payroll->periode),
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->subjectFor($this->variabel()),
        );
    }

    public function content(): Content
    {
        $teks = $this->template->bodyFor($this->variabel());

        return new Content(
            view: 'emails.slip-gaji',
            text: 'emails.slip-gaji-text',
            with: [
                'bodyHtml' => EmailTemplate::keHtml($teks),
                'bodyText' => $teks,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->pdfBinary, $this->pdfName)
                ->withMime('application/pdf'),
        ];
    }
}
