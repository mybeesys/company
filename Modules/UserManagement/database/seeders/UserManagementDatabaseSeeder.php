<?php

namespace Modules\UserManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class UserManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = new ClientRepository();

        $client->createPasswordGrantClient(null, 'Default password grant client', 'http://'+tenant('id')+'.mybee.live');
        $client->createPersonalAccessClient(null, 'Default personal access client', 'http://'+tenant('id')+'.mybee.live');
    }
}
