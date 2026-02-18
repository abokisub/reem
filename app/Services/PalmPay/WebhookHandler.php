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
    private $ledgerService;

    public function __construct(?PalmPaySignature $signature = null, ?LedgerService $ledgerService = null)
    {
        $this->signature = $signature ?? new PalmPaySignature();
        $this->ledgerService = $ledgerService ?? new LedgerService();
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
        try {
            DB::beginTransaction();

            // Store webhook
            $webhook = PalmPayWebhook::create([
                'event_type' => $payload['eventType'] ?? 'unknown',
                'palmpay_reference' => $payload['reference'] ?? null,
                'payload' => $payload,
                'signature' => $signature,
                'verified' => false,
                'processed' => false,
            ]);

            // Verify signature
            if ($signature && !$this->signature->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Invalid PalmPay Webhook Signature', [
                    'webhook_id' => $webhook->id
                ]);

                DB::commit();

                return [
                    'success' => false,
                    'message' => 'Invalid signature'
                ];
            }

            $webhook->update(['verified' => true]);

            // Process webhook based on event type
            $result = $this->processWebhook($webhook, $payload);

            // Mark as processed
            $webhook->update([
                'processed' => true,
                'processed_at' => now(),
            ]);

            DB::commit();

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Webhook Processing Failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            if (isset($webhook)) {
                $webhook->update([
                    'processing_error' => $e->getMessage()
                ]);
            }

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

            // Find virtual account
            $virtualAccount = VirtualAccount::where('palmpay_account_number', $accountNumber)
                ->firstOrFail();

            // Check for duplicate transaction
            $existingTransaction = Transaction::where('palmpay_reference', $palmpayReference)->first();

            if ($existingTransaction) {
                Log::info('Duplicate webhook ignored', [
                    'reference' => $palmpayReference,
                    'transaction_id' => $existingTransaction->transaction_id
                ]);

                return [
                    'success' => true,
                    'message' => 'Duplicate transaction'
                ];
            }

            // Calculate charge based on service_charges table
            $chargeDetails = ChargeCalculator::getServiceCharge('payment', 'palmpay_va', $amount);
            $fee = $chargeDetails['charge'];

            // Net amount is what the company receives (amount - fee)
            $netAmount = $amount - $fee;

            // Get wallet balance before transaction
            $wallet = CompanyWallet::where('company_id', $virtualAccount->company_id)->first();
            $balanceBefore = $wallet ? $wallet->balance : 0;

            // Create transaction
            $transaction = Transaction::create([
                'transaction_id' => Transaction::generateTransactionId(),
                'company_id' => $virtualAccount->company_id,
                'virtual_account_id' => $virtualAccount->id,
                'type' => 'credit',
                'category' => 'virtual_account_credit',
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'total_amount' => $amount,
                'currency' => 'NGN',
                'status' => 'success',
                'reference' => Transaction::generateReference(),
                'palmpay_reference' => $palmpayReference,
                'description' => $payload['narration'] ?? 'Virtual Account Credit',
                'metadata' => [
                    'sender_name' => $payload['senderName'] ?? null,
                    'sender_account' => $payload['senderAccount'] ?? null,
                    'sender_bank' => $payload['senderBank'] ?? null,
                    'charge_type' => $chargeDetails['type'],
                    'charge_value' => $chargeDetails['value'],
                    'charge_cap' => $chargeDetails['cap'],
                ],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $netAmount,
                'processed_at' => now(),
            ]);

            // Update webhook with transaction
            $webhook->update(['transaction_id' => $transaction->id]);

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
                // Immediate settlement (old behavior) - credit net amount to wallet
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
            }

            Log::info('Virtual Account Credited', [
                'transaction_id' => $transaction->transaction_id,
                'gross_amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'account_number' => $accountNumber,
                'charge_config' => $chargeDetails
            ]);

            // Record Ledger Entry (Double Entry)
            try {
                $bankClearing = $this->ledgerService->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');
                $companyWallet = $this->ledgerService->getOrCreateAccount('Company Wallet ' . $virtualAccount->company_id, 'company_wallet', $virtualAccount->company_id);

                $this->ledgerService->recordEntry(
                    $transaction->reference,
                    $bankClearing->id, // Debit Bank Clearing (Asset up)
                    $companyWallet->id, // Credit Company Wallet (Liability up)
                    $amount,
                    "Deposit: " . ($payload['senderName'] ?? 'Unknown')
                );
            } catch (\Exception $e) {
                Log::error('Ledger Recording Failed: ' . $e->getMessage());
                // We do not fail the webhook, but we log the error. 
                // In production, we might want to alert on this.
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
                            'amount' => $transaction->amount,
                            'reference' => $transaction->reference,
                            'status' => 'success',
                            'customer' => [
                                'account_number' => $accountNumber,
                                'sender_name' => $payload['senderName'] ?? null,
                            ]
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