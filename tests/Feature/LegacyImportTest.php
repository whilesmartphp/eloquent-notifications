<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\User;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    private function legacyTable(): void
    {
        Schema::create('notifications_legacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('type')->default('system');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function rows_are_copied_with_their_ids_and_a_rerun_skips_them(): void
    {
        $this->legacyTable();
        $ada = $this->user();
        DB::table('notifications_legacy')->insert([
            ['id' => 7, 'user_id' => $ada->id, 'type' => 'reminder', 'title' => 'Log today', 'body' => null, 'data' => json_encode(['streak' => 3]), 'read_at' => '2026-01-02 10:00:00', 'created_at' => '2026-01-01 09:00:00', 'updated_at' => '2026-01-02 10:00:00'],
            ['id' => 9, 'user_id' => $ada->id, 'type' => 'achievement', 'title' => 'Ten days', 'body' => 'Keep going', 'data' => null, 'read_at' => null, 'created_at' => '2026-01-03 09:00:00', 'updated_at' => '2026-01-03 09:00:00'],
        ]);

        $this->artisan('notifications:import-legacy', ['table' => 'notifications_legacy', '--user-model' => User::class])
            ->expectsOutput('Copied 2 notifications, skipped 0 already present.')->assertSuccessful();
        $this->artisan('notifications:import-legacy', ['table' => 'notifications_legacy', '--user-model' => User::class])
            ->expectsOutput('Copied 0 notifications, skipped 2 already present.')->assertSuccessful();

        $this->actingAs($ada);
        $this->getJson('/api/notifications')->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.data.0.id', 9)
            ->assertJsonPath('data.data.1.id', 7)
            ->assertJsonPath('data.data.1.data', ['streak' => 3])
            ->assertJsonPath('data.data.1.notifiable_type', $ada->getMorphClass())
            ->assertJsonPath('data.data.1.read_at', '2026-01-02T10:00:00+00:00');
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.unread', 1);
    }

    #[Test]
    public function it_refuses_to_copy_a_table_onto_itself(): void
    {
        $this->artisan('notifications:import-legacy', ['table' => 'notifications', '--user-model' => User::class])->assertFailed();
    }
}
