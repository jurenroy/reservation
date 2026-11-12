<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['MMD', 'FAD', 'GD', 'MSESDD', 'ORD'] as $name) {
            Division::firstOrCreate(['name' => $name]);
        }
    }
}
