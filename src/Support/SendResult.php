<?php

namespace Whilesmart\Notifications\Support;

use Illuminate\Database\Eloquent\Model;

final class SendResult
{
    /**
     * @param  array<string, bool|null>  $channels  true when delivered, false when the channel failed, null when skipped
     */
    public function __construct(
        public readonly ?Model $notification,
        public readonly array $channels,
    ) {}

    public function delivered(string $channel): bool
    {
        return ($this->channels[$channel] ?? null) === true;
    }
}
