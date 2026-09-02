<?php

namespace Modules\Employee\Console;

use Illuminate\Console\Command;
use Modules\Employee\database\seeders\EmployeePermissionsSeeder;

class SyncEmployeePermissionsCommand extends Command
{
    protected $signature = 'employees:sync-permissions';

    protected $description = 'Upsert Employees dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var EmployeePermissionsSeeder $seeder */
        $seeder = $this->laravel->make(EmployeePermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Employees permissions synced.');

        return self::SUCCESS;
    }
}
