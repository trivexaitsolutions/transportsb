<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('so_number_series', function (Blueprint $table) {
            $table->id();
            $table->string('series_type', 20)->unique();
            $table->string('prefix', 100)->default('');
            $table->unsignedBigInteger('start_number')->default(1);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('number_digits')->default(4);
            $table->string('suffix', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('so_number_series');
    }
};
