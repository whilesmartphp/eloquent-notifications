<?php

namespace Whilesmart\Notifications\Support;

use Whilesmart\Notifications\Enums\NotificationLevel;

/**
 * Everything a channel needs, resolved when the notification is sent. Queued
 * channels receive this value rather than models, so they never depend on
 * request state such as a selected tenant.
 */
final class NotificationMessage
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $body = null,
        public readonly array $data = [],
        public readonly NotificationLevel $level = NotificationLevel::Info,
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
        public readonly array $metadata = [],
    ) {}
}
