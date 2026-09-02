<?php

namespace Modules\Report\Console;

use Illuminate\Console\Command;
use Modules\Report\Database\Seeders\ReportPermissionsSeeder;

class SyncReportPermissionsCommand extends Command
{
    protected $signature = 'reports:sync-permissions';

    protected $description = 'Upsert general-reports dashboard permissions (EMS) without truncating other permissions';

    public function handle(): int
    {
        /** @var ReportPermissionsSeeder $seeder */
        $seeder = $this->laravel->make(ReportPermissionsSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Report permissions synced.');

        return self::SUCCESS;
    }
}
