<?php

namespace Whilesmart\Notifications\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\ModelConfiguration\Enums\ConfigValueType;
use Whilesmart\Notifications\Contracts\PreferenceStore;

/** Stores preferences as boolean keys through whilesmart/eloquent-model-configuration. */
class ConfigurationPreferenceStore implements PreferenceStore
{
    public function get(Model $notifiable, string $key): ?bool
    {
        if (! method_exists($notifiable, 'getConfigValue')) {
            return null;
        }
        $value = $notifiable->getConfigValue($key);

        return $value === null ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function set(Model $notifiable, string $key, bool $enabled): void
    {
        if (! method_exists($notifiable, 'setConfigValue')) {
            throw new \LogicException(get_class($notifiable).' must use the Configurable trait to store notification preferences.');
        }
        $notifiable->setConfigValue($key, $enabled, ConfigValueType::Boolean);
    }
}
