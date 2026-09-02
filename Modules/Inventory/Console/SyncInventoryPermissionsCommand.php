<?php

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\database\seeders\InventoryPermissionsSeeder;

class SyncInventoryPermissionsCommand extends Command
{
    protected $signature = 'inventory:sync-permissions';

    protected $description = 'Upsert Inventory dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var InventoryPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(InventoryPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Inventory permissions synced.');

        return self::SUCCESS;
    }
}
