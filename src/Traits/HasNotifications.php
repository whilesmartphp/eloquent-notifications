<?php

namespace Whilesmart\Notifications\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/** For owners that scope notifications, such as a workspace or team. */
trait HasNotifications
{
    public function ownedNotifications(): MorphMany
    {
        return $this->morphMany(config('notifications.model'), 'owner');
    }
}
