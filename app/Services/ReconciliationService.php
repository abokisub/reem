<?php

namespace App\Services;

use App\Models\ReconciliationReport;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    protected $vaService;

    public function __construct()
    {
        $this->vaService = new \App\Services\PalmPay\VirtualAccountService();
    }

    /**
     * Run nightly reconciliation for a specific date
     */
    public function runNightlyReconciliation(string $date)
    {
        $reportDate = date('Y-m-d', strtotime($date));
        Log::info("🏁 Starting Nightly Reconciliation for {$reportDate}");

        // 1. Fetch internal Ledger entries (the immutable source of truth)
        $internalLedgerEntries = \App\Models\LedgerEntry::whereDate('created_at', $reportDate)
            ->with(['debitAccount', 'creditAccount'])
            ->get();

        // 2. Map ledger entries to their respective references for easier lookup
        // 3. Reconcile against provider data
        $this->reconcileLedgerToProvider($reportDate, $internalLedgerEntries);

        // 3. Detect "Ghost" transactions (Success on Provider but missing internally)
        // This usually requires a provider report file (CSV/SFTP). 
        // For this implementation, we simulate fetching the "Success" list from provider.
        $this->detectGhostTransactions($reportDate);

        Log::info("✅ Finished Reconciliation for {$reportDate}");
    }

    private function reconcileLedgerToProvider($date, $ledgerEntries)
    {
        foreach ($ledgerEntries->chunk(20) as $chunk) {
            // Fetch references from ledger mapping or related transactions
            // For PalmPay, the reference is usually stored in the transaction tied to the ledger
            $references = $chunk->pluck('reference')->toArray();
            if (empty($references))
                continue;

            // In actual PalmPay context, we map internal 'reference' to 'orderId'
            $response = $this->vaService->bulkQueryPayInOrders($references);

            if ($response['success'] && isset($response['data'])) {
                foreach ($response['data'] as $pTx) {
                    $ref = $pTx['orderNo'] ?? $pTx['orderId'];
                    $internal = $chunk->where('reference', $ref)->first();

                    if (!$internal)
                        continue;

                    // Reconcile amount against ledger debit/credit amount
                    $providerAmount = $pTx['orderAmount'] / 100;
                    if (abs($internal->amount - $providerAmount) > 0.01) {
                        $this->flagMismatch($date, $ref, $internal->reference, $providerAmount, $internal->amount, 'amount_mismatch');
                    }
                }
            }
        }
    }

    private function detectGhostTransactions($date)
    {
        // In a real scenario, this would parse an SFTP CSV report.
        // We'll add a placeholder for where that integration happens.
        Log::info("🔍 Checking for provider-only 'Ghost' transactions...");

        // Example: If we had a provider report array $reportRows
        // foreach ($reportRows as $row) {
        //     $exists = Transaction::where('palmpay_reference', $row['orderNo'])->exists();
        //     if (!$exists) {
        //         $this->flagMismatch($date, $row['orderNo'], null, $row['amount'], 0, 'missing_internal');
        //     }
        // }
    }

    private function flagMismatch($date, $providerRef, $internalRef, $pAmount, $iAmount, $type)
    {
        Log::warning("⚠️ Reconciliation Mismatch detected!", [
            'type' => $type,
            'provider_ref' => $providerRef,
            'internal_ref' => $internalRef
        ]);

        \App\Models\ReconciliationMismatch::updateOrCreate(
            ['provider_reference' => $providerRef, 'report_date' => $date],
            [
                'internal_reference' => $internalRef,
                'amount_provider' => $pAmount,
                'amount_internal' => $iAmount,
                'type' => $type,
                'status' => 'unresolved',
                'details' => ['detected_at' => now()->toDateTimeString()]
            ]
        );

        \App\Services\AlertService::trigger(
            'RECON_MISMATCH',
            "Financial mismatch detected in reconciliation for date: {$date}.",
            ['provider_ref' => $providerRef, 'internal_ref' => $internalRef, 'provider_amount' => $pAmount, 'internal_amount' => $iAmount],
            'critical'
        );
    }
}
