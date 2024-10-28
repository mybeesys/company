<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use App\Models\Tenant;
use Modules\Establishment\database\seeders\EstablishmentDatabaseSeeder;
use Modules\UserManagement\database\seeders\UserManagementDatabaseSeeder;
use Modules\Employee\database\seeders\EmployeeDatabaseSeeder;
use Modules\Product\database\Seeders\DiningTypeSeeder;
use Modules\Product\database\seeders\ProductDatabaseSeeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $this->call([
                UserManagementDatabaseSeeder::class,
                EmployeeDatabaseSeeder::class,
                EstablishmentDatabaseSeeder::class,
                ProductDatabaseSeeder::class,
                DiningTypeSeeder::class
            ]);
    }
}
