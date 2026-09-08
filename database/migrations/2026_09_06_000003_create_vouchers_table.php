<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sr_no')->unique();
            $table->foreignId('transport_company_id')->constrained()->restrictOnDelete();
            $table->date('lr_date');
            $table->string('lr_no', 100)->nullable();
            $table->foreignId('vehicle_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('lorry_no', 100)->nullable();
            $table->string('so_ref_no', 150)->nullable();
            $table->string('from_place', 150)->nullable();
            $table->string('to_place', 150)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('supplier_freight', 15, 2)->default(0);
            $table->decimal('supplier_advance', 15, 2)->default(0);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->decimal('customer_freight', 15, 2)->default(0);
            $table->decimal('hamali_loading', 15, 2)->default(0);
            $table->decimal('hamali_unloading', 15, 2)->default(0);
            $table->string('bill_no', 100)->nullable();
            $table->decimal('gst', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('lr_date');
            $table->index(['transport_company_id', 'lr_date']);
            $table->index(['customer_id', 'lr_date']);
            $table->index(['supplier_id', 'lr_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
