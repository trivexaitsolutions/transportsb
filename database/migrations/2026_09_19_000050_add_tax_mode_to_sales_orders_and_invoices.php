<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'tax_mode')) {
                $table->string('tax_mode', 20)->default('rcm')->after('gst_rate_id');
            }
        });

        Schema::table('invoice_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_batches', 'tax_mode')) {
                $table->string('tax_mode', 20)->default('rcm')->after('sales_order_id');
            }
            if (! Schema::hasColumn('invoice_batches', 'rcm_rate')) {
                $table->decimal('rcm_rate', 7, 2)->default(0)->after('gst_amount');
            }
            if (! Schema::hasColumn('invoice_batches', 'rcm_amount')) {
                $table->decimal('rcm_amount', 15, 2)->default(0)->after('rcm_rate');
            }
        });

        DB::table('sales_orders')->where('gst_amount', '>', 0)->update(['tax_mode' => 'hiring']);
        DB::table('sales_orders')->where('gst_amount', '<=', 0)->update(['tax_mode' => 'rcm', 'gst_rate_id' => null, 'gst_rate' => 0, 'gst_amount' => 0]);

        DB::table('invoice_batches')->where('gst_amount', '>', 0)->update(['tax_mode' => 'hiring', 'rcm_rate' => 0, 'rcm_amount' => 0]);
        DB::table('invoice_batches')->where('gst_amount', '<=', 0)->update(['tax_mode' => 'rcm', 'gst_rate' => 0, 'gst_amount' => 0, 'rcm_rate' => 5]);

        foreach (DB::table('invoice_batches')->where('tax_mode', 'rcm')->select('id', 'customer_freight')->get() as $invoice) {
            DB::table('invoice_batches')->where('id', $invoice->id)->update([
                'rcm_amount' => round(((float) $invoice->customer_freight) * 0.05, 2),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('invoice_batches', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_batches', 'rcm_amount')) {
                $table->dropColumn('rcm_amount');
            }
            if (Schema::hasColumn('invoice_batches', 'rcm_rate')) {
                $table->dropColumn('rcm_rate');
            }
            if (Schema::hasColumn('invoice_batches', 'tax_mode')) {
                $table->dropColumn('tax_mode');
            }
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'tax_mode')) {
                $table->dropColumn('tax_mode');
            }
        });
    }
};
