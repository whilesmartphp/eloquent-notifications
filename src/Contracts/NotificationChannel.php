<?php

namespace Whilesmart\Notifications\Contracts;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Notifications\Support\NotificationMessage;

interface NotificationChannel
{
    /**
     * Deliver $message to $notifiable. $notification is the stored in-app
     * record, or null when the recipient switched the in-app channel off.
     * Return false when the message could not be delivered.
     */
    public function send(Model $notifiable, NotificationMessage $message, ?Model $notification): bool;
}
