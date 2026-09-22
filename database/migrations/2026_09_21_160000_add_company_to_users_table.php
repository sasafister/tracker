<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The business the user bills through, printed at the top of reports.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('currency');
            $table->text('company_address')->nullable()->after('company_name');
            $table->string('company_oib', 11)->nullable()->after('company_address');
            $table->string('company_iban', 34)->nullable()->after('company_oib');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_address',
                'company_oib',
                'company_iban',
            ]);
        });
    }
};
