<?php

namespace Tests\Feature;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Notifications\Contracts\ResponseFormatter;
use Whilesmart\Notifications\Facades\Notifications;
use Whilesmart\Notifications\Http\Controllers\NotificationController;
use Whilesmart\Notifications\Models\Notification;
use Whilesmart\Notifications\Support\NotificationMessage;

class CustomNotificationController extends NotificationController {}

class SyncedNotification extends Notification
{
    protected $appends = ['client_generated_id'];

    public function getClientGeneratedIdAttribute(): string
    {
        return 'client-'.$this->id;
    }
}

class CompactNotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'client_id' => $this->client_generated_id];
    }
}

class EnvelopeFormatter implements ResponseFormatter
{
    public function format(array $payload, int $statusCode = 200): JsonResponse
    {
        return response()->json(['ok' => $payload['success'] ?? false, 'result' => $payload['data'] ?? null], $statusCode);
    }
}

class CustomizationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('notifications.controller', CustomNotificationController::class);
        $app['config']->set('notifications.route_actions', ['index', 'show', 'read']);
        $app['config']->set('notifications.route_write_middleware', ['notification-write']);
        $app['config']->set('notifications.model', SyncedNotification::class);
        $app['config']->set('notifications.resource', CompactNotificationResource::class);
        $app['config']->set('notifications.response_formatter', EnvelopeFormatter::class);
    }

    #[Test]
    public function routes_controller_and_write_middleware_follow_the_config(): void
    {
        $routes = $this->app['router']->getRoutes();

        $this->assertInstanceOf(Route::class, $routes->getByName('notifications.index'));
        $this->assertSame(CustomNotificationController::class.'@show', $routes->getByName('notifications.show')->getActionName());
        $this->assertContains('notification-write', $routes->getByName('notifications.read')->gatherMiddleware());
        $this->assertNotContains('notification-write', $routes->getByName('notifications.index')->gatherMiddleware());
        $this->assertNull($routes->getByName('notifications.destroy'));
        $this->assertNull($routes->getByName('notifications.read-all'));
    }

    #[Test]
    public function the_model_resource_and_formatter_shape_the_response(): void
    {
        Mail::fake();
        $ada = $this->user();
        $notification = Notifications::send($ada, new NotificationMessage('reminder', 'Log today'))->notification;
        $this->assertInstanceOf(SyncedNotification::class, $notification);

        $this->actingAs($ada)->getJson("/api/notifications/{$notification->id}")->assertOk()
            ->assertExactJson(['ok' => true, 'result' => ['id' => $notification->id, 'title' => 'Log today', 'client_id' => 'client-'.$notification->id]]);
    }
}
