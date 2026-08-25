<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Register your commands here
        \Modules\Inventory\Notifications\CheckLowStockReminder::class,
        \Modules\Zatca\Console\SyncPendingZatcaSellsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inventory:ten-minute-reminder')->everyMinute()
            ->sendOutputTo(storage_path('logs/inventory_schedule_output.log'));

        $schedule->command('zatca:sync-pending-sells')->dailyAt('00:00')
            ->timezone('Asia/Riyadh')
            ->sendOutputTo(storage_path('logs/zatca_daily_sync.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Modules/Inventory/Notifications');
        $this->load(base_path('Modules/Zatca/Console'));

        require base_path('routes/console.php');
    }
}
