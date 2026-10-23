<?php

namespace Whilesmart\Notifications\Push;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Notifications\Contracts\PushSender;
use Whilesmart\Notifications\Support\NotificationMessage;

/** Sends nothing until the host binds a real PushSender. */
class NullPushSender implements PushSender
{
    public function send(Model $notifiable, NotificationMessage $message, ?Model $notification): bool
    {
        return false;
    }
}
