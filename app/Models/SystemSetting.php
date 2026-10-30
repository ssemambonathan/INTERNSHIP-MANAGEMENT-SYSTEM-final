<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    /** @var array<string, string|null>|null */
    protected static ?array $cache = null;

    /** @var array<string, string>|null */
    protected static ?array $types = null;

    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::allCached();

        if (! array_key_exists($key, $settings) || $settings[$key] === null) {
            return $default;
        }

        return self::castValue($settings[$key], self::typeFor($key));
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = self::query()->firstOrNew(['key' => $key]);
        $setting->value = is_bool($value) ? ($value ? '1' : '0') : (is_array($value) ? json_encode($value) : (string) $value);
        $setting->save();

        self::flushCache();
    }

    public static function flushCache(): void
    {
        self::$cache = null;
        self::$types = null;
    }

    /** @return array<string, string|null> */
    protected static function allCached(): array
    {
        if (self::$cache === null) {
            $rows = self::query()->get(['key', 'value', 'type']);

            self::$cache = $rows->pluck('value', 'key')->all();
            self::$types = $rows->pluck('type', 'key')->all();
        }

        return self::$cache;
    }

    protected static function typeFor(string $key): string
    {
        self::allCached();

        return self::$types[$key] ?? 'string';
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
