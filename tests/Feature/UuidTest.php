<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Notifications\Facades\Notifications;
use Whilesmart\Notifications\Support\NotificationMessage;

class UuidTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('notifications.uuids', true);
    }

    #[Test]
    public function it_uses_uuid_primary_keys_when_enabled(): void
    {
        Mail::fake();
        $notification = Notifications::send($this->user(), new NotificationMessage('reminder', 'Log today'), channels: ['inapp'])->notification;

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $notification->id);
    }
}
