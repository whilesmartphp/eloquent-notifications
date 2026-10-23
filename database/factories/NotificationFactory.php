<?php

namespace Whilesmart\Notifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Notifications\Models\Notification;

/** @extends Factory<Notification> */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'owner_type' => 'App\\Models\\User',
            'owner_id' => 1,
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'type' => 'system',
            'level' => 'info',
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(),
        ];
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
