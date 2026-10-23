<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Notifications\Events\PreferencesUpdated;

class PreferencesApiTest extends TestCase
{
    #[Test]
    public function preferences_are_read_and_saved_through_the_api(): void
    {
        Event::fake([PreferencesUpdated::class]);
        $ada = $this->user();
        $this->actingAs($ada);

        $this->getJson('/api/notifications/preferences')->assertOk()
            ->assertJsonPath('data.channels.1', ['key' => 'email', 'label' => 'Email', 'enabled' => true, 'configurable' => true])
            ->assertJsonPath('data.types.1.key', 'digest')
            ->assertJsonPath('data.types.1.channels', ['inapp' => true, 'email' => false, 'push' => true])
            ->assertJsonPath('data.types.2.configurable', false);

        $this->putJson('/api/notifications/preferences', [
            'channels' => ['push' => false],
            'types' => ['reminder' => false],
            'type_channels' => ['digest' => ['email' => true]],
        ])->assertOk()
            ->assertJsonPath('data.channels.2.enabled', false)
            ->assertJsonPath('data.types.0.enabled', false)
            ->assertJsonPath('data.types.1.channels.email', true);

        $this->assertFalse($ada->getConfigValue('notifications-push'));
        $this->assertFalse($ada->getConfigValue('notifications-reminders'));
        $this->assertTrue($ada->getConfigValue('notifications-digest-email'));
        Event::assertDispatched(PreferencesUpdated::class, fn ($event) => $event->preferences === [
            'notifications-push' => false, 'notifications-reminders' => false, 'notifications-digest-email' => true,
        ]);
    }

    #[Test]
    public function unknown_or_locked_preferences_are_rejected(): void
    {
        $this->actingAs($this->user());

        $this->putJson('/api/notifications/preferences', ['channels' => ['fax' => false]])->assertUnprocessable()->assertJsonValidationErrors('channels.fax');
        $this->putJson('/api/notifications/preferences', ['types' => ['security' => false]])->assertUnprocessable()->assertJsonValidationErrors('types.security');
        $this->putJson('/api/notifications/preferences', ['type_channels' => ['security' => ['push' => false]]])->assertUnprocessable();
        $this->putJson('/api/notifications/preferences', ['types' => ['reminder' => 'maybe']])->assertUnprocessable();
    }
}
