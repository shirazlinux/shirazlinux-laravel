<?php

use App\Support\JalaliDate;
use App\Support\Settings;

if (! function_exists('jdate')) {
    /**
     * Format a date as Jalali (Persian solar) calendar.
     * Default: ۱۴۰۳/۰۵/۲۶ (Persian digits).
     */
    function jdate(
        \DateTimeInterface|string|null $date,
        string $format = 'Y/m/d',
        bool $persianDigits = true,
    ): string {
        return JalaliDate::format($date, $format, $persianDigits);
    }
}

if (! function_exists('jdate_input')) {
    /**
     * Format for admin Jalali inputs (Latin digits, editable): 1403/05/26 14:30
     */
    function jdate_input(
        \DateTimeInterface|string|null $date,
        string $format = 'Y/m/d H:i',
    ): string {
        return JalaliDate::format($date, $format, false);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('settings')) {
    /** @return array<string, mixed> */
    function settings(): array
    {
        return Settings::all();
    }
}
