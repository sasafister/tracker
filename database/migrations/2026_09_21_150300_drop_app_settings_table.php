<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The single global rate moved onto each user.
     */
    public function up(): void
    {
        Schema::dropIfExists('app_settings');
    }

    public function down(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });
    }
};
