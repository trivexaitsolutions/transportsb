<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number', 150)->unique();
            $table->date('so_date');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('from_location', 180);
            $table->string('to_location', 180);
            $table->text('description');
            $table->unsignedInteger('trips_quantity');
            $table->decimal('per_trip_cost', 15, 2)->default(0);
            $table->decimal('value', 15, 2)->default(0);
            $table->foreignId('gst_rate_id')->nullable()->constrained('gst_rates')->nullOnDelete();
            $table->decimal('gst_rate', 7, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['so_date', 'is_active']);
            $table->index(['customer_id', 'is_active']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sr_no')->unique();
            $table->date('lr_date');
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignId('transport_name_id')->nullable()->constrained('transport_names')->nullOnDelete();
            $table->string('lr_no', 100)->nullable();
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->nullOnDelete();
            $table->string('lorry_number', 100)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('supplier_freight', 15, 2)->default(0);
            $table->decimal('advance_paid', 15, 2)->default(0);
            $table->decimal('hamali_loading', 15, 2)->default(0);
            $table->decimal('hamali_unloading', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lr_date', 'sales_order_id']);
            $table->index(['supplier_id', 'lr_date']);
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode', 50)->nullable();
            $table->string('reference', 150)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['voucher_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('sales_orders');
    }
};
