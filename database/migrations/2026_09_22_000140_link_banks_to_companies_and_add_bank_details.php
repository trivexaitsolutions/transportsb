<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('transport_names', 'gst_no')) {
            Schema::table('transport_names', function (Blueprint $table) {
                $table->string('gst_no', 30)->nullable()->after('name');
            });
        }

        // The original bank master allowed one globally unique bank name. Once banks are
        // company-wise, BGT and LST can both legitimately have (for example) an HDFC Bank.
        Schema::table('banks', function (Blueprint $table) {
            $table->dropUnique('banks_name_unique');
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->foreignId('transport_name_id')->nullable()->after('id')->constrained('transport_names')->restrictOnDelete();
            $table->string('account_holder_name')->nullable()->after('name');
            $table->string('account_number', 100)->nullable()->after('account_holder_name');
            $table->string('account_type', 100)->nullable()->after('account_number');
            $table->string('ifsc_code', 30)->nullable()->after('account_type');
            $table->string('branch_name')->nullable()->after('ifsc_code');
            $table->text('bank_address')->nullable()->after('branch_name');
            $table->boolean('is_default')->default(false)->after('opening_balance');
            $table->unique(['transport_name_id', 'name'], 'banks_company_name_unique');
            $table->index(['transport_name_id', 'is_active', 'is_default'], 'banks_company_default_index');
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropUnique('banks_company_name_unique');
            $table->dropIndex('banks_company_default_index');
            $table->dropForeign(['transport_name_id']);
            $table->dropColumn([
                'transport_name_id',
                'account_holder_name',
                'account_number',
                'account_type',
                'ifsc_code',
                'branch_name',
                'bank_address',
                'is_default',
            ]);
            $table->unique('name');
        });

        if (Schema::hasColumn('transport_names', 'gst_no')) {
            Schema::table('transport_names', function (Blueprint $table) {
                $table->dropColumn('gst_no');
            });
        }
    }
};
