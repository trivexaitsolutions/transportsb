<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gst_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->decimal('rate', 5, 2)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        foreach ([0, 5, 18, 28] as $rate) {
            DB::table('gst_rates')->insert([
                'name' => $rate.'%',
                'rate' => $rate,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('gst_rate_id')
                ->nullable()
                ->after('bill_no')
                ->constrained('gst_rates')
                ->restrictOnDelete();
        });

        $zeroId = DB::table('gst_rates')->where('rate', 0)->value('id');
        if ($zeroId) {
            DB::table('vouchers')->whereNull('gst_rate_id')->update(['gst_rate_id' => $zeroId]);
        }
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gst_rate_id');
        });

        Schema::dropIfExists('gst_rates');
    }
};
