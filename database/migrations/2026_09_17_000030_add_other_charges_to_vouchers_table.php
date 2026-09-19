<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('vouchers', 'other_charges')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('other_charges', 15, 2)->default(0)->after('hamali_unloading');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vouchers', 'other_charges')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('other_charges');
        });
    }
};
