<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->string('code',50)->nullable()->unique(); $table->string('name'); $table->string('contact_person')->nullable(); $table->string('phone',30)->nullable(); $table->string('email')->nullable(); $table->string('gst_no',30)->nullable(); $table->text('address')->nullable(); $table->decimal('opening_balance',15,2)->default(0); $table->boolean('is_government_employee')->default(false); $table->text('bill_note')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['name','is_active']);
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id(); $table->string('code',50)->nullable()->unique(); $table->string('name'); $table->string('contact_person')->nullable(); $table->string('phone',30)->nullable(); $table->string('email')->nullable(); $table->string('gst_no',30)->nullable(); $table->text('address')->nullable(); $table->string('bank_name')->nullable(); $table->string('bank_account')->nullable(); $table->string('ifsc',30)->nullable(); $table->decimal('opening_balance',15,2)->default(0); $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['name','is_active']);
        });
        Schema::create('vehicle_types', function (Blueprint $table) { $table->id(); $table->string('name',150)->unique(); $table->string('description')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('transport_names', function (Blueprint $table) { $table->id(); $table->string('name',100)->unique(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('gst_rates', function (Blueprint $table) { $table->id(); $table->string('name',100); $table->decimal('rate',7,2)->unique(); $table->boolean('is_active')->default(true); $table->boolean('is_default')->default(false); $table->timestamps(); });
        Schema::create('print_settings', function (Blueprint $table) { $table->id(); $table->string('letterhead_image')->nullable(); $table->decimal('letterhead_top_margin_mm',8,2)->default(0); $table->timestamps(); });
    }
    public function down(): void { Schema::dropIfExists('print_settings'); Schema::dropIfExists('gst_rates'); Schema::dropIfExists('transport_names'); Schema::dropIfExists('vehicle_types'); Schema::dropIfExists('suppliers'); Schema::dropIfExists('customers'); }
};
