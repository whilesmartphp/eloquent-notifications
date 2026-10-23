<?php

use Whilesmart\Notifications\Channels\MailChannel;
use Whilesmart\Notifications\Channels\PushChannel;
use Whilesmart\Notifications\Http\Controllers\NotificationController;
use Whilesmart\Notifications\Http\Resources\NotificationResource;
use Whilesmart\Notifications\Mail\NotificationMail;
use Whilesmart\Notifications\Models\Notification;
use Whilesmart\Notifications\ResponseFormatters\DefaultResponseFormatter;

return [
    // The Notification model. Point this at your own subclass to add casts,
    // traits (for example mobile sync) or relations.
    'model' => Notification::class,

    // Use UUID primary keys and uuid morph columns. Must be set before the
    // migration runs, and assumes owners and recipients use UUID keys too.
    'uuids' => (bool) env('NOTIFICATIONS_UUIDS', false),

    'table' => env('NOTIFICATIONS_TABLE', 'notifications'),

    'register_routes' => env('NOTIFICATIONS_REGISTER_ROUTES', true),
    'route_prefix' => env('NOTIFICATIONS_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],
    'route_write_middleware' => [],
    'route_actions' => [
        'index', 'unread_count', 'show', 'read', 'unread', 'read_all', 'destroy', 'preferences', 'update_preferences',
    ],
    'controller' => NotificationController::class,
    'resource' => NotificationResource::class,
    'response_formatter' => DefaultResponseFormatter::class,
    'per_page' => 25,

    // Delivery channels. The in-app channel is the stored notification itself;
    // every other channel names a driver implementing the NotificationChannel
    // contract. A null preference_key means the channel cannot be switched off.
    'channels' => [
        'inapp' => ['label' => 'In-app', 'default' => true, 'preference_key' => 'notifications-inapp'],
        'email' => ['label' => 'Email', 'default' => true, 'preference_key' => 'notifications-email', 'driver' => MailChannel::class],
        'push' => ['label' => 'Push', 'default' => true, 'preference_key' => 'notifications-push', 'driver' => PushChannel::class],
    ],

    // Notification types the host sends. Unlisted types are delivered on
    // every channel and cannot be switched off. Each type may declare:
    //   label, description, default (bool), preference_key (string|null),
    //   channels (allowed channel keys), channel_defaults (['email' => false]),
    //   mailable (a Mailable class extending NotificationMail).
    'types' => [],

    // Key for switching one type off on one channel, e.g. "stop these emails".
    'type_channel_preference_key' => 'notifications-{type}-{channel}',

    'mail' => [
        'mailable' => NotificationMail::class,
        'address_attribute' => 'email',
        'name_attribute' => 'first_name',
        'queue' => true,
    ],

    // Signed one-click unsubscribe links added to every email.
    'unsubscribe' => [
        'enabled' => true,
        'route_prefix' => env('NOTIFICATIONS_UNSUBSCRIBE_PREFIX', 'notifications'),
        'middleware' => ['signed', 'throttle:30,1'],
        'redirect' => env('NOTIFICATIONS_UNSUBSCRIBE_REDIRECT'),
        'expires_days' => null,
    ],
];
