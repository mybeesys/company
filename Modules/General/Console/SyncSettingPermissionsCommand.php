<?php

namespace Modules\General\Console;

use Illuminate\Console\Command;
use Modules\General\database\seeders\SettingPermissionsSeeder;

class SyncSettingPermissionsCommand extends Command
{
    protected $signature = 'settings:sync-permissions';

    protected $description = 'Upsert Settings dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var SettingPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(SettingPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Settings permissions synced.');

        return self::SUCCESS;
    }
}
