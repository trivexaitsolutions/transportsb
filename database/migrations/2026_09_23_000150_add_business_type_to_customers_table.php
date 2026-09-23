<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'business_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('business_type', 10)->default('b2b')->after('gst_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'business_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('business_type');
            });
        }
    }
};
