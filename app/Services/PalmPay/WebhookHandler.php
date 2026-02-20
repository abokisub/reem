<?php

namespace App\Services\PalmPay;

use App\Models\Transaction;
use App\Models\VirtualAccount;
use App\Models\CompanyWallet;
use App\Models\Company;
use App\Models\PalmPayWebhook;
use App\Services\PalmPay\PalmPaySignature;
use App\Services\LedgerService;
use App\Services\ChargeCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PalmPay Webhook Handler
 * 
 * Processes incoming webhooks from PalmPay
 */
class WebhookHandler
{
    private PalmPaySignature $signature;
    private LedgerService $ledgerService;
    private \App\Services\FeeService $feeService;

    public function __construct(
        ?PalmPaySignature $signature = null,
        ?LedgerService $ledgerService = null,
        ?\App\Services\FeeService $feeService = null
    ) {
        $this->signature = $signature ?? new PalmPaySignature();
        $this->ledgerService = $ledgerService ?? new LedgerService();
        $this->feeService = $feeService ?? new \App\Services\FeeService();
    }

    /**
     * Handle incoming webhook from PalmPay
     * 
     * @param array $payload
     * @param string|null $signature
     * @return array
     */
    public function handle(array $payload, ?string $signature = null): array
    {
        // 1. Store webhook (OUTSIDE transaction to persist even if processing fails)
        $webhook = PalmPayWebhook::create([
            'event_type' => $payload['eventType'] ?? 'unknown',
            'palmpay_reference' => $payload['reference'] ?? null,
            'payload' => $payload,
            'signature' => $signature,
            'verified' => false,
            'processed' => false,
            'status' => 'pending',
        ]);

        try {
            // 2. Verify signature
            if ($signature && !$this->signature->verifyWebhookSignature($payload, $signature)) {
                $webhook->update(['status' => 'failed', 'processing_error' => 'Invalid signature']);
                Log::warning('Invalid PalmPay Webhook Signature', ['webhook_id' => $webhook->id]);
                return ['success' => false, 'message' => 'Invalid signature'];
            }

            $webhook->update(['verified' => true]);

            // 3. Process within transaction
            return DB::transaction(function () use ($webhook, $payload) {
                $result = $this->processWebhook($webhook, $payload);

                $webhook->update([
                    'processed' => true,
                    'processed_at' => now(),
                    'status' => 'processed',
                    'processing_error' => null,
                ]);

                return $result;
            });

        } catch (\Exception $e) {
            $retryCount = $webhook->retry_count + 1;
            $nextRetry = now()->addMinutes(pow(2, $retryCount) * 5); // Exponential backoff: 10m, 20m, 40m...

            $webhook->update([
                'status' => $retryCount >= 5 ? 'exhausted' : 'failed',
                'retry_count' => $retryCount,
                'next_retry_at' => $retryCount >= 5 ? null : $nextRetry,
                'processing_error' => $e->getMessage(),
            ]);

            // Log to FailedTransaction for admin visibility if it's a critical error
            if ($retryCount >= 3) {
                \App\Models\FailedTransaction::updateOrCreate(
                    ['transaction_reference' => $webhook->palmpay_reference],
                    [
                        'type' => 'webhook_failure',
                        'amount' => ($payload['orderAmount'] ?? 0) / 100,
                        'payload' => $payload,
                        'failure_reason' => $e->getMessage(),
                        'status' => 'pending'
                    ]
                );
            }

            Log::error('Webhook Processing Failed', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
                'retry_count' => $retryCount
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function processWebhook(PalmPayWebhook $webhook, array $payload): array
    {
        // PalmPay notifications don't have an explicit eventType field.
        // We infer the event type from the presence of specific fields.

        if (isset($payload['virtualAccountNo'])) {
            Log::info('Identified Webhook Event: VIRTUAL_ACCOUNT_CASH_IN');
            return $this->handleVirtualAccountCredit($webhook, $payload);
        }

        if (isset($payload['orderId'])) {
            Log::info('Identified Webhook Event: PAYOUT_RESULT');

            // orderStatus: 2 for success, 3 for fail
            $status = (int) ($payload['orderStatus'] ?? 0);
            if ($status === 2) {
                return $this->handleTransferSuccess($webhook, $payload);
            } elseif ($status === 3) {
                return $this->handleTransferFailed($webhook, $payload);
            }
        }

        return $this->handleUnknownEvent($webhook, $payload);
    }

    /**
     * Handle virtual account credit (incoming payment)
     * 
     * @param PalmPayWebhook $webhook
     * @param array $payload
     * @return array
     */
    private function handleVirtualAccountCredit(PalmPayWebhook $webhook, array $payload): array
    {
        try {
            // Documentation names: virtualAccountNo, orderAmount, orderNo (palmpay ref), accountReference (our ref)
            $accountNumber = $payload['virtualAccountNo'] ?? null;
            $orderAmount = $payload['orderAmount'] ?? 0;
            $palmpayReference = $payload['orderNo'] ?? null;
            $accountReference = $payload['accountReference'] ?? null;

            if (!$accountNumber || !$orderAmount) {
                throw new \Exception('Missing required fields: virtualAccountNo or orderAmount');
            }

            // Convert kobo to Naira
            $amount = $orderAmount / 100;

            // Finding Virtual Account
            $virtualAccount = VirtualAccount::where('palmpay_account_number', $accountNumber)->firstOrFail();

            // --- STRICT IDEMPOTENCY CHECK ---
            // 1. Check if this specific webhook ORDER has already resulted in a successful transaction
            $existingTransaction = Transaction::where('palmpay_reference', $palmpayReference)
                ->where('provider', 'palmpay')
                ->first();

            if ($existingTransaction) {
                Log::info('Webhook Ignored: Transaction already exists for this provider reference', [
                    'reference' => $palmpayReference,
                    'transaction_id' => $existingTransaction->transaction_id
                ]);

                return ['success' => true, 'message' => 'Duplicate transaction (Idempotent)'];
            }

            // 2. Double-check the webhooks table to ensure no race condition between different webhook triggers
            $duplicateWebhook = PalmPayWebhook::where('palmpay_reference', $palmpayReference)
                ->where('status', 'processed')
                ->where('id', '!=', $webhook->id)
                ->exists();

            if ($duplicateWebhook) {
                Log::info('Webhook Ignored: Already processed in another webhook entry', ['reference' => $palmpayReference]);
                return ['success' => true, 'message' => 'Duplicate webhook (Idempotent)'];
            }
            // ---------------------------------

            // Calculate charge based on unified FeeService
            $feeResults = $this->feeService->calculateFee($virtualAccount->company_id, $amount, 'va_deposit');
            $fee = (float) $feeResults['fee'];

            // Net amount is what the company receives (amount - fee)
            $netAmount = $amount - $fee;

            // Get wallet balance before transaction
            $wallet = CompanyWallet::where('company_id', $virtualAccount->company_id)->first();
            $balanceBefore = $wallet ? $wallet->balance : 0;

            // Extract sender information from payload
            $senderName = $payload['payerAccountName'] ?? $payload['senderName'] ?? 'Unknown';
            $senderAccount = $payload['payerAccountNo'] ?? $payload['senderAccount'] ?? null;
            $senderBank = $payload['payerBankName'] ?? $payload['senderBank'] ?? null;
            $narration = $payload['reference'] ?? $payload['narration'] ?? 'Virtual Account Credit';
            
            // Create transaction
            $transaction = Transaction::create([
                'transaction_id' => Transaction::generateTransactionId(),
                'company_id' => $virtualAccount->company_id,
                'virtual_account_id' => $virtualAccount->id,
                'type' => 'credit',
                'category' => 'virtual_account_credit',
                'transaction_type' => 'va_deposit', // CRITICAL: Set transaction_type for RA Transactions filtering
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'total_amount' => $amount,
                'currency' => 'NGN',
                'status' => 'success',
                'settlement_status' => 'settled', // Deposits are immediately settled
                'reference' => Transaction::generateReference(),
                'palmpay_reference' => $palmpayReference,
                'description' => $narration,
                'metadata' => [
                    'sender_name' => $senderName,
                    'sender_account' => $senderAccount,
                    'sender_bank' => $senderBank,
                    'sender_account_name' => $senderName,
                    'sender_bank_name' => $senderBank,
                    'narration' => $narration,
                    'fee_model' => $feeResults['model'],
                    'fee_type' => $feeResults['type'] ?? 'PERCENT',
                    'fee_value' => $feeResults['value'] ?? 0,
                    'fee_cap' => $feeResults['cap'] ?? null,
                    'palmpay_order_no' => $palmpayReference,
                    'palmpay_session_id' => $payload['sessionId'] ?? null,
                    'virtual_account_name' => $payload['virtualAccountName'] ?? null,
                ],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $netAmount,
                'provider' => 'palmpay',
                'reconciliation_status' => 'not_started',
                'processed_at' => now(),
            ]);

            // Update webhook with transaction
            $webhook->update(['transaction_id' => $transaction->id]);

            // ===================================================================
            // CRITICAL: Detect if this is COMPANY SELF-FUNDING or CLIENT PAYMENT
            // ===================================================================
            // company_user_id = NULL → Master account (company funding themselves)
            // company_user_id = value → Client account (end user payment)
            // 
            // Company self-funding should:
            // - Credit wallet INSTANTLY (no settlement delay)
            // - NOT go to settlement queue
            // - NOT count in "Total Transactions" or "Total Revenue" metrics
            // ===================================================================
            
            $isCompanySelfFunding = ($virtualAccount->company_user_id === null);
            
            if ($isCompanySelfFunding) {
                // INSTANT CREDIT for company self-funding
                Log::info('Company Self-Funding Detected - Instant Credit', [
                    'transaction_id' => $transaction->transaction_id,
                    'company_id' => $virtualAccount->company_id,
                    'amount' => $netAmount,
                ]);
                
                $wallet = CompanyWallet::where('company_id', $virtualAccount->company_id)
                    ->where('currency', 'NGN')
                    ->lockForUpdate()
                    ->firstOrFail();

                $balanceBefore = $wallet->balance;
                $wallet->credit($netAmount);
                $wallet->save();

                // Update transaction balances
                $transaction->update([
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'settlement_status' => 'instant',
                        'settlement_type' => 'company_self_funding',
                        'bypass_reason' => 'Master account funding - instant credit',
                    ]),
                ]);

                // Sync System Wallets (Revenue & Clearing Isolation)
                $revWallet = \App\Models\SystemWallet::where('slug', 'platform_revenue')->lockForUpdate()->first();
                if ($revWallet)
                    $revWallet->credit($fee);

                $clrWallet = \App\Models\SystemWallet::where('slug', 'bank_clearing')->lockForUpdate()->first();
                if ($clrWallet)
                    $clrWallet->credit($amount);
                    
                Log::info('Company Wallet Credited Instantly', [
                    'transaction_id' => $transaction->transaction_id,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                    'net_amount' => $netAmount,
                ]);
            } else {
                // CLIENT PAYMENT - Use settlement queue
                Log::info('Client Payment Detected - Using Settlement Queue', [
                    'transaction_id' => $transaction->transaction_id,
                    'company_id' => $virtualAccount->company_id,
                    'company_user_id' => $virtualAccount->company_user_id,
                ]);
                
                // Check if settlement is enabled
                $settings = DB::table('settings')->first();
                $company = Company::find($virtualAccount->company_id);

                $settlementEnabled = $settings && property_exists($settings, 'auto_settlement_enabled') && $settings->auto_settlement_enabled;
                $useCustomSettlement = $company && property_exists($company, 'custom_settlement_enabled') && $company->custom_settlement_enabled;

                if ($settlementEnabled) {
                // Get settlement configuration
                $delayHours = $useCustomSettlement ?
                    (int) ($company->custom_settlement_delay_hours ?? 24) :
                    (int) ($settings->settlement_delay_hours ?? 24);

                $skipWeekends = (bool) ($settings->settlement_skip_weekends ?? true);
                $skipHolidays = (bool) ($settings->settlement_skip_holidays ?? true);
                $settlementTime = $settings->settlement_time ?? '02:00:00';

                // Calculate settlement date
                $scheduledDate = \App\Console\Commands\ProcessSettlements::calculateSettlementDate(
                    now(),
                    $delayHours,
                    $skipWeekends,
                    $skipHolidays,
                    $settlementTime
                );

                // Queue for settlement (use net amount - what company receives after fees)
                DB::table('settlement_queue')->insert([
                    'company_id' => $virtualAccount->company_id,
                    'transaction_id' => $transaction->id,
                    'amount' => $netAmount,
                    'status' => 'pending',
                    'transaction_date' => now(),
                    'scheduled_settlement_date' => $scheduledDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update transaction metadata
                $transaction->update([
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'settlement_status' => 'pending',
                        'scheduled_settlement_date' => $scheduledDate->toDateTimeString(),
                        'settlement_delay_hours' => $delayHours,
                    ]),
                ]);

                Log::info('Transaction Queued for Settlement', [
                    'transaction_id' => $transaction->transaction_id,
                    'gross_amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                    'scheduled_date' => $scheduledDate->toDateTimeString(),
                ]);
            } else {
                // Immediate settlement (auto settlement disabled) - credit net amount to wallet
                $wallet = CompanyWallet::where('company_id', $virtualAccount->company_id)
                    ->where('currency', 'NGN')
                    ->lockForUpdate()
                    ->firstOrFail();

                $balanceBefore = $wallet->balance;
                $wallet->credit($netAmount);
                $wallet->save();

                // Update transaction balances
                $transaction->update([
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                ]);

                // Sync System Wallets (Revenue & Clearing Isolation)
                $revWallet = \App\Models\SystemWallet::where('slug', 'platform_revenue')->lockForUpdate()->first();
                if ($revWallet)
                    $revWallet->credit($fee);

                $clrWallet = \App\Models\SystemWallet::where('slug', 'bank_clearing')->lockForUpdate()->first();
                if ($clrWallet)
                    $clrWallet->credit($amount);
            }
            } // End of CLIENT PAYMENT block

            Log::info('Virtual Account Credited', [
                'transaction_id' => $transaction->transaction_id,
                'gross_amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'account_number' => $accountNumber,
                'charge_config' => $feeResults
            ]);

            // Record 3-Way Ledger Transaction (Double Entry)
            try {
                $bankClearing = $this->ledgerService->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');
                $companyWalletAccount = $this->ledgerService->getOrCreateAccount('Company Wallet ' . $virtualAccount->company_id, 'company_wallet', $virtualAccount->company_id);
                $revenueAccount = $this->ledgerService->getOrCreateAccount('Platform Revenue', 'revenue');

                $this->ledgerService->recordTransaction($transaction->reference, [
                    ['account_id' => $bankClearing->id, 'type' => 'debit', 'amount' => $amount], // Debit Clearing (Gross)
                    ['account_id' => $companyWalletAccount->id, 'type' => 'credit', 'amount' => $netAmount], // Credit Wallet (Net)
                    ['account_id' => $revenueAccount->id, 'type' => 'credit', 'amount' => $fee], // Credit Revenue (Fee)
                ], "Deposit from " . $senderName . " via " . ($senderBank ?? 'Bank'));

            } catch (\Exception $e) {
                Log::error('Ledger Recording Failed: ' . $e->getMessage());
            }

            // Dispatch webhook to company
            $company = $virtualAccount->company;
            if ($company && $company->webhook_url) {
                $webhookLog = \App\Models\CompanyWebhookLog::create([
                    'company_id' => $company->id,
                    'transaction_id' => $transaction->id,
                    'event_type' => 'payment.success',
                    'webhook_url' => $company->webhook_url,
                    'payload' => [
                        'event' => 'payment.success',
                        'data' => [
                            'transaction_id' => $transaction->transaction_id,
                            'amount' => $transaction->amount,
                            'fee' => $transaction->fee,
                            'net_amount' => $transaction->netAmount,
                            'reference' => $transaction->reference,
                            'status' => 'success',
                            'customer' => [
                                'account_number' => $accountNumber,
                                'sender_name' => $senderName,
                                'sender_account' => $senderAccount,
                                'sender_bank' => $senderBank,
                            ],
                            'narration' => $narration,
                            'created_at' => $transaction->created_at->toIso8601String(),
                        ]
                    ],
                    'status' => 'pending',
                ]);

                \App\Jobs\SendOutgoingWebhook::dispatch($webhookLog);
            }

            return [
                'success' => true,
                'message' => 'Payment processed',
                'transaction_id' => $transaction->transaction_id
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Process Virtual Account Credit', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            throw $e;
        }
    }

    /**
     * Handle transfer success
     * 
     * @param PalmPayWebhook $webhook
     * @param array $payload
     * @return array
     */
    private function handleTransferSuccess(PalmPayWebhook $webhook, array $payload): array
    {
        try {
            // Documentation names: orderId (our reference), orderNo (PalmPay reference)
            $reference = $payload['orderId'] ?? null;
            $palmpayReference = $payload['orderNo'] ?? null;

            if (!$reference) {
                throw new \Exception('Missing orderId');
            }

            // Find transaction
            $transaction = Transaction::where('reference', $reference)
                ->firstOrFail();

            // Update transaction status
            $transaction->update([
                'status' => 'success',
                'palmpay_reference' => $palmpayReference,
                'processed_at' => now(),
            ]);

            // Remove from pending balance
            $wallet = $transaction->company->wallet;
            $wallet->removePending($transaction->total_amount);
            $wallet->save();

            // Update webhook
            $webhook->update(['transaction_id' => $transaction->id]);

            Log::info('Transfer Completed Successfully', [
                'transaction_id' => $transaction->transaction_id,
                'reference' => $reference
            ]);

            // Dispatch webhook to company
            $company = $transaction->company;
            if ($company && $company->webhook_url) {
                $webhookLog = \App\Models\CompanyWebhookLog::create([
                    'company_id' => $company->id,
                    'transaction_id' => $transaction->id,
                    'event_type' => 'transfer.success',
                    'webhook_url' => $company->webhook_url,
                    'payload' => [
                        'event' => 'transfer.success',
                        'data' => [
                            'amount' => $transaction->amount,
                            'reference' => $transaction->reference,
                            'status' => 'success',
                            'palmpay_reference' => $palmpayReference
                        ]
                    ],
                    'status' => 'pending',
                ]);

                \App\Jobs\SendOutgoingWebhook::dispatch($webhookLog);
            }

            return [
                'success' => true,
                'message' => 'Transfer completed',
                'transaction_id' => $transaction->transaction_id
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Process Transfer Success', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            throw $e;
        }
    }

    /**
     * Handle transfer failed
     * 
     * @param PalmPayWebhook $webhook
     * @param array $payload
     * @return array
     */
    private function handleTransferFailed(PalmPayWebhook $webhook, array $payload): array
    {
        try {
            // Documentation names: orderId (our reference), errorMsg
            $reference = $payload['orderId'] ?? null;
            $errorMessage = $payload['errorMsg'] ?? 'Transfer failed';

            if (!$reference) {
                throw new \Exception('Missing orderId');
            }

            // Find transaction
            $transaction = Transaction::where('reference', $reference)
                ->firstOrFail();

            // Update transaction status
            $transaction->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'processed_at' => now(),
            ]);

            // Refund to wallet
            $wallet = $transaction->company->wallet;
            $wallet->credit($transaction->total_amount);
            $wallet->removePending($transaction->total_amount);
            $wallet->save();

            // Update webhook
            $webhook->update(['transaction_id' => $transaction->id]);

            Log::info('Transfer Failed and Refunded', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $errorMessage
            ]);

            // Dispatch webhook to company
            $company = $transaction->company;
            if ($company && $company->webhook_url) {
                $webhookLog = \App\Models\CompanyWebhookLog::create([
                    'company_id' => $company->id,
                    'transaction_id' => $transaction->id,
                    'event_type' => 'transfer.failed',
                    'webhook_url' => $company->webhook_url,
                    'payload' => [
                        'event' => 'transfer.failed',
                        'data' => [
                            'amount' => $transaction->amount,
                            'reference' => $transaction->reference,
                            'status' => 'failed',
                            'reason' => $errorMessage
                        ]
                    ],
                    'status' => 'pending',
                ]);

                \App\Jobs\SendOutgoingWebhook::dispatch($webhookLog);
            }

            return [
                'success' => true,
                'message' => 'Transfer failed, refunded',
                'transaction_id' => $transaction->transaction_id
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Process Transfer Failure', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            throw $e;
        }
    }

    /**
     * Handle unknown event type
     * 
     * @param PalmPayWebhook $webhook
     * @param array $payload
     * @return array
     */
    private function handleUnknownEvent(PalmPayWebhook $webhook, array $payload): array
    {
        Log::warning('Unknown PalmPay Webhook Event', [
            'event_type' => $payload['eventType'] ?? 'unknown',
            'webhook_id' => $webhook->id
        ]);

        return [
            'success' => true,
            'message' => 'Event logged but not processed'
        ];
    }
}