<?php

namespace Whilesmart\Notifications\Enums;

enum NotificationLevel: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
