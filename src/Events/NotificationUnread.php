<?php

namespace Whilesmart\Notifications\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class NotificationUnread
{
    use Dispatchable;

    public function __construct(public Model $notification) {}
}
