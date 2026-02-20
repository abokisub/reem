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

        // Settlement processing (check every 5 mins for due settlements)
        $schedule->command('settlements:process')->everyFiveMinutes();

        // Settlement & Reconciliation (Enterprise Requirements)
        $schedule->command('gateway:settle')->dailyAt('02:00');
        $schedule->command('gateway:reconcile')->dailyAt('03:00');

        // Auto-refund stale transactions
        $schedule->job(new \App\Jobs\ProcessStaleTransactionsJob)->hourly();

        // Webhook Retry Engine (Exponential Backoff)
        $schedule->command('webhooks:retry')->everyMinute()->withoutOverlapping();

        // Automated Reconciliation (every 10 minutes)
        $schedule->command('reconcile:auto')->everyTenMinutes()->withoutOverlapping();

        // Transaction Reconciliation (every 5 minutes)
        $schedule->command('transactions:reconcile')->everyFiveMinutes()->withoutOverlapping();

        // Cleanup old logs (webhook and API request logs older than 48 hours)
        $schedule->command('logs:cleanup')->hourly();

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
