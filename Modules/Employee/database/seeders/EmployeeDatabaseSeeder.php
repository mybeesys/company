<?php

namespace Modules\Employee\database\seeders;

use Hash;
use Illuminate\Database\Seeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Establishment\Models\Establishment;

class EmployeeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employee = Employee::updateOrCreate(['email' => 'admin@admin.com'], [
            'name' => 'آدمن',
            'name_en' => 'admin',
            'user_name' => 'admin',
            'password' => Hash::make('12345678'),
            'establishment_id' => Establishment::notMain()->active()->first()?->id,
            'employment_start_date' => now()->format('Y-m-d'),
            'pin' => 99913,
            'ems_access' => true,
            'pos_is_active' => true
        ]);

        $pos_permissions = include base_path('Modules/Employee/data/pos-permissions.php');
        $dashboard_permissions = include base_path('Modules/Employee/data/dashboard-permissions.php');
        $permissions = array_merge($pos_permissions, $dashboard_permissions);
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'type' => $permission['type']],
                [
                    'name_ar' => $permission['name_ar'],
                    'description' => $permission['description'],
                    'description_ar' => $permission['description_ar'],
                    'type' => $permission['type'],
                    'guard_name' => 'web'
                ]
            );
        }

        $permissions = Permission::where('name', 'LIKE', '%all%')->where('type', 'ems')->get();
        $employee->syncPermissions($permissions);
    }
}
