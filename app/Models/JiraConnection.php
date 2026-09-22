<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One user's API token for one Jira Cloud site.
 */
class JiraConnection extends Model
{
    protected $fillable = [
        'name',
        'site',
        'email',
        'token',
        'base_url',
        'account_name',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'token' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function issueUrl(string $key): string
    {
        return "https://{$this->site}/browse/{$key}";
    }
}
