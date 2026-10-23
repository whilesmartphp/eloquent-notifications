<?php

namespace Whilesmart\Notifications\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PreferenceStore
{
    /** The stored value, or null when the recipient never set it. */
    public function get(Model $notifiable, string $key): ?bool;

    public function set(Model $notifiable, string $key, bool $enabled): void;
}
