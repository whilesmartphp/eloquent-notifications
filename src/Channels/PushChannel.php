<?php

namespace Whilesmart\Notifications\Channels;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Notifications\Contracts\NotificationChannel;
use Whilesmart\Notifications\Contracts\PushSender;
use Whilesmart\Notifications\Support\NotificationMessage;

class PushChannel implements NotificationChannel
{
    public function __construct(private PushSender $sender) {}

    public function send(Model $notifiable, NotificationMessage $message, ?Model $notification): bool
    {
        return $this->sender->send($notifiable, $message, $notification);
    }
}
