<?php

namespace Modules\Accounting\Console;

use Illuminate\Console\Command;
use Modules\Accounting\database\seeders\AccountingPermissionsSeeder;

class SyncAccountingPermissionsCommand extends Command
{
    protected $signature = 'accounting:sync-permissions';

    protected $description = 'Upsert Accounting dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var AccountingPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(AccountingPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Accounting permissions synced.');

        return self::SUCCESS;
    }
}
