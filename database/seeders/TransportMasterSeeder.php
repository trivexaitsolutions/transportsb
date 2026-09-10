<?php

namespace Database\Seeders;

use App\Models\GstRate;
use App\Models\TransportCompany;
use Illuminate\Database\Seeder;

class TransportMasterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['BGT', 'LST'] as $name) {
            TransportCompany::query()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }

        foreach ([0, 5, 18, 28] as $rate) {
            GstRate::query()->firstOrCreate(
                ['rate' => $rate],
                ['name' => $rate.'%', 'is_active' => true],
            );
        }
    }
}
