<?php

use App\Models\SystemSetting;

if (! function_exists('settings')) {
    function settings(string $key, mixed $default = null): mixed
    {
        return SystemSetting::get($key, $default);
    }
}
