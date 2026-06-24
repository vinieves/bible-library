<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class DateTimeFormat
{
    public static function displayTimezone(): string
    {
        return (string) config('app.display_timezone', 'America/Sao_Paulo');
    }

    public static function display(?DateTimeInterface $date, string $format = 'd/m/Y H:i:s'): ?string
    {
        if (! $date instanceof DateTimeInterface) {
            return filled($date) ? (string) $date : null;
        }

        return Carbon::instance($date)
            ->timezone(static::displayTimezone())
            ->format($format);
    }

    public static function filamentColumn(string $format = 'd/m/Y H:i:s'): \Closure
    {
        return fn ($state): ?string => static::display(
            $state instanceof DateTimeInterface ? $state : null,
            $format,
        );
    }
}
