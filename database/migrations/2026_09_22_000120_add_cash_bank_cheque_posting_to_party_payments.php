<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('customer_party_payments', 'bank_id')) {
            Schema::table('customer_party_payments', function (Blueprint $table) {
                $table->foreignId('bank_id')->nullable()->after('payment_mode')->constrained('banks')->restrictOnDelete();
                $table->string('cheque_status', 20)->nullable()->after('bank_id');
                $table->date('cheque_cleared_date')->nullable()->after('cheque_status');
                $table->index(['payment_mode', 'cheque_status'], 'customer_party_payments_mode_cheque_index');
            });
        }

        if (! Schema::hasColumn('supplier_party_payments', 'bank_id')) {
            Schema::table('supplier_party_payments', function (Blueprint $table) {
                $table->foreignId('bank_id')->nullable()->after('payment_mode')->constrained('banks')->restrictOnDelete();
                $table->string('cheque_status', 20)->nullable()->after('bank_id');
                $table->date('cheque_cleared_date')->nullable()->after('cheque_status');
                $table->index(['payment_mode', 'cheque_status'], 'supplier_party_payments_mode_cheque_index');
            });
        }

        if (! Schema::hasColumn('bank_transactions', 'source_type')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->string('source_type', 40)->nullable()->after('remarks');
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->unique(['source_type', 'source_id'], 'bank_transactions_source_unique');
            });
        }

        if (! Schema::hasTable('cash_transactions')) {
            Schema::create('cash_transactions', function (Blueprint $table) {
                $table->id();
                $table->date('transaction_date');
                $table->enum('type', ['deposit', 'withdraw']);
                $table->decimal('amount', 15, 2);
                $table->string('remarks', 500)->nullable();
                $table->string('source_type', 40);
                $table->unsignedBigInteger('source_id');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['source_type', 'source_id'], 'cash_transactions_source_unique');
                $table->index(['transaction_date', 'type']);
            });
        }

        // Existing cheque payments were already counted by the old system.
        // Keep them posted in ledgers instead of unexpectedly changing old balances.
        if (Schema::hasColumn('customer_party_payments', 'cheque_status')) {
            DB::table('customer_party_payments')
                ->whereRaw('LOWER(COALESCE(payment_mode, "")) = ?', ['cheque'])
                ->whereNull('cheque_status')
                ->update(['cheque_status' => 'cleared']);
        }

        if (Schema::hasColumn('supplier_party_payments', 'cheque_status')) {
            DB::table('supplier_party_payments')
                ->whereRaw('LOWER(COALESCE(payment_mode, "")) = ?', ['cheque'])
                ->whereNull('cheque_status')
                ->update(['cheque_status' => 'cleared']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');

        if (Schema::hasColumn('bank_transactions', 'source_type')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropUnique('bank_transactions_source_unique');
                $table->dropColumn(['source_type', 'source_id']);
            });
        }

        if (Schema::hasColumn('supplier_party_payments', 'bank_id')) {
            Schema::table('supplier_party_payments', function (Blueprint $table) {
                $table->dropIndex('supplier_party_payments_mode_cheque_index');
                $table->dropConstrainedForeignId('bank_id');
                $table->dropColumn(['cheque_status', 'cheque_cleared_date']);
            });
        }

        if (Schema::hasColumn('customer_party_payments', 'bank_id')) {
            Schema::table('customer_party_payments', function (Blueprint $table) {
                $table->dropIndex('customer_party_payments_mode_cheque_index');
                $table->dropConstrainedForeignId('bank_id');
                $table->dropColumn(['cheque_status', 'cheque_cleared_date']);
            });
        }
    }
};
