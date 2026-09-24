<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Baris pengaturan default (single-row config), padanan KEY_SETTINGS
 * di public/js/slip-common.js.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(['id' => 1], [
            'retention_months' => 1,
            'email_enabled' => true,
            'wa_enabled' => true,
        ]);
    }
}
