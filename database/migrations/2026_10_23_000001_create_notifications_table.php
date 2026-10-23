<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $uuids = (bool) config('notifications.uuids', false);

        Schema::create(config('notifications.table', 'notifications'), function (Blueprint $table) use ($uuids) {
            if ($uuids) {
                $table->uuid('id')->primary();
                $table->uuidMorphs('owner');
                $table->uuidMorphs('notifiable');
            } else {
                $table->id();
                $table->morphs('owner');
                $table->morphs('notifiable');
            }

            $table->string('type');
            $table->string('level')->default('info');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url', 2048)->nullable();
            $table->string('action_label')->nullable();
            $table->json('data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index(['notifiable_type', 'notifiable_id', 'created_at']);
            $table->index(['owner_type', 'owner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notifications.table', 'notifications'));
    }
};
