<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            // The list is always read newest first, and the running entry is
            // looked up by ended_at being null.
            $table->index('started_at');
            $table->index('ended_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
