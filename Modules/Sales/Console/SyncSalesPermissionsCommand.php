<?php

namespace Modules\Sales\Console;

use Illuminate\Console\Command;
use Modules\Sales\database\Seeders\SalesPermissionsSeeder;

class SyncSalesPermissionsCommand extends Command
{
    protected $signature = 'sales:sync-permissions';

    protected $description = 'Upsert Sales dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var SalesPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(SalesPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Sales permissions synced.');

        return self::SUCCESS;
    }
}
