<?php

namespace Modules\UserManagement\database\seeders;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Passport\ClientRepository;

use Illuminate\Database\Seeder;

class UserManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          echo "Seeding complete for tenant: " . tenant('id'). "\n";
          
          $client = new ClientRepository();

        $client->createPasswordGrantClient(null, 'Default password grant client',tenant('id') . "." . env("APP_DOMAIN") );
        $client->createPersonalAccessClient(null, 'Default personal access client', tenant('id') . "." . env("APP_DOMAIN"));

        User::firstOrNew([
            "name"=> "admin",
            "email"=> "admin@admin.com",
            "password"=> bcrypt("12345678"),
        ]);
    }
}
