<?php

namespace Whilesmart\Notifications\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Notifications\Notifier;
use Whilesmart\Notifications\Support\NotificationMessage;
use Whilesmart\Notifications\Support\SendResult;

/**
 * For recipients. The names avoid notify() and notifications(), which
 * Laravel's own Notifiable trait already defines.
 */
trait ReceivesNotifications
{
    public function receivedNotifications(): MorphMany
    {
        return $this->morphMany(config('notifications.model'), 'notifiable');
    }

    public function sendNotification(NotificationMessage $message, ?Model $owner = null, ?array $channels = null): SendResult
    {
        return app(Notifier::class)->send($this, $message, $owner, $channels);
    }
}
