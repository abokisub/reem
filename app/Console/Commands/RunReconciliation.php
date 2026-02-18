<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunReconciliation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reconcile:nightly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = $this->argument('date') ?: now()->subDay()->format('Y-m-d');
        $this->info("🏁 Starting reconciliation for {$date}...");

        $service = new \App\Services\ReconciliationService();
        $service->runNightlyReconciliation($date);

        $this->info("✅ Reconciliation process finished.");
        return 0;
    }
}
