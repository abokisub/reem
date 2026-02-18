<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\PalmPay\WebhookHandler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPalmPayTransactions extends Command
{
    protected $signature = 'palmpay:sync-transactions {--company_id=}';
    protected $description = 'Manually sync PalmPay transactions for companies (useful when webhooks are not configured)';

    public function handle()
    {
        $companyId = $this->option('company_id');
        
        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::whereNotNull('palmpay_account_number')
                ->where('status', 'active')
                ->get();
        }

        if ($companies->isEmpty()) {
            $this->error('No companies found with PalmPay accounts');
            return 1;
        }

        $this->info("Syncing transactions for " . $companies->count() . " companies...");
        $this->newLine();

        $webhookHandler = new WebhookHandler();
        $totalProcessed = 0;

        foreach ($companies as $company) {
            $this->info("Processing: {$company->name} (Account: {$company->palmpay_account_number})");
            
            try {
                // Query PalmPay for transactions
                $transactions = $this->queryPalmPayTransactions($company->palmpay_account_number);
                
                if (empty($transactions)) {
                    $this->warn("  No transactions found");
                    continue;
                }

                $this->info("  Found " . count($transactions) . " transactions");

                foreach ($transactions as $txn) {
                    // Check if already processed
                    $exists = \App\Models\Transaction::where('palmpay_reference', $txn['orderNo'])->exists();
                    
                    if ($exists) {
                        $this->line("  - Skipping {$txn['orderNo']} (already processed)");
                        continue;
                    }

                    // Process as webhook
                    $result = $webhookHandler->handle($txn, null);
                    
                    if ($result['success']) {
                        $this->info("  ✅ Processed {$txn['orderNo']} - ₦" . number_format($txn['orderAmount'] / 100, 2));
                        $totalProcessed++;
                    } else {
                        $this->error("  ❌ Failed {$txn['orderNo']}: {$result['message']}");
                    }
                }

            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
                Log::error("PalmPay Sync Error for {$company->name}: " . $e->getMessage());
            }

            $this->newLine();
        }

        $this->info("Sync complete! Processed {$totalProcessed} new transactions.");
        return 0;
    }

    private function queryPalmPayTransactions($accountNumber)
    {
        $baseUrl = config('services.palmpay.base_url');
        $merchantId = config('services.palmpay.merchant_id');
        $appId = config('services.palmpay.app_id');

        // Build request
        $timestamp = now()->format('Y-m-d\TH:i:s\Z');
        $requestData = [
            'virtualAccountNo' => $accountNumber,
            'pageNo' => 1,
            'pageSize' => 50,
        ];

        $signature = $this->generateSignature($requestData, $timestamp);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'merchantId' => $merchantId,
            'appId' => $appId,
            'timestamp' => $timestamp,
            'sign' => $signature,
        ])->post($baseUrl . '/api/merchant/v1/virtualAccount/queryTransactionList', $requestData);

        if (!$response->successful()) {
            throw new \Exception("PalmPay API error: " . $response->body());
        }

        $data = $response->json();

        if ($data['code'] !== '10000') {
            throw new \Exception("PalmPay error: " . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['data']['list'] ?? [];
    }

    private function generateSignature($data, $timestamp)
    {
        $signatureService = new \App\Services\PalmPay\PalmPaySignature();
        return $signatureService->generateSignature($data);
    }
}
