<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'smtp_host', 'value' => 'indigo.herosite.pro', 'encrypted' => false],
            ['key' => 'smtp_username', 'value' => 'tracker@sibu.org.my', 'encrypted' => false],
            ['key' => 'smtp_password', 'value' => 'V6dzXEALS1Hq94RS', 'encrypted' => true],
            ['key' => 'smtp_port', 'value' => '465', 'encrypted' => false],
            ['key' => 'smtp_encryption', 'value' => 'ssl', 'encrypted' => false],
            ['key' => 'infobip_api_key', 'value' => '0e040fb935c4d8583278784bf3b26e5b-53d418fe-b571-4d5f-bed9-ddcab216f129', 'encrypted' => true],
            ['key' => 'infobip_base_url', 'value' => 'r4eee.api.infobip.com', 'encrypted' => false],
            ['key' => 'infobip_sender_number', 'value' => '+60123456789', 'encrypted' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully.');
    }
}
