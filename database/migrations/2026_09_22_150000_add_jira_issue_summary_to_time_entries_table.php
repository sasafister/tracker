<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The ticket's title as it was when picked, so the entry can show it
     * next to the key without asking Jira again. Encrypted like descriptions.
     */
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->text('jira_issue_summary')->nullable()->after('jira_issue_key');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('jira_issue_summary');
        });
    }
};
