<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_batches', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 80)->unique();
            $table->date('invoice_date');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->unsignedInteger('trip_count')->default(0);
            $table->decimal('customer_freight', 15, 2)->default(0);
            $table->decimal('gst_rate', 7, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'invoice_date']);
            $table->index(['sales_order_id', 'invoice_date']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_batch_id')->constrained('invoice_batches')->cascadeOnDelete();
            $table->foreignId('voucher_id')->unique()->constrained('vouchers')->restrictOnDelete();
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('gst_rate', 7, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_batch_id')->constrained('invoice_batches')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_batch_id')->constrained('invoice_batches')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode', 50)->nullable();
            $table->string('reference', 150)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['invoice_batch_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('invoice_attachments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoice_batches');
    }
};
