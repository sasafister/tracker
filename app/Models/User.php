<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\EncryptedDecimal;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'hourly_rate',
    'currency',
    'locale',
    'company_name',
    'company_address',
    'company_tax_id',
    'company_iban',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Stored encrypted with APP_KEY; see the encrypt_sensitive_columns migration.
            'hourly_rate' => EncryptedDecimal::class,
            'company_name' => 'encrypted',
            'company_address' => 'encrypted',
            'company_tax_id' => 'encrypted',
            'company_iban' => 'encrypted',
        ];
    }

    /**
     * The column is encrypted text and cannot carry a default, so a new
     * user's rate is set here.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->hourly_rate ??= 0;
        });
    }

    /**
     * Emails, like the password reset, go out in the user's language.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?? 'hr';
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * What an hour on the given project is worth for this user: the rate set
     * on their project if there is one, otherwise their own default.
     */
    public function hourlyRateFor(?int $projectId): string
    {
        if ($projectId !== null) {
            $projectRate = $this->projects()->whereKey($projectId)->first()?->hourly_rate;

            if ($projectRate !== null) {
                return $projectRate;
            }
        }

        return $this->hourly_rate ?? '0.00';
    }
}
