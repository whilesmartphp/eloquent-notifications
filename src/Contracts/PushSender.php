<?php

namespace Whilesmart\Notifications\Contracts;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Notifications\Support\NotificationMessage;

interface PushSender
{
    /**
     * Send $message to the recipient's devices through the host's push
     * provider. Return false when nothing was delivered.
     */
    public function send(Model $notifiable, NotificationMessage $message, ?Model $notification): bool;
}
