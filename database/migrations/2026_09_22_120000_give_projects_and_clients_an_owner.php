<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Projects and clients stop being shared: each belongs to one user, and a
 * project carries that user's rate itself, so the per-user rate table goes.
 *
 * Existing rows go to the users who used them. A project used by several
 * users is copied, one per user, with that user's entries and rate moved
 * onto the copy; clients follow their projects the same way.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Names are now unique per user, not across the whole app.
        $this->dropNameIndex('projects');
        $this->dropNameIndex('clients');

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

            // Encrypted with APP_KEY, like every other rate.
            $table->text('hourly_rate')->nullable()->after('color');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $this->assignProjects();
        $this->assignClients();

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->unique(['user_id', 'name']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->unique(['user_id', 'name']);
        });

        Schema::dropIfExists('project_user');
    }

    public function down(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('hourly_rate')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // Each owner's rate goes back to the shared table. Copies made on the
        // way up stay as separate projects.
        DB::table('projects')->whereNotNull('hourly_rate')->orderBy('id')->each(function (object $project) {
            DB::table('project_user')->insert([
                'project_id' => $project->id,
                'user_id' => $project->user_id,
                'hourly_rate' => $project->hourly_rate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // MySQL keeps the (user_id, name) index for the foreign key, so the key
        // goes first, then the index, then the column.
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'name']);
            $table->dropColumn(['user_id', 'hourly_rate']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'name']);
            $table->dropColumn('user_id');
        });

        // Names were unique across the app before; that only comes back if
        // no two users ended up with the same name.
        foreach (['projects', 'clients'] as $table) {
            $hasDuplicates = DB::table($table)
                ->select('name')
                ->groupBy('name')
                ->havingRaw('count(*) > 1')
                ->exists();

            if (! $hasDuplicates) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique('name'));
            }
        }
    }

    private function dropNameIndex(string $table): void
    {
        if (Schema::hasIndex($table, ['name'], 'unique')) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique(['name']));
        }
    }

    private function assignProjects(): void
    {
        $firstUserId = DB::table('users')->min('id');

        foreach (DB::table('projects')->orderBy('id')->get() as $project) {
            $rates = DB::table('project_user')
                ->where('project_id', $project->id)
                ->pluck('hourly_rate', 'user_id');

            $userIds = DB::table('time_entries')
                ->where('project_id', $project->id)
                ->distinct()
                ->pluck('user_id')
                ->merge($rates->keys())
                ->unique()
                ->sort()
                ->values();

            if ($userIds->isEmpty()) {
                if ($firstUserId === null) {
                    DB::table('projects')->where('id', $project->id)->delete();

                    continue;
                }

                $userIds = collect([$firstUserId]);
            }

            $owner = $userIds->shift();

            DB::table('projects')->where('id', $project->id)->update([
                'user_id' => $owner,
                'hourly_rate' => $rates->get($owner),
            ]);

            foreach ($userIds as $userId) {
                $copyId = DB::table('projects')->insertGetId([
                    'user_id' => $userId,
                    'client_id' => $project->client_id,
                    'name' => $project->name,
                    'color' => $project->color,
                    'hourly_rate' => $rates->get($userId),
                    'created_at' => $project->created_at,
                    'updated_at' => now(),
                ]);

                DB::table('time_entries')
                    ->where('project_id', $project->id)
                    ->where('user_id', $userId)
                    ->update(['project_id' => $copyId]);
            }
        }
    }

    private function assignClients(): void
    {
        $firstUserId = DB::table('users')->min('id');

        foreach (DB::table('clients')->orderBy('id')->get() as $client) {
            $userIds = DB::table('projects')
                ->where('client_id', $client->id)
                ->distinct()
                ->orderBy('user_id')
                ->pluck('user_id');

            if ($userIds->isEmpty()) {
                if ($firstUserId === null) {
                    DB::table('clients')->where('id', $client->id)->delete();

                    continue;
                }

                $userIds = collect([$firstUserId]);
            }

            $owner = $userIds->shift();

            DB::table('clients')->where('id', $client->id)->update(['user_id' => $owner]);

            foreach ($userIds as $userId) {
                $copyId = DB::table('clients')->insertGetId([
                    'user_id' => $userId,
                    'name' => $client->name,
                    'address' => $client->address,
                    'tax_id' => $client->tax_id,
                    'created_at' => $client->created_at,
                    'updated_at' => now(),
                ]);

                DB::table('projects')
                    ->where('client_id', $client->id)
                    ->where('user_id', $userId)
                    ->update(['client_id' => $copyId]);
            }
        }
    }
};
