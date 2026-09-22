<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A user's link to a Jira Cloud site, reused by any of their projects;
     * a project can then limit it to one Jira project, and an entry can
     * carry the ticket it was for.
     */
    public function up(): void
    {
        Schema::create('jira_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('site');

            // Encrypted with APP_KEY; the token never leaves the server.
            $table->text('email');
            $table->text('token');

            // Where the token worked: the site itself, or Atlassian's
            // gateway for scoped tokens.
            $table->string('base_url')->nullable();
            $table->string('account_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('jira_connection_id')->nullable()->after('client_id')
                ->constrained()->nullOnDelete();
            $table->string('jira_project_key', 32)->nullable()->after('jira_connection_id');
            $table->boolean('jira_only_mine')->default(false)->after('jira_project_key');
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->string('jira_issue_key', 64)->nullable()->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('jira_issue_key');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jira_connection_id');
            $table->dropColumn(['jira_project_key', 'jira_only_mine']);
        });

        Schema::dropIfExists('jira_connections');
    }
};
