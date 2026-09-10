<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('other_charges', 15, 2)->default(0)->after('hamali_unloading');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->text('bill_note')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('other_charges');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('bill_note');
        });
    }
};
