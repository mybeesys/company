<?php

namespace Modules\Screen\Console;

use Illuminate\Console\Command;
use Modules\Screen\database\seeders\ScreenPermissionsSeeder;

class SyncScreenPermissionsCommand extends Command
{
    protected $signature = 'screens:sync-permissions';

    protected $description = 'Upsert Screens dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var ScreenPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(ScreenPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Screens permissions synced.');

        return self::SUCCESS;
    }
}
