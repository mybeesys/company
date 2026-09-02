<?php

namespace Modules\Franchise\Console;

use Illuminate\Console\Command;
use Modules\Franchise\database\seeders\FranchisePermissionsSeeder;

class SyncFranchisePermissionsCommand extends Command
{
    protected $signature = 'franchise:sync-permissions';

    protected $description = 'Upsert Franchise dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var FranchisePermissionsSeeder $seeder */
        $seeder = $this->laravel->make(FranchisePermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Franchise permissions synced.');

        return self::SUCCESS;
    }
}
