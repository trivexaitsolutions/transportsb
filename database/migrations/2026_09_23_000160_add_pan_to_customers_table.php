<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'pan_no')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('pan_no', 20)->nullable()->after('gst_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'pan_no')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('pan_no');
            });
        }
    }
};
