<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('bank_transactions', 'affect_cash_in_hand')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                // Default false at DB level so auto-created bank entries from
                // Customer/Supplier payments never affect Cash in Hand by accident.
                // The manual Bank Transaction form sends true by default.
                $table->boolean('affect_cash_in_hand')
                    ->default(false)
                    ->after('source_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bank_transactions', 'affect_cash_in_hand')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropColumn('affect_cash_in_hand');
            });
        }
    }
};
