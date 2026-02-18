<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        // Bank synchronization
        $schedule->command('banks:sync')->daily();
        
        // Settlement & Reconciliation (Enterprise Requirements)
        $schedule->command('gateway:settle')->dailyAt('02:00');
        $schedule->command('gateway:reconcile')->dailyAt('03:00');
        
        // Auto-refund stale transactions
        $schedule->job(new \App\Jobs\ProcessStaleTransactionsJob)->hourly();
        
        // Sandbox reset (24-hour cycle)
        if (config('app.env') === 'sandbox' || config('app.sandbox_mode', false)) {
            $schedule->command('sandbox:reset')->dailyAt('00:00');
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
