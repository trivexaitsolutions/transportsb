<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_party_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode', 50)->nullable();
            $table->string('reference', 150)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('attachment_mime', 120)->nullable();
            $table->unsignedBigInteger('attachment_size')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'payment_date']);
            $table->index('payment_date');
        });

        Schema::create('customer_party_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode', 50)->nullable();
            $table->string('reference', 150)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('attachment_mime', 120)->nullable();
            $table->unsignedBigInteger('attachment_size')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'payment_date']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_party_payments');
        Schema::dropIfExists('supplier_party_payments');
    }
};
