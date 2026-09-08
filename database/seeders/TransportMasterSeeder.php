<?php

namespace Database\Seeders;

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
    }
}
