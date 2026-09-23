<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('customer_party_payments', 'tds_percent')) {
            Schema::table('customer_party_payments', function (Blueprint $table) {
                $table->decimal('tds_percent', 5, 2)->default(0)->after('amount');
                $table->decimal('tds_amount', 15, 2)->default(0)->after('tds_percent');
                $table->decimal('net_amount', 15, 2)->nullable()->after('tds_amount');
            });
        }

        DB::table('customer_party_payments')
            ->whereNull('net_amount')
            ->update([
                'tds_percent' => 0,
                'tds_amount' => 0,
                'net_amount' => DB::raw('amount'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_party_payments', 'tds_percent')) {
            Schema::table('customer_party_payments', function (Blueprint $table) {
                $table->dropColumn(['tds_percent', 'tds_amount', 'net_amount']);
            });
        }
    }
};
