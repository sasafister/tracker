<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->boolean('billable')->default(true)->after('ended_at');

            // The rate in force when the entry was logged, so changing a rate
            // later does not rewrite what past work was worth.
            $table->decimal('hourly_rate', 10, 2)->default(0)->after('billable');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['billable', 'hourly_rate']);
        });
    }
};
