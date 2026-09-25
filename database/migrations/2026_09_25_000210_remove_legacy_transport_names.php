<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Preserve any historical Bank/Voucher row that still has only the legacy
        // transport_name_id. Only referenced legacy names are copied; an unused
        // test row such as `testComp` is intentionally not promoted to Company Master.
        if (Schema::hasTable('transport_names') && Schema::hasTable('companies')) {
            $legacyRows = DB::table('transport_names')->orderBy('id')->get();

            foreach ($legacyRows as $legacy) {
                $usedByBank = Schema::hasColumn('banks', 'transport_name_id')
                    && DB::table('banks')->where('transport_name_id', $legacy->id)->exists();
                $usedByVoucher = Schema::hasColumn('vouchers', 'transport_name_id')
                    && DB::table('vouchers')->where('transport_name_id', $legacy->id)->exists();

                if (! $usedByBank && ! $usedByVoucher) {
                    continue;
                }

                $companyId = DB::table('companies')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $legacy->name)])
                    ->value('id');

                if (! $companyId) {
                    $companyId = DB::table('companies')->insertGetId([
                        'name' => $legacy->name,
                        'gst_no' => (string) ($legacy->gst_no ?? ''),
                        'address' => '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($usedByBank && Schema::hasColumn('banks', 'company_id')) {
                    DB::table('banks')
                        ->where('transport_name_id', $legacy->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $companyId]);
                }

                if ($usedByVoucher && Schema::hasColumn('vouchers', 'company_id')) {
                    DB::table('vouchers')
                        ->where('transport_name_id', $legacy->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $companyId]);
                }
            }
        }

        $this->dropLegacyCompanyColumn('banks', 'transport_name_id', [
            'banks_company_name_unique',
            'banks_company_default_index',
        ]);

        $this->dropLegacyCompanyColumn('vouchers', 'transport_name_id');

        Schema::dropIfExists('transport_names');
    }

    public function down(): void
    {
        if (! Schema::hasTable('transport_names')) {
            Schema::create('transport_names', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('gst_no', 30)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('banks', 'transport_name_id')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->unsignedBigInteger('transport_name_id')->nullable()->after('company_id');
            });
        }

        if (! Schema::hasColumn('vouchers', 'transport_name_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->unsignedBigInteger('transport_name_id')->nullable()->after('company_id');
            });
        }
    }

    private function dropLegacyCompanyColumn(string $table, string $column, array $knownIndexes = []): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $foreignKeys = DB::select(
            'SELECT DISTINCT CONSTRAINT_NAME AS constraint_name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column]
        );

        foreach ($foreignKeys as $foreignKey) {
            $name = str_replace('`', '``', (string) $foreignKey->constraint_name);
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
        }

        foreach ($knownIndexes as $index) {
            if ($this->indexExists($table, $index)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropColumn($column);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return (bool) DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
