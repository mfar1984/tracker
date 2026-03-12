<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    const CACHE_KEY = 'app_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key
     * Returns from cache if available, otherwise loads from database
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    /**
     * Get a decrypted setting value
     * Automatically decrypts if the setting is marked as encrypted
     */
    public function getDecrypted(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * Set a setting value
     * Automatically encrypts if $encrypted is true
     */
    public function set(string $key, mixed $value, bool $encrypted = false): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'encrypted' => $encrypted]
        );
        
        $this->clearCache();
    }

    /**
     * Get all settings as key-value array
     * Cached for performance
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = Setting::all();
            $result = [];
            
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }
            
            return $result;
        });
    }

    /**
     * Clear the settings cache
     * Called after any setting update
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Bulk update settings
     * Used by admin settings form
     */
    public function bulkUpdate(array $settings): void
    {
        foreach ($settings as $key => $value) {
            // Determine if this setting should be encrypted
            $encrypted = in_array($key, ['smtp_password', 'infobip_api_key']);
            
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'encrypted' => $encrypted]
            );
        }
        
        $this->clearCache();
    }
}
