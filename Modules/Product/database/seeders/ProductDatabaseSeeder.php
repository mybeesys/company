<?php

namespace Modules\Product\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Station;

class ProductDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Station::updateOrCreate([
            'name_ar' => 'POS 1',
            'name_en' => 'POS 1',
            'active' => 1,
        ]);
        Station::updateOrCreate([
            'name_ar' => 'POS 2',
            'name_en' => 'POS 2',
            'active' => 1,
        ]);
    }
}
