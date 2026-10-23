<?php

namespace Whilesmart\Notifications\Facades;

use Illuminate\Support\Facades\Facade;
use Whilesmart\Notifications\Notifier;

/**
 * @method static \Whilesmart\Notifications\Support\SendResult send(\Illuminate\Database\Eloquent\Model $notifiable, \Whilesmart\Notifications\Support\NotificationMessage $message, ?\Illuminate\Database\Eloquent\Model $owner = null, ?array $channels = null)
 */
class Notifications extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Notifier::class;
    }
}
