<?php

namespace Whilesmart\Notifications\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Notifications\Contracts\PreferenceStore;
use Whilesmart\Notifications\Events\PreferencesUpdated;

class Preferences
{
    public function __construct(private PreferenceStore $store) {}

    /** @return array<string, array> */
    public function channels(): array
    {
        return config('notifications.channels', []);
    }

    /** @return array<string, array> */
    public function types(): array
    {
        return config('notifications.types', []);
    }

    /** @return list<string> */
    public function channelsFor(string $type): array
    {
        return $this->types()[$type]['channels'] ?? array_keys($this->channels());
    }

    public function channelKey(string $channel): ?string
    {
        return array_key_exists('preference_key', $this->channels()[$channel] ?? [])
            ? $this->channels()[$channel]['preference_key']
            : 'notifications-'.$channel;
    }

    public function typeKey(string $type): ?string
    {
        $definition = $this->types()[$type] ?? null;
        if ($definition === null) {
            return null;
        }

        return array_key_exists('preference_key', $definition) ? $definition['preference_key'] : 'notifications-'.$type;
    }

    public function typeChannelKey(string $type, string $channel): ?string
    {
        if (! isset($this->types()[$type])) {
            return null;
        }

        return strtr((string) config('notifications.type_channel_preference_key', 'notifications-{type}-{channel}'), [
            '{type}' => $type,
            '{channel}' => $channel,
        ]);
    }

    public function channelEnabled(Model $notifiable, string $channel): bool
    {
        $key = $this->channelKey($channel);

        return ($key ? $this->store->get($notifiable, $key) : null) ?? (bool) ($this->channels()[$channel]['default'] ?? true);
    }

    public function typeEnabled(Model $notifiable, string $type): bool
    {
        $key = $this->typeKey($type);

        return ($key ? $this->store->get($notifiable, $key) : null) ?? (bool) ($this->types()[$type]['default'] ?? true);
    }

    public function typeChannelEnabled(Model $notifiable, string $type, string $channel): bool
    {
        $key = $this->typeChannelKey($type, $channel);

        return ($key ? $this->store->get($notifiable, $key) : null)
            ?? (bool) ($this->types()[$type]['channel_defaults'][$channel] ?? true);
    }

    public function allows(Model $notifiable, string $type, string $channel): bool
    {
        return isset($this->channels()[$channel])
            && in_array($channel, $this->channelsFor($type), true)
            && $this->channelEnabled($notifiable, $channel)
            && $this->typeEnabled($notifiable, $type)
            && $this->typeChannelEnabled($notifiable, $type, $channel);
    }

    public function describe(Model $notifiable): array
    {
        return [
            'channels' => collect($this->channels())->map(fn (array $channel, string $key) => [
                'key' => $key,
                'label' => $channel['label'] ?? $key,
                'enabled' => $this->channelEnabled($notifiable, $key),
                'configurable' => $this->channelKey($key) !== null,
            ])->values()->all(),
            'types' => collect($this->types())->map(fn (array $type, string $key) => [
                'key' => $key,
                'label' => $type['label'] ?? $key,
                'description' => $type['description'] ?? null,
                'enabled' => $this->typeEnabled($notifiable, $key),
                'configurable' => $this->typeKey($key) !== null,
                'channels' => collect($this->channelsFor($key))
                    ->mapWithKeys(fn (string $channel) => [$channel => $this->typeChannelEnabled($notifiable, $key, $channel)])
                    ->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array{channels?: array<string, bool>, types?: array<string, bool>, type_channels?: array<string, array<string, bool>>}  $changes
     */
    public function update(Model $notifiable, array $changes): void
    {
        $keys = [];
        foreach ($changes['channels'] ?? [] as $channel => $enabled) {
            if ($key = $this->channelKey($channel)) {
                $keys[$key] = (bool) $enabled;
            }
        }
        foreach ($changes['types'] ?? [] as $type => $enabled) {
            if ($key = $this->typeKey($type)) {
                $keys[$key] = (bool) $enabled;
            }
        }
        foreach ($changes['type_channels'] ?? [] as $type => $channels) {
            foreach ($channels as $channel => $enabled) {
                if ($key = $this->typeChannelKey($type, $channel)) {
                    $keys[$key] = (bool) $enabled;
                }
            }
        }
        foreach ($keys as $key => $enabled) {
            $this->store->set($notifiable, $key, $enabled);
        }
        if ($keys !== []) {
            PreferencesUpdated::dispatch($notifiable, $keys);
        }
    }
}
