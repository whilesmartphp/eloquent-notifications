<?php

namespace Whilesmart\Notifications\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies a user-keyed notifications table (id, user_id, type, title, body,
 * data, read_at, timestamps) into the package table. Ids are kept so clients
 * that sync by id keep working, and rows already copied are skipped.
 */
class ImportLegacyNotifications extends Command
{
    protected $signature = 'notifications:import-legacy
        {table : The existing table to copy from}
        {--user-model= : The model the user column points at}
        {--user-column=user_id : The column holding the recipient id}
        {--chunk=500 : Rows copied per batch}';

    protected $description = 'Copy notifications from a user-keyed table into the package table';

    public function handle(): int
    {
        $source = (string) $this->argument('table');
        $target = (string) config('notifications.table', 'notifications');
        $userModel = (string) ($this->option('user-model') ?: config('auth.providers.users.model'));
        $userColumn = (string) $this->option('user-column');

        if ($source === $target) {
            $this->error('Rename the existing table first; the package table is created under the configured name.');

            return self::FAILURE;
        }
        if (! Schema::hasTable($source) || ! Schema::hasTable($target)) {
            $this->error("Both [{$source}] and [{$target}] must exist. Run the package migrations first.");

            return self::FAILURE;
        }
        if (! is_a($userModel, Model::class, true)) {
            $this->error('Pass --user-model with the recipient model class.');

            return self::FAILURE;
        }

        $morph = (new $userModel)->getMorphClass();
        $columns = Schema::getColumnListing($source);
        $copied = 0;
        $skipped = 0;

        DB::table($source)->orderBy('id')->chunkById((int) $this->option('chunk'), function ($rows) use ($target, $morph, $userColumn, $columns, &$copied, &$skipped) {
            $existing = DB::table($target)->whereIn('id', $rows->pluck('id'))->pluck('id')->map(fn ($id) => (string) $id)->all();
            $inserts = [];
            foreach ($rows as $row) {
                if (in_array((string) $row->id, $existing, true)) {
                    $skipped++;

                    continue;
                }
                $inserts[] = [
                    'id' => $row->id,
                    'owner_type' => $morph,
                    'owner_id' => $row->{$userColumn},
                    'notifiable_type' => $morph,
                    'notifiable_id' => $row->{$userColumn},
                    'type' => $row->type ?? 'system',
                    'level' => in_array('level', $columns, true) && $row->level ? $row->level : 'info',
                    'title' => $row->title,
                    'body' => $row->body ?? null,
                    'data' => $row->data ?? null,
                    'metadata' => in_array('metadata', $columns, true) ? $row->metadata : null,
                    'read_at' => $row->read_at ?? null,
                    'created_at' => $row->created_at ?? null,
                    'updated_at' => $row->updated_at ?? null,
                    'deleted_at' => in_array('deleted_at', $columns, true) ? $row->deleted_at : null,
                ];
            }
            if ($inserts) {
                DB::table($target)->insert($inserts);
                $copied += count($inserts);
            }
        });

        $this->resetSequence($target);
        $this->info("Copied {$copied} notifications, skipped {$skipped} already present.");

        return self::SUCCESS;
    }

    /** Postgres keeps its own id sequence, which explicit ids do not advance. */
    private function resetSequence(string $table): void
    {
        if (DB::getDriverName() !== 'pgsql' || config('notifications.uuids', false)) {
            return;
        }
        DB::statement("select setval(pg_get_serial_sequence(?, 'id'), coalesce(max(id), 1), max(id) is not null) from \"{$table}\"", [$table]);
    }
}
