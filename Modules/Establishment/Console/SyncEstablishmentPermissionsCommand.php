<?php

namespace Modules\Establishment\Console;

use Illuminate\Console\Command;
use Modules\Establishment\database\seeders\EstablishmentPermissionsSeeder;

class SyncEstablishmentPermissionsCommand extends Command
{
    protected $signature = 'establishments:sync-permissions';

    protected $description = 'Upsert Establishments dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var EstablishmentPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(EstablishmentPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Establishments permissions synced.');

        return self::SUCCESS;
    }
}
