<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Tests\Fixtures\User;
use Whilesmart\Notifications\NotificationsServiceProvider;
use Whilesmart\OwnerAccess\OwnerAccessServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/whilesmart/eloquent-model-configuration/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            OwnerAccessServiceProvider::class,
            NotificationsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('notifications.route_middleware', ['api']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('notifications.types', [
            'reminder' => ['label' => 'Reminders', 'preference_key' => 'notifications-reminders'],
            'digest' => ['label' => 'Weekly digest', 'channel_defaults' => ['email' => false]],
            'security' => ['label' => 'Security', 'preference_key' => null, 'channels' => ['inapp', 'email']],
        ]);
    }

    protected function user(array $attributes = []): User
    {
        return User::create($attributes + ['first_name' => 'Ada', 'email' => 'ada@example.com']);
    }
}
