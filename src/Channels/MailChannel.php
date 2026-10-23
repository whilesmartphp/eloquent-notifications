<?php

namespace Whilesmart\Notifications\Channels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Whilesmart\Notifications\Contracts\NotificationChannel;
use Whilesmart\Notifications\Mail\NotificationMail;
use Whilesmart\Notifications\Support\NotificationMessage;
use Whilesmart\Notifications\Support\UnsubscribeLinks;

class MailChannel implements NotificationChannel
{
    public function __construct(private UnsubscribeLinks $links) {}

    public function send(Model $notifiable, NotificationMessage $message, ?Model $notification): bool
    {
        $address = data_get($notifiable, config('notifications.mail.address_attribute', 'email'));
        if (! is_string($address) || $address === '') {
            return false;
        }
        $class = config("notifications.types.{$message->type}.mailable") ?? config('notifications.mail.mailable', NotificationMail::class);
        if (! is_a($class, NotificationMail::class, true)) {
            throw new \InvalidArgumentException('The notification mailable must extend '.NotificationMail::class.'.');
        }
        $name = data_get($notifiable, config('notifications.mail.name_attribute', 'first_name'));
        $mail = new $class($message, $this->links->for($notifiable, $message->type, 'email'), is_string($name) ? $name : null);

        if (config('notifications.mail.queue', true)) {
            Mail::to($address)->queue($mail->afterCommit());
        } else {
            Mail::to($address)->send($mail);
        }

        return true;
    }
}
