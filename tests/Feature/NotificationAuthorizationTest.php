<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Workspace;
use Tests\TestCase;
use Whilesmart\Notifications\Facades\Notifications;
use Whilesmart\Notifications\Support\NotificationMessage;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class NotificationAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });
    }

    #[Test]
    public function notifications_under_an_inaccessible_owner_stay_hidden(): void
    {
        Mail::fake();
        $ada = $this->user();
        $notification = Notifications::send($ada, new NotificationMessage('reminder', 'Private'), Workspace::create(['name' => 'Gone']))->notification;
        $this->actingAs($ada);

        $this->getJson('/api/notifications')->assertOk()->assertJsonPath('data.meta.total', 0);
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.unread', 0);
        $this->postJson('/api/notifications/read-all')->assertJsonPath('data.updated', 0);
        $this->getJson("/api/notifications/{$notification->id}")->assertForbidden();
        $this->postJson("/api/notifications/{$notification->id}/read")->assertForbidden();
        $this->postJson("/api/notifications/{$notification->id}/unread")->assertForbidden();
        $this->deleteJson("/api/notifications/{$notification->id}")->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
        $this->assertNull($notification->fresh()->deleted_at);
    }
}
