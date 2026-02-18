<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Models\EndUserTransaction;
use App\Models\VirtualAccount;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PalmPayWebhookController extends Controller
{
    /**
     * Handle incoming PalmPay webhooks.
     * This endpoint receives payment notifications from PalmPay.
     */
    public function handleWebhook(Request $request)
    {
        // Log the incoming webhook
        $webhookLog = WebhookLog::create([
            'provider' => 'palmpay',
            'event_type' => $request->input('event_type', 'payment_notification'),
            'payload' => $request->all(),
            'signature' => $request->header('X-PalmPay-Signature'),
            'verified' => false,
            'processed' => false,
        ]);

        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                $webhookLog->markAsProcessed('Invalid signature');
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
            }

            $webhookLog->markAsVerified();

            // Process the webhook
            $this->processWebhook($request->all(), $webhookLog);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('PalmPay webhook processing error: ' . $e->getMessage(), [
                'webhook_id' => $webhookLog->id,
                'payload' => $request->all(),
            ]);

            $webhookLog->markAsProcessed($e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Processing failed'], 500);
        }
    }

    /**
     * Process the webhook payload.
     */
    private function processWebhook(array $payload, WebhookLog $webhookLog)
    {
        $eventType = $payload['event_type'] ?? 'payment_notification';

        switch ($eventType) {
            case 'payment_notification':
            case 'payment.success':
                $this->handlePaymentSuccess($payload, $webhookLog);
                break;

            case 'payment.failed':
                $this->handlePaymentFailed($payload, $webhookLog);
                break;

            default:
                Log::warning('Unknown webhook event type: ' . $eventType);
                $webhookLog->markAsProcessed('Unknown event type');
        }
    }

    /**
     * Handle successful payment notification.
     */
    private function handlePaymentSuccess(array $payload, WebhookLog $webhookLog)
    {
        DB::beginTransaction();
        try {
            // Extract payment data
            $transactionReference = $payload['transaction_reference'] ?? $payload['reference'] ?? null;
            $accountNumber = $payload['account_number'] ?? null;
            $amount = $payload['amount'] ?? 0;
            $customerName = $payload['customer_name'] ?? null;
            $customerReference = $payload['customer_reference'] ?? null;

            if (!$transactionReference || !$accountNumber) {
                throw new \Exception('Missing required fields: transaction_reference or account_number');
            }

            // Find the virtual account
            $virtualAccount = VirtualAccount::where('palmpay_account_number', $accountNumber)
                ->orWhere('account_number', $accountNumber)
                ->first();

            if (!$virtualAccount) {
                throw new \Exception('Virtual account not found: ' . $accountNumber);
            }

            // Check if transaction already exists
            $existingTransaction = EndUserTransaction::where('transaction_reference', $transactionReference)->first();
            if ($existingTransaction) {
                Log::info('Duplicate webhook received for transaction: ' . $transactionReference);
                $webhookLog->markAsProcessed('Duplicate transaction');
                DB::commit();
                return;
            }

            // Calculate fee (e.g., 1.5% + ₦100)
            $fee = ($amount * 0.015) + 100;
            $netAmount = $amount - $fee;

            // Create transaction record
            $transaction = EndUserTransaction::create([
                'company_id' => $virtualAccount->company_id,
                'virtual_account_id' => $virtualAccount->id,
                'transaction_reference' => $transactionReference,
                'customer_reference' => $customerReference ?? 'CUST_' . time(),
                'customer_name' => $customerName,
                'customer_email' => $payload['customer_email'] ?? null,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'status' => 'successful',
                'payment_method' => 'bank_transfer',
                'description' => $payload['description'] ?? 'Payment via virtual account',
                'metadata' => $payload,
                'paid_at' => now(),
            ]);

            // Update company wallet balance
            $company = Company::find($virtualAccount->company_id);
            if ($company && $company->wallet) {
                $company->wallet->increment('balance', $netAmount);
            }

            // TODO: Forward webhook to company's webhook URL
            $this->forwardWebhookToCompany($company, $transaction, $payload);

            $webhookLog->markAsProcessed();
            DB::commit();

            Log::info('Payment processed successfully', [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'company_id' => $virtualAccount->company_id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle failed payment notification.
     */
    private function handlePaymentFailed(array $payload, WebhookLog $webhookLog)
    {
        $transactionReference = $payload['transaction_reference'] ?? $payload['reference'] ?? null;

        if ($transactionReference) {
            $transaction = EndUserTransaction::where('transaction_reference', $transactionReference)->first();
            if ($transaction) {
                $transaction->markAsFailed();
            }
        }

        $webhookLog->markAsProcessed();
    }

    /**
     * Forward webhook to company's configured webhook URL.
     */
    private function forwardWebhookToCompany(Company $company, EndUserTransaction $transaction, array $originalPayload)
    {
        if (!$company->webhook_url) {
            return;
        }

        try {
            // Prepare payload for company
            $payload = [
                'event' => 'payment.success',
                'transaction_reference' => $transaction->transaction_reference,
                'customer_reference' => $transaction->customer_reference,
                'customer_name' => $transaction->customer_name,
                'customer_email' => $transaction->customer_email,
                'amount' => $transaction->amount,
                'fee' => $transaction->fee,
                'net_amount' => $transaction->net_amount,
                'status' => $transaction->status,
                'paid_at' => $transaction->paid_at->toIso8601String(),
                'metadata' => $transaction->metadata,
            ];

            // Generate signature for company
            $signature = hash_hmac('sha256', json_encode($payload), $company->secret_key);

            // Send webhook (async recommended in production)
            $ch = curl_init($company->webhook_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-PointPay-Signature: ' . $signature,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseBody = curl_error($ch) ?: $response; // Capture error or response
            curl_close($ch);

            // Log the webhook delivery attempt
            \App\Models\CompanyWebhookLog::create([
                'company_id' => $company->id,
                'event_type' => $payload['event'],
                'webhook_url' => $company->webhook_url,
                'payload' => $payload,
                'http_status' => $httpCode,
                'response_body' => substr($responseBody, 0, 1000), // Limit size
                'status' => ($httpCode >= 200 && $httpCode < 300) ? 'sent' : 'failed',
                'attempt_number' => 1,
                'sent_at' => now(),
            ]);

            Log::info('Webhook forwarded to company', [
                'company_id' => $company->id,
                'webhook_url' => $company->webhook_url,
                'http_code' => $httpCode,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to forward webhook to company: ' . $e->getMessage(), [
                'company_id' => $company->id,
                'transaction_id' => $transaction->id,
            ]);

            // Log failed attempt
            \App\Models\CompanyWebhookLog::create([
                'company_id' => $company->id,
                'event_type' => 'payment.success', // Default or extract
                'webhook_url' => $company->webhook_url,
                'payload' => ['error' => $e->getMessage()], // We might not have full payload if it failed early
                'status' => 'failed',
                'attempt_number' => 1,
                'response_body' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify PalmPay webhook signature.
     */
    private function verifySignature(Request $request)
    {
        $signature = $request->header('X-PalmPay-Signature');

        if (!$signature) {
            return false;
        }

        // Get PalmPay secret key from config
        $secretKey = config('services.palmpay.webhook_secret');

        if (!$secretKey) {
            Log::warning('PalmPay webhook secret not configured');
            return true; // Allow in development
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }
}
