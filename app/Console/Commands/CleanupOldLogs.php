<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete webhook logs and API request logs older than 48 hours';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting log cleanup...');

        $cutoffTime = Carbon::now()->subHours(48);

        // Delete old webhook logs
        $webhookDeleted = DB::table('webhook_logs')
            ->where('created_at', '<', $cutoffTime)
            ->delete();

        $this->info("Deleted {$webhookDeleted} webhook logs older than 48 hours");

        // Delete old API request logs
        $apiDeleted = DB::table('api_request_logs')
            ->where('created_at', '<', $cutoffTime)
            ->delete();

        $this->info("Deleted {$apiDeleted} API request logs older than 48 hours");

        $this->info('Log cleanup completed successfully!');

        return 0;
    }
}
