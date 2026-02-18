<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\GatewayWebhookLog;
use App\Models\LedgerAccount;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use App\Services\FeeService;
use App\Services\LedgerService;
use App\Services\PalmPay\PalmPaySignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $signature;
    protected $ledger;
    protected $feeService;

    public function __construct(PalmPaySignature $signature, LedgerService $ledger, FeeService $feeService)
    {
        $this->signature = $signature;
        $this->ledger = $ledger;
        $this->feeService = $feeService;
    }

    /**
     * Handle PalmPay Webhook
     */
    public function handlePalmPay(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('Signature');

        // 1. Log Webhook Immediately (Audit Trail)
        $log = GatewayWebhookLog::create([
            'provider' => 'palmpay',
            'event_type' => $payload['eventType'] ?? 'unknown',
            'provider_reference' => $payload['orderNo'] ?? ($payload['orderId'] ?? null),
            'payload' => $payload,
            'signature' => $signature,
            'status' => 'logged'
        ]);

        // 2. Verify Signature
        if (!$this->signature->verifyWebhookSignature($payload, $signature)) {
            $log->update(['status' => 'failed', 'error_message' => 'Invalid Signature']);
            return response()->json(['code' => 'FAILED', 'message' => 'Invalid Signature'], 400);
        }

        $log->update(['verified' => true]);

        // 3. Process Transaction (Only if successful)
        if (($payload['status'] ?? null) === 'SUCCESS' || ($payload['respCode'] ?? null) === '00000') {
            return $this->processPayment($payload, $log);
        }

        return response()->json(['code' => 'OK', 'message' => 'Received']);
    }

    protected function processPayment(array $payload, GatewayWebhookLog $log)
    {
        $providerRef = $payload['orderNo'] ?? $payload['orderId'];
        $amount = (float) ($payload['amount'] ?? 0);
        $accountNumber = $payload['accountNumber'] ?? null;

        // Idempotency: Check if already processed
        if (Transaction::where('provider_reference', $providerRef)->exists()) {
            return response()->json(['code' => 'OK', 'message' => 'Already Processed']);
        }

        // Find Virtual Account
        $va = VirtualAccount::where('account_number', $accountNumber)->first();
        if (!$va) {
            $log->update(['status' => 'failed', 'error_message' => "Virtual Account {$accountNumber} not found"]);
            return response()->json(['code' => 'FAILED', 'message' => 'Account not found'], 404);
        }

        $company = Company::find($va->company_id);
        $customer = CompanyUser::find($va->company_user_id);

        // 4. Calculate Fee
        $feeData = $this->feeService->calculateFee($company->id, $amount);

        // 5. Create Transaction Record (Master Ledger Event)
        $tx = Transaction::create([
            'company_id' => $company->id,
            'company_user_id' => $customer ? $customer->id : null,
            'virtual_account_id' => $va->id,
            'reference' => 'PWV_TRX_' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10)),
            'provider_reference' => $providerRef,
            'amount' => $amount,
            'fee' => $feeData['fee'],
            'net_amount' => $feeData['net'],
            'currency' => 'NGN',
            'type' => 'credit',
            'channel' => 'virtual_account',
            'status' => 'successful',
            'description' => "Virtual Account Credit: {$accountNumber} - {$va->account_name}",
        ]);

        // 6. RECORD LEDGER ENTRIES (The Financial Brain)
        try {
            // Get necessary accounts
            $bankClearing = $this->ledger->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');
            $companyWallet = $this->ledger->getOrCreateAccount($company->name . ' Wallet', 'company_wallet', $company->id);
            $revenueAccount = $this->ledger->getOrCreateAccount('Gateway Revenue', 'revenue');

            // Entry 1: Total inflow to Company Wallet
            // Note: In real double-entry, we might credit Net to wallet and Credit Fee to Revenue.
            // Debit: PalmPay Clearing (₦10,000)
            // Credit: Company Wallet (₦9,850)
            // Credit: Revenue (₦150)

            // For simplicity in our LedgerService (which currently handles atomic pairs):
            $this->ledger->recordEntry($tx->reference, $bankClearing->id, $companyWallet->id, $amount, "Gross Credit");
            $this->ledger->recordEntry($tx->reference, $companyWallet->id, $revenueAccount->id, $feeData['fee'], "Transaction Fee");

            // Update log
            $log->update(['status' => 'processed', 'processed_at' => now()]);

            // 7. Notify Company (Merchant Webhook)
            $this->notifyMerchant($company, $tx, $customer);

            return response()->json(['code' => 'OK', 'message' => 'Processed']);

        } catch (\Exception $e) {
            Log::error('Ledger Recording Failed', ['error' => $e->getMessage(), 'txn' => $tx->reference]);
            $log->update(['status' => 'failed', 'error_message' => 'Ledger Error: ' . $e->getMessage()]);
            return response()->json(['code' => 'FAILED', 'message' => 'Internal Error'], 500);
        }
    }

    protected function notifyMerchant(Company $company, Transaction $tx, ?CompanyUser $customer)
    {
        if (!$company->webhook_url)
            return;

        $payload = [
            'event' => 'transaction.success',
            'data' => [
                'reference' => $tx->reference,
                'amount' => $tx->amount,
                'fee' => $tx->fee,
                'net_amount' => $tx->net_amount,
                'customer_id' => $customer ? $customer->uuid : null,
                'external_customer_id' => $customer ? $customer->external_customer_id : null,
                'channel' => $tx->channel,
                'created_at' => $tx->created_at->toIso8601String(),
            ]
        ];

        // Fire and forget or use a job for retries
        try {
            Http::timeout(5)->post($company->webhook_url, $payload);
        } catch (\Exception $e) {
            Log::warning('Merchant Webhook Failed', ['url' => $company->webhook_url, 'error' => $e->getMessage()]);
        }
    }
}
