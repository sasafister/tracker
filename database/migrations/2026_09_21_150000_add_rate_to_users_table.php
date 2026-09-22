<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The user's own rate is the fallback for any project that has no
        // rate of its own for them.
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->default(0)->after('password');
            $table->string('currency', 3)->default('EUR')->after('hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['hourly_rate', 'currency']);
        });
    }
};
