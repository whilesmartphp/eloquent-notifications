# Whilesmart Eloquent Notifications

Polymorphic notifications for Laravel. Store an in-app notification, email it and push it to devices in one call, with per-type and per-channel preferences, signed one-click unsubscribe links, and a ready API for the bell in your app.

## Installation

```bash
composer require whilesmart/eloquent-notifications
php artisan vendor:publish --tag=notifications-config
php artisan migrate
```

Preferences are stored through [`whilesmart/eloquent-model-configuration`](https://github.com/whilesmartphp/eloquent-model-configuration), so its migration must run too.

## Concepts

- **Recipient** (`notifiable`): the model the notification is for, usually a user. Add `ReceivesNotifications` and the `Configurable` trait from eloquent-model-configuration.
- **Owner**: the scope the notification belongs to, such as a workspace, a team or the recipient itself. Access goes through [`whilesmart/eloquent-owner-access`](https://github.com/whilesmartphp/eloquent-owner-access), so bind your `OwnerAuthorizer`. Add `HasNotifications` to owner models.
- **Type**: your own vocabulary, such as `reminder` or `post-published`.
- **Channel**: `inapp` (the stored notification), `email`, `push`, or any channel you register.

```php
use Whilesmart\ModelConfiguration\Traits\Configurable;
use Whilesmart\Notifications\Traits\ReceivesNotifications;

class User extends Authenticatable
{
    use Configurable, Notifiable, ReceivesNotifications;
}
```

The trait methods are `receivedNotifications()` and `sendNotification()`, so they sit alongside Laravel's own `Notifiable` trait.

## Sending

```php
use Whilesmart\Notifications\Enums\NotificationLevel;
use Whilesmart\Notifications\Facades\Notifications;
use Whilesmart\Notifications\Support\NotificationMessage;

$result = Notifications::send($user, new NotificationMessage(
    type: 'post-published',
    title: 'Your post is live',
    body: '"Doors open Monday" was published on every channel you picked.',
    level: NotificationLevel::Success,
    actionUrl: 'https://app.example/schedule',
    actionLabel: 'See your schedule',
    data: ['post_id' => 42],
), owner: $workspace);

$result->notification;        // the stored notification, or null when in-app is off
$result->channels;            // ['inapp' => true, 'email' => true, 'push' => false]
```

Content is resolved when you call `send`, so queued channels never depend on request state such as a selected tenant. Each channel is tried independently: a failing push provider is reported and the email still goes out.

## Types and preferences

Declare the types you send in `config/notifications.php`:

```php
'types' => [
    'post-published' => ['label' => 'Post published', 'description' => 'When a post you scheduled goes out'],
    'backup-completed' => ['label' => 'Backup finished', 'channel_defaults' => ['email' => false]],
    'security' => ['label' => 'Security', 'preference_key' => null, 'channels' => ['inapp', 'email']],
],
```

A notification is delivered on a channel only when all three switches are on:

| Switch | Default key | Default value |
|---|---|---|
| The channel | `notifications-{channel}` (set per channel) | the channel's `default` |
| The type | `notifications-{type}` (set per type, `null` to lock it on) | the type's `default` |
| The type on that channel | `type_channel_preference_key`, `notifications-{type}-{channel}` | the type's `channel_defaults` |

`channels` limits a type to some channels. Types you never declare are delivered everywhere and cannot be switched off.

## Channels

Email uses the bundled `NotificationMail` markdown template, queued after the surrounding transaction commits. Override it globally with `mail.mailable`, or per type with `types.{type}.mailable`; both must extend `NotificationMail`. Publish the view with `--tag=notifications-views`.

Push sends nothing until you bind a sender:

```php
use Whilesmart\Notifications\Contracts\PushSender;

$this->app->bind(PushSender::class, FirebasePushSender::class);
```

Add your own channel by naming a driver that implements `Contracts\NotificationChannel`:

```php
'channels' => [
    // ...
    'sms' => ['label' => 'SMS', 'default' => false, 'preference_key' => 'notifications-sms', 'driver' => SmsChannel::class],
],
```

Preferences are read and written through `Contracts\PreferenceStore`. The default stores them with eloquent-model-configuration; bind your own to keep them elsewhere.

## Unsubscribe

Every email carries a signed link, plus `List-Unsubscribe` headers for one-click unsubscribe in mail clients. Following it switches that type off by email only. Set `unsubscribe.redirect` to send people to a confirmation page; the type and channel are added to the query string. Set `unsubscribe.expires_days` to make links expire.

## API

All routes use `route_prefix` (default `api`) and `route_middleware` (default `api`, `auth:sanctum`). Each person only sees notifications addressed to them, within owners the `OwnerAuthorizer` allows.

| Method | Path | Action |
|---|---|---|
| GET | `/notifications` | List, newest first. Filters: `unread=1`, `type`, `owner_type` with `owner_id`, `per_page` |
| GET | `/notifications/unread-count` | `{ unread }` |
| GET | `/notifications/{id}` | Show one |
| POST | `/notifications/{id}/read` | Mark read |
| POST | `/notifications/{id}/unread` | Mark unread |
| POST | `/notifications/read-all` | Mark all read, optionally by `type` |
| DELETE | `/notifications/{id}` | Delete |
| GET | `/notifications/preferences` | Channels and types with their current state |
| PUT | `/notifications/preferences` | `{ channels: {email: false}, types: {reminder: false}, type_channels: {digest: {email: true}} }` |
| GET, POST | `/notifications/unsubscribe/{type}/{channel}` | Signed unsubscribe link (outside the auth middleware) |

## Customization

Everything below is set in `config/notifications.php`; no controller or route copies are needed.

| Key | Purpose |
|---|---|
| `model` | Your subclass of `Models\Notification`, for casts, traits or relations |
| `uuids` | UUID primary keys and morph columns (set before migrating) |
| `table` | Table name |
| `route_actions` | Which routes to register |
| `route_write_middleware` | Extra middleware for routes that change data |
| `controller` | A subclass of `NotificationController` |
| `resource` | The API resource used for each notification |
| `response_formatter` | A `Contracts\ResponseFormatter` for the outer envelope |
| `register_routes` | Turn the package routes off entirely |

## Events

Dispatched after the change is saved: `NotificationCreated`, `NotificationRead`, `NotificationUnread`, `NotificationDeleted` and `PreferencesUpdated`.

## Moving from a user-keyed notifications table

Apps that already keep notifications in a table with `user_id`, `type`, `title`, `body`, `data` and `read_at` can move without changing ids:

1. Rename the existing table, for example to `notifications_legacy`, in an app migration.
2. Run the package migration.
3. Copy the rows. Ids, read state and timestamps are kept, and a rerun skips rows already copied:

   ```bash
   php artisan notifications:import-legacy notifications_legacy --user-model="App\Models\User"
   ```

4. Point `model` at your existing notification class, now extending `Whilesmart\Notifications\Models\Notification`, so traits such as mobile sync keep working.
5. Keep your stored preference keys by naming them in config, for example `'reminder' => ['preference_key' => 'notifications-reminders']`. The channel keys default to `notifications-email`, `notifications-push` and `notifications-inapp`.
6. Bind your push sender and replace direct sends with `Notifications::send()`.

The package table is named `notifications` by default, which is also the name of Laravel's database notification channel table. Set `table` if your app uses that channel.

## License

MIT. WhileSmart LTD.
