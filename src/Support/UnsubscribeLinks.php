<?php

namespace Whilesmart\Notifications\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class UnsubscribeLinks
{
    public const ROUTE = 'notifications.unsubscribe';

    public function __construct(private Preferences $preferences) {}

    /** A signed link that switches $type off on $channel, or null when it cannot be switched off. */
    public function for(Model $notifiable, string $type, string $channel): ?string
    {
        if (! config('notifications.unsubscribe.enabled', true) || ! Route::has(self::ROUTE)
            || $this->preferences->typeChannelKey($type, $channel) === null) {
            return null;
        }
        $parameters = [
            'type' => $type,
            'channel' => $channel,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
        ];
        $days = config('notifications.unsubscribe.expires_days');

        return $days
            ? URL::temporarySignedRoute(self::ROUTE, now()->addDays((int) $days), $parameters)
            : URL::signedRoute(self::ROUTE, $parameters);
    }
}
