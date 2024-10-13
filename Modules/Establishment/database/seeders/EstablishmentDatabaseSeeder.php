<?php

namespace Modules\Establishment\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Establishment\Models\Brand;
use Modules\Establishment\Models\Division;
use Modules\Establishment\Models\Establishment;

class EstablishmentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Brand::firstOrCreate([]);
        Division::firstOrCreate([
            'divisionName' => 'test'
        ]);
        Establishment::firstOrCreate(['name' => 'es1']);
        Establishment::firstOrCreate(['name' => 'es2']);
    }
}
