<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GatewaySettle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gateway:settle {--date= : Settlement date (Y-m-d)}';

    protected $description = 'Automates daily merchant settlements (Wallet to Settlement Account)';

    public function handle(\App\Services\SettlementService $settler)
    {
        $date = $this->option('date') ?: now()->toDateString();
        $this->info("Processing settlements for {$date}...");

        $results = $settler->processSettlements($date);

        $tableData = [];
        foreach ($results as $company => $res) {
            $tableData[] = [
                $company,
                $res['status'],
                isset($res['amount']) ? number_format($res['amount'], 2) : '0.00',
                $res['reference'] ?? ($res['message'] ?? 'n/a')
            ];
        }

        $this->table(['Company', 'Status', 'Amount', 'Reference/Error'], $tableData);

        return 0;
    }
}
