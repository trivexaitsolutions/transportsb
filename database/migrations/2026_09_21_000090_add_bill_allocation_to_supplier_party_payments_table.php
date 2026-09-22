<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_party_payments', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('on_account')->after('supplier_id');
            $table->foreignId('voucher_id')->nullable()->after('payment_type')->constrained('vouchers')->restrictOnDelete();
            $table->index(['payment_type', 'voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::table('supplier_party_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_type', 'voucher_id']);
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn('payment_type');
        });
    }
};
