<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Seed default lookup values into system_settings.
     */
    public function run(): void
    {
        foreach (SystemSetting::DEFAULTS as $key => $values) {
            // Only create if not already present (safe for re-runs)
            if (!SystemSetting::find($key)) {
                SystemSetting::setList($key, $values);
            }
        }
    }
}
