<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('day_number')->unique();
            $table->date('entry_date')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('voucher_day_id')
                ->nullable()
                ->after('id')
                ->constrained('voucher_days')
                ->nullOnDelete();
        });

        // Preserve existing voucher data by creating one sequential Voucher Day
        // for every distinct LR date that already exists.
        $dates = DB::table('vouchers')
            ->whereNotNull('lr_date')
            ->select('lr_date')
            ->distinct()
            ->orderBy('lr_date')
            ->pluck('lr_date');

        $now = now();

        foreach ($dates->values() as $index => $date) {
            $dayId = DB::table('voucher_days')->insertGetId([
                'day_number' => $index + 1,
                'entry_date' => $date,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('vouchers')
                ->where('lr_date', $date)
                ->update(['voucher_day_id' => $dayId]);
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->index(['voucher_day_id', 'sr_no']);
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['voucher_day_id', 'sr_no']);
            $table->dropConstrainedForeignId('voucher_day_id');
        });

        Schema::dropIfExists('voucher_days');
    }
};
