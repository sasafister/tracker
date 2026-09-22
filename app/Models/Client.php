<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company one user does work for. Each user keeps their own.
 */
class Client extends Model
{
    protected $fillable = [
        'name',
        'address',
        'tax_id',
    ];

    protected function casts(): array
    {
        return [
            'address' => 'encrypted',
            'tax_id' => 'encrypted',
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
}
