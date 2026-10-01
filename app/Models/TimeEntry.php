<?php

namespace App\Models;

use App\Casts\EncryptedDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    protected $fillable = [
        'description',
        'project_id',
        'jira_issue_key',
        'jira_issue_summary',
        'jira_issues',
        'started_at',
        'ended_at',
        'billable',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'billable' => 'boolean',
            // Stored encrypted with APP_KEY, so they cannot be searched in SQL.
            'description' => 'encrypted',
            'jira_issue_summary' => 'encrypted',
            'jira_issues' => 'encrypted:array',
            'hourly_rate' => EncryptedDecimal::class,
        ];
    }

    /**
     * The rate is taken when the entry is logged and again whenever it moves
     * to another project, never from the client.
     */
    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry) {
            $needsRate = ! $entry->exists || $entry->isDirty('project_id');

            if ($needsRate) {
                $entry->hourly_rate = $entry->user->hourlyRateFor($entry->project_id);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * An entry with no end is the one currently counting. At most one exists
     * per user at a time — starting a new entry stops whatever was running.
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function getDurationInSecondsAttribute(): int
    {
        $end = $this->ended_at ?? now();

        return max(0, $end->diffInSeconds($this->started_at, absolute: true));
    }
}
