<?php

namespace Modules\Zatca\Console;

use Illuminate\Console\Command;
use Modules\Zatca\Database\Seeders\ZatcaPermissionsSeeder;

class SyncZatcaPermissionsCommand extends Command
{
    protected $signature = 'zatca:sync-permissions';

    protected $description = 'Upsert ZATCA dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        $this->callSilent(ZatcaPermissionsSeeder::class);
        $this->info('ZATCA permissions synced.');

        return self::SUCCESS;
    }
}
