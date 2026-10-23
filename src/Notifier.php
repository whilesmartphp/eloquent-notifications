<?php

namespace Whilesmart\Notifications;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Notifications\Contracts\NotificationChannel;
use Whilesmart\Notifications\Events\NotificationCreated;
use Whilesmart\Notifications\Support\NotificationMessage;
use Whilesmart\Notifications\Support\Preferences;
use Whilesmart\Notifications\Support\SendResult;

class Notifier
{
    public const IN_APP = 'inapp';

    public function __construct(private Preferences $preferences) {}

    /**
     * Store and deliver $message for $notifiable on every channel the type
     * allows and the recipient has not switched off. $owner scopes the record
     * (a workspace, team or the recipient itself) and defaults to $notifiable.
     *
     * @param  list<string>|null  $channels  limit delivery to these channels
     */
    public function send(Model $notifiable, NotificationMessage $message, ?Model $owner = null, ?array $channels = null): SendResult
    {
        $owner ??= $notifiable;
        $wanted = $channels ?? $this->preferences->channelsFor($message->type);
        $results = [];
        $notification = null;

        if (in_array(self::IN_APP, $wanted, true)) {
            if ($this->preferences->allows($notifiable, $message->type, self::IN_APP)) {
                $notification = $this->store($notifiable, $owner, $message);
                $results[self::IN_APP] = true;
            } else {
                $results[self::IN_APP] = null;
            }
        }

        foreach ($wanted as $channel) {
            if ($channel === self::IN_APP) {
                continue;
            }
            if (! $this->preferences->allows($notifiable, $message->type, $channel)) {
                $results[$channel] = null;

                continue;
            }
            try {
                $results[$channel] = $this->driver($channel)->send($notifiable, $message, $notification);
            } catch (\Throwable $error) {
                report($error);
                $results[$channel] = false;
            }
        }

        return new SendResult($notification, $results);
    }

    private function store(Model $notifiable, Model $owner, NotificationMessage $message): Model
    {
        $model = config('notifications.model');
        $notification = $model::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'type' => $message->type,
            'level' => $message->level,
            'title' => $message->title,
            'body' => $message->body,
            'action_url' => $message->actionUrl,
            'action_label' => $message->actionLabel,
            'data' => $message->data ?: null,
            'metadata' => $message->metadata ?: null,
        ]);
        NotificationCreated::dispatch($notification);

        return $notification;
    }

    private function driver(string $channel): NotificationChannel
    {
        $class = config("notifications.channels.{$channel}.driver");
        $driver = $class ? app($class) : null;
        if (! $driver instanceof NotificationChannel) {
            throw new \InvalidArgumentException("The notification channel [{$channel}] must name a driver implementing ".NotificationChannel::class.'.');
        }

        return $driver;
    }
}
