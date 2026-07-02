<?php

namespace App\Helpers;

/**
 * Lightweight Jalali (Shamsi) date converter.
 * No external package needed.
 */
class Jalalian
{
    /**
     * Convert a Carbon/DateTime to Jalali string.
     */
    public static function fromCarbon($date, string $format = 'Y/m/d'): string
    {
        if (!$date) {
            return '—';
        }

        [$jy, $jm, $jd] = self::toJalali(
            (int) $date->format('Y'),
            (int) $date->format('m'),
            (int) $date->format('d')
        );

        $result = str_replace(
            ['Y', 'm', 'd', 'H', 'i'],
            [$jy, str_pad($jm, 2, '0', STR_PAD_LEFT), str_pad($jd, 2, '0', STR_PAD_LEFT), $date->format('H'), $date->format('i')],
            $format
        );

        return $result;
    }

    /**
     * Get current Jalali date.
     */
    public static function now(string $format = 'Y/m/d'): string
    {
        return self::fromCarbon(now(), $format);
    }

    /**
     * Jalali day-of-week name (Saturday-based).
     */
    public static function dayOfWeek($date): string
    {
        $days = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $dow = (int) $date->format('w'); // 0=Sun
        return $days[$dow];
    }

    /**
     * Short day name.
     */
    public static function shortDay($date): string
    {
        $days = ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش'];
        return $days[(int) $date->format('w')];
    }

    /**
     * Jalali month name.
     */
    public static function monthName(int $month): string
    {
        $months = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
        ];
        return $months[$month] ?? '';
    }

    /**
     * Core Gregorian → Jalali conversion algorithm.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100))
            + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * ((int)($days / 12053)));
        $days = $days % 12053;
        $jy += 4 * ((int)($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return [$jy, $jm, $jd];
    }
}
