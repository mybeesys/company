<?php

namespace Modules\Purchases\Console;

use Illuminate\Console\Command;
use Modules\Purchases\database\seeders\PurchasesPermissionsSeeder;

class SyncPurchasesPermissionsCommand extends Command
{
    protected $signature = 'purchases:sync-permissions';

    protected $description = 'Upsert Purchases dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var PurchasesPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(PurchasesPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Purchases permissions synced.');

        return self::SUCCESS;
    }
}
