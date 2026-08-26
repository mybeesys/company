<?php

namespace Modules\Zatca\Console;

use Illuminate\Console\Command;
use Modules\Zatca\database\Seeders\ZatcaPermissionsSeeder;

class SyncZatcaPermissionsCommand extends Command
{
    protected $signature = 'zatca:sync-permissions';

    protected $description = 'Upsert ZATCA dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var ZatcaPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(ZatcaPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('ZATCA permissions synced.');

        return self::SUCCESS;
    }
}
