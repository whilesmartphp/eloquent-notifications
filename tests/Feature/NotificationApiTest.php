<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Workspace;
use Tests\TestCase;
use Whilesmart\Notifications\Events\NotificationDeleted;
use Whilesmart\Notifications\Events\NotificationRead;
use Whilesmart\Notifications\Events\NotificationUnread;
use Whilesmart\Notifications\Facades\Notifications;
use Whilesmart\Notifications\Support\NotificationMessage;

class NotificationApiTest extends TestCase
{
    #[Test]
    public function a_recipient_reads_marks_and_deletes_only_their_own_notifications(): void
    {
        Mail::fake();
        Event::fake([NotificationRead::class, NotificationUnread::class, NotificationDeleted::class]);
        $ada = $this->user();
        $bob = $this->user(['first_name' => 'Bob', 'email' => 'bob@example.com']);
        $workspace = Workspace::create(['name' => 'Studio']);
        $first = Notifications::send($ada, new NotificationMessage('reminder', 'Log today', 'You have not logged anything.'))->notification;
        $second = $ada->sendNotification(new NotificationMessage('digest', 'Your week', actionUrl: 'https://app.example/week'), $workspace)->notification;
        $other = Notifications::send($bob, new NotificationMessage('reminder', 'Not yours'))->notification;

        $this->actingAs($ada);
        $this->getJson('/api/notifications')->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.data.0.id', $second->id)
            ->assertJsonPath('data.data.0.owner_type', $workspace->getMorphClass())
            ->assertJsonPath('data.data.0.action_url', 'https://app.example/week')
            ->assertJsonPath('data.data.1.level', 'info');
        $this->getJson('/api/notifications?type=reminder')->assertJsonPath('data.meta.total', 1);
        $this->getJson('/api/notifications?owner_type='.urlencode($workspace->getMorphClass()).'&owner_id='.$workspace->id)
            ->assertJsonPath('data.meta.total', 1);
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.unread', 2);

        $this->getJson("/api/notifications/{$first->id}")->assertOk()->assertJsonPath('data.title', 'Log today');
        $this->postJson("/api/notifications/{$first->id}/read")->assertOk()->assertJsonPath('data.read_at', fn ($value) => $value !== null);
        $this->getJson('/api/notifications?unread=1')->assertJsonPath('data.meta.total', 1);
        $this->postJson("/api/notifications/{$first->id}/unread")->assertOk()->assertJsonPath('data.read_at', null);
        $this->postJson('/api/notifications/read-all')->assertOk()->assertJsonPath('data.updated', 2)->assertJsonPath('data.unread', 0);
        $this->deleteJson("/api/notifications/{$first->id}")->assertOk();
        $this->getJson('/api/notifications')->assertJsonPath('data.meta.total', 1);

        $this->getJson("/api/notifications/{$other->id}")->assertNotFound();
        $this->postJson("/api/notifications/{$other->id}/read")->assertNotFound();
        $this->deleteJson("/api/notifications/{$other->id}")->assertNotFound();
        $this->assertNull($other->fresh()->read_at);

        Event::assertDispatchedTimes(NotificationRead::class, 1);
        Event::assertDispatchedTimes(NotificationUnread::class, 1);
        Event::assertDispatched(NotificationDeleted::class, fn ($event) => $event->notification->is($first));
    }

    #[Test]
    public function a_guest_is_refused(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }
}
