<?php

namespace Whilesmart\Notifications\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class PreferencesUpdated
{
    use Dispatchable;

    /** @param  array<string, bool>  $preferences  the stored keys that changed */
    public function __construct(public Model $notifiable, public array $preferences) {}
}
