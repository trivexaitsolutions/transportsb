<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('banks', 'company_id')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id', 'banks_company_id_index');
            });
        }

        if (! Schema::hasColumn('vouchers', 'company_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('sales_order_id');
                $table->index(['company_id', 'lr_date'], 'vouchers_company_date_index');
            });
        }

        // Map old BGT/LST Transport Name records to the new Company Master.
        // If a matching Company was not created manually yet, create a safe placeholder
        // using the old name/GST so historical Bank/Voucher records never lose ownership.
        if (Schema::hasTable('transport_names') && Schema::hasTable('companies')) {
            $transportNames = DB::table('transport_names')->orderBy('id')->get();

            foreach ($transportNames as $transportName) {
                $isUsed = DB::table('banks')->where('transport_name_id', $transportName->id)->exists()
                    || DB::table('vouchers')->where('transport_name_id', $transportName->id)->exists();

                if (! $isUsed) {
                    continue;
                }

                $companyId = DB::table('companies')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $transportName->name)])
                    ->value('id');

                if (! $companyId) {
                    $companyId = DB::table('companies')->insertGetId([
                        'name' => $transportName->name,
                        'gst_no' => (string) ($transportName->gst_no ?? ''),
                        'address' => '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('banks')
                    ->where('transport_name_id', $transportName->id)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);

                DB::table('vouchers')
                    ->where('transport_name_id', $transportName->id)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);
            }
        }

        Schema::table('banks', function (Blueprint $table) {
            $table->foreign('company_id', 'banks_company_id_foreign')
                ->references('id')->on('companies')->restrictOnDelete();
            $table->unique(['company_id', 'name'], 'banks_company_master_name_unique');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreign('company_id', 'vouchers_company_id_foreign')
                ->references('id')->on('companies')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('vouchers', 'company_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropForeign('vouchers_company_id_foreign');
                $table->dropIndex('vouchers_company_date_index');
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasColumn('banks', 'company_id')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->dropUnique('banks_company_master_name_unique');
                $table->dropForeign('banks_company_id_foreign');
                $table->dropIndex('banks_company_id_index');
                $table->dropColumn('company_id');
            });
        }
    }
};
