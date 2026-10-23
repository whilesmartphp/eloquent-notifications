<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\User;
use Tests\TestCase;
use Whilesmart\Notifications\Facades\Notifications;
use Whilesmart\Notifications\Mail\NotificationMail;
use Whilesmart\Notifications\Support\NotificationMessage;

class UnsubscribeTest extends TestCase
{
    private function link(): string
    {
        Mail::fake();
        Notifications::send($this->user(), new NotificationMessage('reminder', 'Log today'));
        $url = null;
        Mail::assertQueued(NotificationMail::class, function (NotificationMail $mail) use (&$url) {
            $url = $mail->unsubscribeUrl;

            return true;
        });

        return $url;
    }

    #[Test]
    public function the_emailed_link_stops_that_type_by_email_only(): void
    {
        $url = $this->link();

        $this->get($url)->assertOk()->assertJsonPath('data', ['type' => 'reminder', 'channel' => 'email']);

        $result = Notifications::send(User::first(), new NotificationMessage('reminder', 'Again'));
        $this->assertSame(['inapp' => true, 'email' => null, 'push' => false], $result->channels);
    }

    #[Test]
    public function mail_clients_can_unsubscribe_in_one_click_and_hosts_can_redirect(): void
    {
        config(['notifications.unsubscribe.redirect' => 'https://app.example/unsubscribed']);
        $url = $this->link();

        $this->post($url)->assertRedirect('https://app.example/unsubscribed?type=reminder&channel=email');
        $this->assertFalse(User::first()->getConfigValue('notifications-reminder-email'));
    }

    #[Test]
    public function a_tampered_link_is_refused(): void
    {
        $url = $this->link();

        $this->get(str_replace('/reminder/email', '/digest/email', $url))->assertForbidden();
        $this->get(preg_replace('/notifiable_id=\d+/', 'notifiable_id=99', $url))->assertForbidden();
        $this->assertNull(User::first()->getConfigValue('notifications-reminder-email'));
    }
}
