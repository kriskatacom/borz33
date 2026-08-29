<?php

declare(strict_types=1);

namespace Store\Core;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class DateFormat
{
    public const TIMEZONE = 'Europe/Sofia';

    public static function absolute(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->timezone(self::TIMEZONE)->format('d.m.Y, H:i');
    }

    public static function relative(DateTimeInterface $date): string
    {
        $seconds = max(0, time() - $date->getTimestamp());

        if ($seconds < 45) {
            return 'току-що';
        }

        if ($seconds < 60) {
            return self::ago($seconds, 'секунда', 'секунди', 'секунди');
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return self::ago($minutes, 'минута', 'минути', 'минути');
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return self::ago($hours, 'час', 'часа', 'часа');
        }

        $days = intdiv($hours, 24);

        if ($days < 7) {
            return self::ago($days, 'ден', 'дни', 'дни');
        }

        $weeks = intdiv($days, 7);

        if ($days < 30) {
            return self::ago($weeks, 'седмица', 'седмици', 'седмици');
        }

        $months = intdiv($days, 30);

        if ($months < 12) {
            return self::ago(max(1, $months), 'месец', 'месеца', 'месеца');
        }

        return self::ago(max(1, intdiv($days, 365)), 'година', 'години', 'години');
    }

    public static function greeting(?DateTimeInterface $now = null): string
    {
        $hour = (int) Carbon::instance($now ?? Carbon::now())->timezone(self::TIMEZONE)->format('G');

        return match (true) {
            $hour < 5 => 'Добра нощ',
            $hour < 11 => 'Добро утро',
            $hour < 18 => 'Добър ден',
            default => 'Добър вечер',
        };
    }

    private static function ago(int $count, string $one, string $few, string $many): string
    {
        return 'преди ' . $count . ' ' . self::plural($count, $one, $few, $many);
    }

    private static function plural(int $count, string $one, string $few, string $many): string
    {
        if ($count === 1) {
            return $one;
        }

        if ($count >= 2 && $count <= 4) {
            return $few;
        }

        return $many;
    }
}
