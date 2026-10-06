<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

/**
 * EmailTemplateController
 *
 * Menyambungkan public/js/template-email.js ke tabel email_templates (gajii).
 * Template dipakai App\Mail\SlipGajiMail saat mengirim slip gaji.
 */
class EmailTemplateController extends Controller
{
    protected function toJson(EmailTemplate $t): array
    {
        return [
            'subject' => $t->subject,
            'body' => $t->body,
            'updatedAt' => optional($t->updated_at)->format('d M Y H:i'),
            'placeholders' => EmailTemplate::PLACEHOLDERS,
            'default' => [
                'subject' => EmailTemplate::DEFAULT_SUBJECT,
                'body' => EmailTemplate::DEFAULT_BODY,
            ],
        ];
    }

    public function show()
    {
        return response()->json($this->toJson(EmailTemplate::slipGaji()));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
        ], [
            'subject.required' => 'Subject email wajib diisi.',
            'subject.max' => 'Subject email maksimal 200 karakter.',
            'body.required' => 'Isi email wajib diisi.',
            'body.max' => 'Isi email maksimal 10.000 karakter.',
        ]);

        $template = EmailTemplate::slipGaji();
        $template->update([
            'subject' => trim($data['subject']),
            'body' => str_replace(["\r\n", "\r"], "\n", trim($data['body'])),
        ]);

        ActivityLog::catat('Ubah template email', 'Memperbarui template email slip gaji.');

        return response()->json($this->toJson($template->fresh()));
    }
}
