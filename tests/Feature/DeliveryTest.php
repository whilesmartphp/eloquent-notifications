<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\ModelConfiguration\Enums\ConfigValueType;
use Whilesmart\Notifications\Contracts\PushSender;
use Whilesmart\Notifications\Events\NotificationCreated;
use Whilesmart\Notifications\Facades\Notifications;
use Whilesmart\Notifications\Mail\NotificationMail;
use Whilesmart\Notifications\Support\NotificationMessage;

class RecordingPushSender implements PushSender
{
    public array $sent = [];

    public bool $fail = false;

    public function send(Model $notifiable, NotificationMessage $message, ?Model $notification): bool
    {
        if ($this->fail) {
            throw new \RuntimeException('Push provider is down.');
        }
        $this->sent[] = [$notifiable->getKey(), $message->title, $notification?->getKey()];

        return true;
    }
}

class DeliveryTest extends TestCase
{
    private RecordingPushSender $push;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->push = new RecordingPushSender;
        $this->app->instance(PushSender::class, $this->push);
    }

    #[Test]
    public function every_allowed_channel_receives_the_message(): void
    {
        Event::fake([NotificationCreated::class]);
        $ada = $this->user();

        $result = Notifications::send($ada, new NotificationMessage('reminder', 'Log today', 'Two minutes is enough.', actionUrl: 'https://app.example/log', actionLabel: 'Log now'));

        $this->assertSame(['inapp' => true, 'email' => true, 'push' => true], $result->channels);
        $this->assertSame([[$ada->id, 'Log today', $result->notification->id]], $this->push->sent);
        Event::assertDispatched(NotificationCreated::class, fn ($event) => $event->notification->is($result->notification));
        Mail::assertQueued(NotificationMail::class, function (NotificationMail $mail) {
            $mail->assertSeeInHtml('Two minutes is enough.')->assertSeeInHtml('https://app.example/log')->assertSeeInHtml('Stop these emails');

            $this->assertSame('<'.$mail->unsubscribeUrl.'>', $mail->headers()->text['List-Unsubscribe']);

            return $mail->hasTo('ada@example.com') && $mail->recipientName === 'Ada'
                && str_contains((string) $mail->unsubscribeUrl, '/notifications/unsubscribe/reminder/email');
        });
    }

    #[Test]
    public function channel_type_and_type_channel_preferences_each_switch_delivery_off(): void
    {
        $ada = $this->user();
        $ada->setConfigValue('notifications-email', false, ConfigValueType::Boolean);
        $result = Notifications::send($ada, new NotificationMessage('reminder', 'No email'));
        $this->assertSame(['inapp' => true, 'email' => null, 'push' => true], $result->channels);

        $ada->setConfigValue('notifications-reminders', false, ConfigValueType::Boolean);
        $result = Notifications::send($ada, new NotificationMessage('reminder', 'Nothing at all'));
        $this->assertNull($result->notification);
        $this->assertSame(['inapp' => null, 'email' => null, 'push' => null], $result->channels);

        $bob = $this->user(['first_name' => 'Bob', 'email' => 'bob@example.com']);
        $this->assertNull(Notifications::send($bob, new NotificationMessage('digest', 'Off by default'))->channels['email']);
        $bob->setConfigValue('notifications-digest-email', true, ConfigValueType::Boolean);
        $this->assertTrue(Notifications::send($bob, new NotificationMessage('digest', 'Opted in'))->channels['email']);
        Mail::assertQueuedCount(1);
    }

    #[Test]
    public function email_still_goes_out_when_in_app_is_off(): void
    {
        $ada = $this->user();
        $ada->setConfigValue('notifications-inapp', false, ConfigValueType::Boolean);

        $result = Notifications::send($ada, new NotificationMessage('reminder', 'Email only'));

        $this->assertNull($result->notification);
        $this->assertTrue($result->delivered('email'));
        $this->assertSame([[$ada->id, 'Email only', null]], $this->push->sent);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function a_failing_channel_does_not_stop_the_others(): void
    {
        $this->push->fail = true;
        $result = Notifications::send($this->user(), new NotificationMessage('reminder', 'Still stored'));

        $this->assertSame(['inapp' => true, 'email' => true, 'push' => false], $result->channels);
        $this->assertDatabaseCount('notifications', 1);
    }

    #[Test]
    public function type_channels_and_unlisted_types_are_respected(): void
    {
        $ada = $this->user();
        $this->assertSame(['inapp' => true, 'email' => true], Notifications::send($ada, new NotificationMessage('security', 'New sign-in'))->channels);
        $this->assertSame(['inapp' => true, 'email' => true, 'push' => true], Notifications::send($ada, new NotificationMessage('anything', 'Unlisted'))->channels);
        $this->assertSame(['email' => true], Notifications::send($ada, new NotificationMessage('reminder', 'Email only'), channels: ['email'])->channels);
    }

    #[Test]
    public function mail_can_be_sent_immediately_and_skips_recipients_without_an_address(): void
    {
        config(['notifications.mail.queue' => false]);
        Notifications::send($this->user(), new NotificationMessage('reminder', 'Right now'));
        Mail::assertSent(NotificationMail::class, 1);

        $result = Notifications::send($this->user(['email' => null]), new NotificationMessage('reminder', 'No address'));
        $this->assertFalse($result->channels['email']);
    }
}
