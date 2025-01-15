<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\Accounting\database\seeders\AccountingDatabaseSeeder;

use Modules\Establishment\database\seeders\EstablishmentDatabaseSeeder;
use Modules\Employee\database\seeders\EmployeeDatabaseSeeder;
use Modules\General\database\seeders\GeneralDatabaseSeeder;
use Modules\Product\database\seeders\DiningTypeSeeder;
use Modules\Product\database\seeders\ProductDatabaseSeeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
            $this->call([
                EmployeeDatabaseSeeder::class,
                EstablishmentDatabaseSeeder::class,
                ProductDatabaseSeeder::class,
                DiningTypeSeeder::class,
                AccountingDatabaseSeeder::class,
                GeneralDatabaseSeeder::class
            ]);
    }
}