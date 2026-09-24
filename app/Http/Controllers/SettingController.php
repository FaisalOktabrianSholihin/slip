<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
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
        return [
            'retentionMonths' => $s->retention_months,
            'emailEnabled' => (bool) $s->email_enabled,
            'waEnabled' => (bool) $s->wa_enabled,
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
        ]);

        $setting = Setting::current();
        $setting->update([
            'retention_months' => $data['retentionMonths'],
            'email_enabled' => $data['emailEnabled'],
            'wa_enabled' => $data['waEnabled'],
        ]);

        ActivityLog::catat(
            'Ubah pengaturan',
            "Mengubah retensi slip menjadi {$setting->retention_months} bulan; ".
            'Email '.($setting->email_enabled ? 'aktif' : 'nonaktif').'; '.
            'WhatsApp '.($setting->wa_enabled ? 'aktif' : 'nonaktif').'.'
        );

        return response()->json($this->toJson($setting));
    }
}
