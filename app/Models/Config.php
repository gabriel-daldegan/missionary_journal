<?php

namespace App\Models;

use App\Constants\ConfigConstants;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key): ?string
    {
        $config = self::where('key', $key)->first();
        if ($config) {
            if (in_array($key, ConfigConstants::ENCRYPTED_CONFIGS)) {
                return Config::decryptValue($config->value);
            }

            return $config->value;
        }

        return null;
    }

    public static function getAll(): array
    {
        $configs = self::all();

        $result = [];
        foreach ($configs as $config) {
            if (in_array($config->key, ConfigConstants::ENCRYPTED_CONFIGS)) {
                $result[$config->key] = Config::decryptValue($config->value);
            } else {
                $result[$config->key] = $config->value;
            }
        }

        return $result;
    }

    public static function set(string $key, ?string $value): void
    {
        if (in_array($key, ConfigConstants::ENCRYPTED_CONFIGS)) {
            $value = Config::encryptValue($value);
        }

        $config = self::where('key', $key)->first();
        if ($config) {
            $config->value = $value;
            $config->save();
        } else {
            self::create([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    private static function decryptValue(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (Exception $e) {
            return $value;
        }
    }

    private static function encryptValue(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return encrypt($value);
        } catch (Exception $e) {
            return $value;
        }
    }
}
