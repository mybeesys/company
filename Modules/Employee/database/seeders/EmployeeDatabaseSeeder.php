<?php

namespace Modules\Employee\database\seeders;

use Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;

class EmployeeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::firstOrCreate(['email' => 'admin@admin.com'], [
            'name' => 'آدمن',
            'name_en' => 'admin',
            'password' => Hash::make('12345678'),
            'pin' => Crypt::encryptString(13245),
            'ems_access' => true,
            'pos_is_active' => true
        ]);
        // Employee::factory()->count(5)->create();
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
    }
}
