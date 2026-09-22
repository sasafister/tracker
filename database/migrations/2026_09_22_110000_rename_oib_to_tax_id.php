<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The field holds a foreign VAT ID as often as an OIB, so it is named for
 * both. The encrypted values carry over untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->renameColumn('oib', 'tax_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('company_oib', 'company_tax_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->renameColumn('tax_id', 'oib');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('company_tax_id', 'company_oib');
        });
    }
};
