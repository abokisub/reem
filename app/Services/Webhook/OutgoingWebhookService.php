<?php

namespace App\Services\Webhook;

use App\Models\WebhookEvent;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OutgoingWebhookService
{
    /**
     * Send webhook to company endpoint
     */
    public function send(Transaction $transaction, string $eventType): WebhookEvent
    {
        $company = $transaction->company;

        // Generate unique event ID
        $eventId = Str::uuid()->toString();

        // Prepare payload
        $payload = $this->preparePayload($transaction, $eventType);

        // Create webhook event record
        $webhookEvent = WebhookEvent::create([
            'event_id' => $eventId,
            'transaction_id' => $transaction->id,
            'direction' => 'outgoing',
            'company_id' => $company->id,
            'provider_name' => 'pointpay',
            'endpoint_url' => $company->webhook_url,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'pending',
            'attempt_count' => 0,
        ]);

        // Attempt to deliver immediately
        $this->deliver($webhookEvent);

        return $webhookEvent;
    }

    /**
     * Deliver webhook to endpoint
     */
    public function deliver(WebhookEvent $webhookEvent): bool
    {
        if (!$webhookEvent->endpoint_url) {
            Log::warning('No webhook URL configured for company', [
                'company_id' => $webhookEvent->company_id,
                'event_id' => $webhookEvent->event_id
            ]);

            $webhookEvent->update([
                'status' => 'failed',
                'response_body' => 'No webhook URL configured'
            ]);

            return false;
        }

        // Increment attempt count
        $webhookEvent->increment('attempt_count');
        $webhookEvent->update(['last_attempt_at' => now()]);

        try {
            // Get company webhook secret for signature
            $company = $webhookEvent->company;
            $webhookSecret = $company->webhook_secret;
            
            // Ensure webhook secret is a plain string (not serialized)
            // Laravel's encrypted cast may return serialized format
            if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
                $webhookSecret = unserialize($webhookSecret);
            }
            
            // Generate HMAC-SHA256 signature
            $jsonPayload = json_encode($webhookEvent->payload);
            $signature = hash_hmac('sha256', $jsonPayload, $webhookSecret);
            
            // Send HTTP POST request with signature
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-PointWave-Signature' => $signature,
                    'X-PointWave-Event-ID' => $webhookEvent->event_id,
                    'X-PointWave-Event-Type' => $webhookEvent->event_type,
                    'X-PointWave-Timestamp' => now()->timestamp,
                ])
                ->post($webhookEvent->endpoint_url, $webhookEvent->payload);

            $statusCode = $response->status();
            $responseBody = $response->body();

            // Update webhook event
            $webhookEvent->update([
                'http_status' => $statusCode,
                'response_body' => $responseBody,
                'status' => $statusCode >= 200 && $statusCode < 300 ? 'delivered' : 'failed',
                'next_retry_at' => $statusCode >= 200 && $statusCode < 300 
                    ? null 
                    : $webhookEvent->calculateNextRetry(),
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('Webhook delivered successfully', [
                    'event_id' => $webhookEvent->event_id,
                    'endpoint' => $webhookEvent->endpoint_url,
                    'status_code' => $statusCode
                ]);

                return true;
            } else {
                Log::warning('Webhook delivery failed', [
                    'event_id' => $webhookEvent->event_id,
                    'endpoint' => $webhookEvent->endpoint_url,
                    'status_code' => $statusCode,
                    'response' => $responseBody
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Webhook delivery exception', [
                'event_id' => $webhookEvent->event_id,
                'endpoint' => $webhookEvent->endpoint_url,
                'error' => $e->getMessage()
            ]);

            $webhookEvent->update([
                'status' => 'failed',
                'response_body' => $e->getMessage(),
                'next_retry_at' => $webhookEvent->calculateNextRetry(),
            ]);

            return false;
        }
    }

    /**
     * Prepare webhook payload
     */
    private function preparePayload(Transaction $transaction, string $eventType): array
    {
        return [
            'event' => $eventType,
            'event_id' => Str::uuid()->toString(),
            'timestamp' => now()->toISOString(),
            'data' => [
                'transaction_id' => $transaction->transaction_ref,
                'reference' => $transaction->provider_reference ?? $transaction->transaction_ref,
                'session_id' => $transaction->session_id,
                'type' => $transaction->transaction_type,
                'amount' => $transaction->amount,
                'fee' => $transaction->fee,
                'net_amount' => $transaction->net_amount,
                'currency' => $transaction->currency ?? 'NGN',
                'status' => $transaction->status,
                'settlement_status' => $transaction->settlement_status,
                'customer' => [
                    'name' => $transaction->payer_name ?? $transaction->customer->name ?? null,
                    'account_number' => $transaction->payer_account_number ?? null,
                    'bank_name' => $transaction->payer_bank_name ?? null,
                    'email' => $transaction->customer->email ?? null,
                ],
                'virtual_account' => [
                    'account_number' => $transaction->virtualAccount->account_number ?? null,
                    'account_name' => $transaction->virtualAccount->account_name ?? null,
                ],
                'created_at' => $transaction->created_at->toISOString(),
                'updated_at' => $transaction->updated_at->toISOString(),
            ]
        ];
    }
}
