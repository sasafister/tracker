<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });

        // The application is single-user, so settings are one row that is
        // edited in place rather than created by anyone.
        DB::table('app_settings')->insert([
            'hourly_rate' => 0,
            'currency' => 'EUR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
