<?php

namespace Modules\Product\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductUnitTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('product_unit_transfer')->insert([
            [
                'product_id' => null,
                'ingredient_id' => null,
                'modifier_id' => null,
                'unit1' => 'حبة',
                'unit2' => null,
                'transfer' => -100,
                'default' => 1,
                'primary' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
