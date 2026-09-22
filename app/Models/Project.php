<?php

namespace App\Models;

use App\Casts\EncryptedDecimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One user's project. Its rate is that user's rate for it; null means the
 * user's default applies.
 */
class Project extends Model
{
    protected $fillable = [
        'client_id',
        'jira_connection_id',
        'jira_project_key',
        'jira_only_mine',
        'name',
        'color',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => EncryptedDecimal::class,
            'jira_only_mine' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function jiraConnection(): BelongsTo
    {
        return $this->belongsTo(JiraConnection::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
