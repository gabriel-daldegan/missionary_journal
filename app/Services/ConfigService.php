<?php

namespace App\Services;

use App\Constants\ConfigConstants;
use App\Models\Config;
use Exception;

class ConfigService
{
    public function isAdminSettingsEnabled()
    {
        return config('app.admin_settings.enabled', false);
    }

    public function loadConfigs()
    {
        if (! $this->isAdminSettingsEnabled()) {
            return;
        }

        $configs = cache()->many(ConfigConstants::OVERRIDABLE_CONFIGS);

        if ($this->allAreNull($configs)) {  // this is to re-cache the configs if they were cleared from the cache for any reason
            $this->exportAllConfigs();

            $configs = cache()->many(ConfigConstants::OVERRIDABLE_CONFIGS);
        }

        config($this->toKeyValueArray($configs));
    }

    public function set(string $key, $value): void
    {
        if (! in_array($key, ConfigConstants::OVERRIDABLE_CONFIGS)) {
            throw new Exception("Config key $key is not overridable");
        }

        Config::set($key, $value);

        cache()->forever($key, $value);

        config([$key => $value]);
    }

    public function exportAllConfigs(): void
    {
        if (! $this->isAdminSettingsEnabled()) {
            return;
        }

        $configs = Config::getAll();

        foreach ($configs as $key => $value) {
            cache()->forever($key, $value);
        }
    }

    /**
     * This is a one-time operation to encrypt sensitive configs to migrate non-encrypted sensitive configs to be encrypted.
     */
    public function encryptSensitiveConfigs()
    {
        foreach (ConfigConstants::ENCRYPTED_CONFIGS as $key) {
            $value = Config::get($key);
            if ($value) {
                Config::set($key, $value);
            }
        }
    }

    public function get(string $key, ?string $default = null): string|array|null
    {
        try {
            return Config::get($key) ?? config($key) ?? $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    private function toKeyValueArray($configs): array
    {
        $result = [];
        foreach ($configs as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function allAreNull(array $configs): bool
    {
        foreach ($configs as $key => $value) {
            if (! is_null($value)) {
                return false;
            }
        }

        return true;
    }
}
