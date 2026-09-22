<?php

use App\Casts\EncryptedDecimal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rates, descriptions and company details are stored encrypted with the
 * application key, so a database dump or backup does not reveal them. The
 * columns become text because the ciphertext is far longer than the value.
 *
 * Losing APP_KEY makes these columns unreadable for good.
 */
return new class extends Migration
{
    /**
     * [table => [column => whether it holds an amount]]
     */
    private const COLUMNS = [
        'users' => [
            'hourly_rate' => true,
            'company_name' => false,
            'company_address' => false,
            'company_oib' => false,
            'company_iban' => false,
        ],
        'project_user' => [
            'hourly_rate' => true,
        ],
        'time_entries' => [
            'description' => false,
            'hourly_rate' => true,
        ],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach (array_keys($columns) as $column) {
                    $blueprint->text($column)->nullable()->default(null)->change();
                }
            });

            $this->rewrite($table, $columns, function (string $value, bool $isAmount) {
                $plain = $isAmount ? EncryptedDecimal::format($value) : $value;

                return Crypt::encryptString($plain);
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $this->rewrite($table, $columns, fn (string $value) => Crypt::decryptString($value));
        }

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->default(0)->change();
            $table->string('company_name')->nullable()->change();
            $table->string('company_oib', 11)->nullable()->change();
            $table->string('company_iban', 34)->nullable()->change();
        });

        Schema::table('project_user', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->change();
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
            $table->decimal('hourly_rate', 10, 2)->default(0)->change();
        });
    }

    /**
     * Runs every non-null value of the given columns through $transform, one
     * row at a time, straight on the table so no model casts get involved.
     */
    private function rewrite(string $table, array $columns, callable $transform): void
    {
        DB::table($table)->orderBy('id')->lazyById()->each(
            function (object $row) use ($table, $columns, $transform) {
                $changes = [];

                foreach ($columns as $column => $isAmount) {
                    if ($row->{$column} !== null) {
                        $changes[$column] = $transform((string) $row->{$column}, $isAmount);
                    }
                }

                if ($changes !== []) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            },
        );
    }
};
