<?php

namespace Whilesmart\Notifications;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Whilesmart\Notifications\Console\ImportLegacyNotifications;
use Whilesmart\Notifications\Contracts\PreferenceStore;
use Whilesmart\Notifications\Contracts\PushSender;
use Whilesmart\Notifications\Contracts\ResponseFormatter;
use Whilesmart\Notifications\Http\Controllers\UnsubscribeController;
use Whilesmart\Notifications\Models\Notification;
use Whilesmart\Notifications\Push\NullPushSender;
use Whilesmart\Notifications\Support\ConfigurationPreferenceStore;
use Whilesmart\Notifications\Support\UnsubscribeLinks;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifications.php', 'notifications');

        $this->app->bind(
            ResponseFormatter::class,
            fn ($app): ResponseFormatter => $app->make(config('notifications.response_formatter')),
        );
        $this->app->bindIf(PushSender::class, NullPushSender::class);
        $this->app->bindIf(PreferenceStore::class, ConfigurationPreferenceStore::class);
        $this->app->singleton(Notifier::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'notifications');

        $this->publishes([
            __DIR__.'/../config/notifications.php' => config_path('notifications.php'),
        ], 'notifications-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'notifications-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/notifications'),
        ], 'notifications-views');

        if ($this->app->runningInConsole()) {
            $this->commands([ImportLegacyNotifications::class]);
        }

        Route::model('notification', config('notifications.model', Notification::class));

        if (config('notifications.register_routes', true)) {
            Route::middleware(config('notifications.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('notifications.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }

        if (config('notifications.unsubscribe.enabled', true)) {
            Route::middleware(config('notifications.unsubscribe.middleware', ['signed', 'throttle:30,1']))
                ->prefix(config('notifications.unsubscribe.route_prefix', 'notifications'))
                ->group(fn () => Route::match(['get', 'post'], 'unsubscribe/{type}/{channel}', UnsubscribeController::class)
                    ->name(UnsubscribeLinks::ROUTE));
        }
    }
}
