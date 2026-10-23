<?php

namespace Whilesmart\Notifications\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Whilesmart\Notifications\Contracts\ResponseFormatter;
use Whilesmart\Notifications\Events\NotificationDeleted;
use Whilesmart\Notifications\Events\NotificationRead;
use Whilesmart\Notifications\Events\NotificationUnread;
use Whilesmart\Notifications\Http\Requests\UpdatePreferencesRequest;
use Whilesmart\Notifications\Models\Notification;
use Whilesmart\Notifications\Support\Preferences;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;

class NotificationController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request): JsonResponse
    {
        $query = $this->mine($request);

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }
        if ($request->filled('type')) {
            $query->whereIn('type', (array) $request->input('type'));
        }
        if ($request->filled('owner_type') && $request->filled('owner_id')) {
            $query->where('owner_type', $request->input('owner_type'))
                ->where('owner_id', $request->input('owner_id'));
        }

        $notifications = $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(min(100, (int) $request->input('per_page', config('notifications.per_page', 25))));

        return $this->response([
            'success' => true,
            'data' => $this->resource()::collection($notifications)->response()->getData(true),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->response([
            'success' => true,
            'data' => ['unread' => $this->mine($request)->whereNull('read_at')->count()],
        ]);
    }

    public function show(Request $request, $notification): JsonResponse
    {
        $this->authorizeRecipient($notification, $request);

        return $this->response(['success' => true, 'data' => new ($this->resource())($notification)]);
    }

    public function read(Request $request, $notification): JsonResponse
    {
        $this->authorizeRecipient($notification, $request);
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
            NotificationRead::dispatch($notification);
        }

        return $this->response(['success' => true, 'data' => new ($this->resource())($notification->fresh())]);
    }

    public function unread(Request $request, $notification): JsonResponse
    {
        $this->authorizeRecipient($notification, $request);
        if ($notification->read_at) {
            $notification->update(['read_at' => null]);
            NotificationUnread::dispatch($notification);
        }

        return $this->response(['success' => true, 'data' => new ($this->resource())($notification->fresh())]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $query = $this->mine($request)->whereNull('read_at');
        if ($request->filled('type')) {
            $query->whereIn('type', (array) $request->input('type'));
        }
        $updated = $query->update(['read_at' => now()]);

        return $this->response([
            'success' => true,
            'data' => ['updated' => $updated, 'unread' => $this->mine($request)->whereNull('read_at')->count()],
        ]);
    }

    public function destroy(Request $request, $notification): JsonResponse
    {
        $this->authorizeRecipient($notification, $request);
        $notification->delete();
        NotificationDeleted::dispatch($notification);

        return $this->response(['success' => true, 'message' => 'Notification deleted.']);
    }

    public function preferences(Request $request, Preferences $preferences): JsonResponse
    {
        return $this->response(['success' => true, 'data' => $preferences->describe($request->user())]);
    }

    public function updatePreferences(UpdatePreferencesRequest $request, Preferences $preferences): JsonResponse
    {
        $preferences->update($request->user(), $request->validated());

        return $this->response(['success' => true, 'data' => $preferences->describe($request->user())]);
    }

    /** Notifications addressed to the signed-in user, within owners they may access. */
    protected function mine(Request $request)
    {
        $model = $this->model();
        $user = $request->user();
        abort_unless($user instanceof Model, 401);

        return $this->scopeAccessibleOwners($model::query(), $user)
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey());
    }

    protected function authorizeRecipient(Model $notification, Request $request): void
    {
        $this->authorizeAccessTo($notification, $request->user());
        abort_unless($notification instanceof Notification && $notification->isFor($request->user()), 404);
    }

    protected function model(): string
    {
        return config('notifications.model', Notification::class);
    }

    protected function resource(): string
    {
        $resource = config('notifications.resource');
        if (! is_a($resource, JsonResource::class, true)) {
            throw new \InvalidArgumentException('The configured notification resource must extend '.JsonResource::class.'.');
        }

        return $resource;
    }

    protected function response(array $payload, int $statusCode = 200): JsonResponse
    {
        return app(ResponseFormatter::class)->format($payload, $statusCode);
    }
}
