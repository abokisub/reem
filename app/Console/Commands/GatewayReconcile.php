<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GatewayReconcile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gateway:reconcile {--date= : Data to reconcile (Y-m-d)}';

    protected $description = 'Reconciles internal transactions with provider reports (PalmPay)';

    public function handle(\App\Services\ReconciliationService $reconciler)
    {
        $date = $this->option('date') ?: now()->subDay()->format('Y-m-d');
        $this->info("Starting reconciliation for {$date}...");

        // In a production app, you would fetch this from PalmPay API or SFTP
        // For now, we mock the report fetching logic
        $providerTransactions = $this->mockPalmPayReport($date);

        $report = $reconciler->verifyDailyReport($date, $providerTransactions);

        if ($report->status === 'balanced') {
            $this->info("✅ Reconciliation successful. All transactions matched.");
        } else {
            $this->error("❌ Reconciliation failed. {$report->mismatched_count} discrepancies found.");
            $this->table(['Type', 'Reference', 'Amount'], collect($report->discrepancies)->map(function ($d) {
                return [$d['type'], $d['ref'], $d['amount'] ?? $d['internal_amount'] ?? 'n/a'];
            }));
        }

        return 0;
    }

    protected function mockPalmPayReport(string $date)
    {
        // This is where you would call $palmPayClient->downloadReport($date)
        return \App\Models\Transaction::whereDate('created_at', $date)
            ->whereNotNull('provider_reference')
            ->get()
            ->map(function ($tx) {
                return [
                    'provider_reference' => $tx->provider_reference,
                    'amount' => $tx->amount,
                    'status' => 'SUCCESS'
                ];
            })->toArray();
    }
}
