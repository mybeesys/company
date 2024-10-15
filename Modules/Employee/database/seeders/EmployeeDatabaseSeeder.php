<?php

namespace Modules\Employee\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;

class EmployeeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::factory()->count(10)->create();
        $permissions = include base_path('Modules/Employee/data/pos-permissions.php');
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'name_ar' => $permission['name_ar'],
                    'description' => $permission['description'],
                    'description_ar' => $permission['description_ar'],
                    'type' => $permission['type'],
                    'guard_name' => 'web'
                ]
            );
        }
    }
}
