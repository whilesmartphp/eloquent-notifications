<?php

namespace Whilesmart\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Whilesmart\Notifications\Database\Factories\NotificationFactory;
use Whilesmart\Notifications\Enums\NotificationLevel;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'level' => NotificationLevel::class,
        'data' => 'array',
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Notification $notification) {
            if (config('notifications.uuids', false) && empty($notification->{$notification->getKeyName()})) {
                $notification->{$notification->getKeyName()} = (string) Str::orderedUuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return ! (bool) config('notifications.uuids', false);
    }

    public function getKeyType(): string
    {
        return config('notifications.uuids', false) ? 'string' : 'int';
    }

    public function getTable(): string
    {
        return config('notifications.table', 'notifications');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeFor(Builder $query, Model $notifiable): Builder
    {
        return $query->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey());
    }

    public function isFor(?Model $notifiable): bool
    {
        return $notifiable !== null
            && $this->notifiable_type === $notifiable->getMorphClass()
            && (string) $this->notifiable_id === (string) $notifiable->getKey();
    }

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }
}
