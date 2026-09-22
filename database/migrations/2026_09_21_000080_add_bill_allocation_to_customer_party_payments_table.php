<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_party_payments', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('on_account');
            $table->foreignId('invoice_batch_id')->nullable()->constrained('invoice_batches')->restrictOnDelete();
            $table->index(['payment_type', 'invoice_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_party_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_type', 'invoice_batch_id']);
            $table->dropConstrainedForeignId('invoice_batch_id');
            $table->dropColumn('payment_type');
        });
    }
};
