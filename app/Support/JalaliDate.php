<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * Lightweight Gregorian → Jalali (Persian solar) formatter.
 * No external package required.
 */
class JalaliDate
{
    /** @var list<string> */
    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    public static function format(
        DateTimeInterface|string|null $date,
        string $format = 'Y/m/d',
        bool $persianDigits = true,
    ): string {
        if ($date === null || $date === '') {
            return '';
        }

        if (is_string($date)) {
            try {
                $date = \Carbon\Carbon::parse($date);
            } catch (\Throwable) {
                return '';
            }
        }

        if ($date instanceof CarbonInterface) {
            $gy = (int) $date->year;
            $gm = (int) $date->month;
            $gd = (int) $date->day;
            $H = (int) $date->hour;
            $i = (int) $date->minute;
            $s = (int) $date->second;
        } else {
            $gy = (int) $date->format('Y');
            $gm = (int) $date->format('m');
            $gd = (int) $date->format('d');
            $H = (int) $date->format('H');
            $i = (int) $date->format('i');
            $s = (int) $date->format('s');
        }

        [$jy, $jm, $jd] = self::toJalali($gy, $gm, $gd);

        $map = [
            'Y' => sprintf('%04d', $jy),
            'y' => sprintf('%02d', $jy % 100),
            'm' => sprintf('%02d', $jm),
            'n' => (string) $jm,
            'd' => sprintf('%02d', $jd),
            'j' => (string) $jd,
            'H' => sprintf('%02d', $H),
            'i' => sprintf('%02d', $i),
            's' => sprintf('%02d', $s),
        ];

        // Replace tokens longest-first is not needed (single letters)
        $out = '';
        $len = strlen($format);
        for ($p = 0; $p < $len; $p++) {
            $ch = $format[$p];
            $out .= $map[$ch] ?? $ch;
        }

        return $persianDigits ? self::toPersianDigits($out) : $out;
    }

    public static function toPersianDigits(string $value): string
    {
        return strtr($value, array_combine(range(0, 9), self::PERSIAN_DIGITS));
    }

    /**
     * @return array{0:int,1:int,2:int} [jy, jm, jd]
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /**
     * @return array{0:int,1:int,2:int} [gy, gm, gd]
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv((($jy % 33) + 3), 4)
            + $jd + (($jm < 7) ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186));
        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }
        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = [0, 31, ((($gy % 4 === 0) && ($gy % 100 !== 0)) || ($gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 1;
        while ($gm <= 12 && $gd > $sal_a[$gm]) {
            $gd -= $sal_a[$gm];
            $gm++;
        }

        return [$gy, $gm, $gd];
    }

    public static function toLatinDigits(string $value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($fa, $en, $value);
    }

    /**
     * Parse Jalali datetime string like "1403/05/26 14:30" (Persian or Latin digits).
     * Also accepts Gregorian ISO "2024-08-17T14:30" for backward compatibility.
     */
    public static function parseToCarbon(?string $value): ?\Carbon\Carbon
    {
        if ($value === null) {
            return null;
        }
        $value = trim(self::toLatinDigits($value));
        if ($value === '') {
            return null;
        }

        // Gregorian datetime-local leftover
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $value, $m)) {
            $y = (int) $m[1];
            if ($y > 1600) {
                return \Carbon\Carbon::create(
                    $y,
                    (int) $m[2],
                    (int) $m[3],
                    (int) ($m[4] ?? 0),
                    (int) ($m[5] ?? 0),
                    (int) ($m[6] ?? 0),
                );
            }
        }

        // Jalali: 1403/5/26 14:30 or 1403-05-26
        if (preg_match('/^(\d{3,4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:[T\s]+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $value, $m)) {
            $jy = (int) $m[1];
            $jm = (int) $m[2];
            $jd = (int) $m[3];
            $H = (int) ($m[4] ?? 0);
            $i = (int) ($m[5] ?? 0);
            $s = (int) ($m[6] ?? 0);
            if ($jy < 1200 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
                return null;
            }
            [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);

            return \Carbon\Carbon::create($gy, $gm, $gd, $H, $i, $s);
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Days in a Jalali month (1–12). */
    public static function jalaliMonthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }

        return self::isJalaliLeap($jy) ? 30 : 29;
    }

    public static function isJalaliLeap(int $jy): bool
    {
        $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        $bl = count($breaks);
        $jp = $breaks[0];
        $jump = 0;
        for ($i = 1; $i < $bl; $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $jp = $jm;
        }
        $n = $jy - $jp;
        if ($jump - $n < 6) {
            $n = $n - $jump + (int) (($jump + 4) / 33) * 33;
        }
        $leap = ((($n + 1) % 33) - 1) % 4;
        if ($leap === -1) {
            $leap = 4;
        }

        return $leap === 0;
    }
}
