<?php

namespace Modules\Product\Console;

use Illuminate\Console\Command;
use Modules\Product\Database\Seeders\ProductPermissionsSeeder;

class SyncProductPermissionsCommand extends Command
{
    protected $signature = 'products:sync-permissions';

    protected $description = 'Upsert Products dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var ProductPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(ProductPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Products permissions synced.');

        return self::SUCCESS;
    }
}
