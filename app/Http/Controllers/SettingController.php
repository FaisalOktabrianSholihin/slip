<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Support\MailEnv;
use App\Support\Retensi;
use Illuminate\Http\Request;

/**
 * SettingController
 *
 * Menyambungkan public/js/pengaturan.js (lewat SlipStore.getSettings/
 * saveSettings di slip-common.js) ke tabel `settings` (gajii, single-row).
 */
class SettingController extends Controller
{
    protected function toJson(Setting $s): array
    {
        $mail = MailEnv::baca();

        return [
            'retentionMonths' => (int) $s->retention_months,
            'emailEnabled' => (bool) $s->email_enabled,
            'waEnabled' => (bool) $s->wa_enabled,
            'senderName' => $s->sender_name,
            // Sumber kebenaran email pengirim = file .env (MAIL_FROM_ADDRESS / MAIL_USERNAME).
            'senderEmail' => $mail['from'] ?: ($mail['username'] ?: $s->sender_email),
            'mailUsername' => $mail['username'],
            'senderPasswordSet' => ! empty($mail['password']),
            'dashboardKpis' => $s->dashboard_kpis ?? ['total','status','masaKerja5'],
            'componentConfig' => ['additions'=>$s->payroll_component_additions, 'deductions'=>$s->payroll_component_deductions],
        ];
    }

    public function show()
    {
        return $this->toJson(Setting::current());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'retentionMonths' => ['required', 'integer', 'min:1', 'max:60'],
            'emailEnabled' => ['required', 'boolean'],
            'waEnabled' => ['required', 'boolean'],
            'senderName' => ['required','string','max:100'],
            'senderEmail' => ['required','email','max:150'],
            'senderPassword' => ['nullable','string','max:100'],
            'dashboardKpis' => ['nullable','array','max:3'],
            'dashboardKpis.*' => ['in:total,status,masaKerja5'],
        ]);

        // --- Email pengirim: ikut mengubah .env (akun SMTP), bukan sekadar label ---
        $env = MailEnv::baca();
        $emailBaru = trim($data['senderEmail']);
        $password = preg_replace('/\s+/', '', (string) ($data['senderPassword'] ?? ''));
        $akunBerubah = strcasecmp((string) $env['username'], $emailBaru) !== 0;

        if ($akunBerubah && $password === '') {
            return response()->json([
                'message' => 'Email pengirim diganti, jadi password aplikasi (App Password) email baru juga wajib diisi agar bisa login SMTP.',
            ], 422);
        }

        $ubahEnv = ['MAIL_USERNAME' => $emailBaru, 'MAIL_FROM_ADDRESS' => $emailBaru];
        if ($password !== '') {
            $ubahEnv['MAIL_PASSWORD'] = $password;
        }
        if (strcasecmp((string) $env['from'], $emailBaru) !== 0 || $akunBerubah || $password !== '') {
            try {
                MailEnv::tulis($ubahEnv);
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            MailEnv::terapkan();
        }

        $setting = Setting::current();
        $setting->update([
            'retention_months' => $data['retentionMonths'],
            'email_enabled' => $data['emailEnabled'],
            'wa_enabled' => $data['waEnabled'],
            'sender_name' => $data['senderName'],
            'sender_email' => $data['senderEmail'],
            'dashboard_kpis' => $data['dashboardKpis'] ?? ['total','status','masaKerja5'],
        ]);

        ActivityLog::catat(
            'Ubah pengaturan',
            "Mengubah retensi slip menjadi {$setting->retention_months} bulan; ".
            'Email '.($setting->email_enabled ? 'aktif' : 'nonaktif').'; '.
            'WhatsApp '.($setting->wa_enabled ? 'aktif' : 'nonaktif').'; '.
            'Email pengirim '.$emailBaru.'.'
        );

        return response()->json($this->toJson($setting));
    }

    /** Terapkan retensi sekarang (hapus riwayat & payroll yang melewati periode retensi). */
    public function terapkanRetensi()
    {
        return response()->json(Retensi::jalankan(null, 'manual'));
    }
}
