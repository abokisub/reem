<?php

namespace App\Services\Webhook;

use App\Models\WebhookEvent;
use App\Models\Transaction;
use App\Services\PalmPay\PalmPaySignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IncomingWebhookService
{
    private PalmPaySignature $signatureService;

    public function __construct(PalmPaySignature $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Process incoming webhook from provider
     */
    public function process(array $payload, string $signature, string $providerName): array
    {
        // Step 1: Verify provider signature
        if (!$this->verifySignature($payload, $signature, $providerName)) {
            Log::warning('Incoming webhook signature verification failed', [
                'provider' => $providerName,
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'error' => 'Invalid signature'
            ];
        }

        // Step 2: Extract provider reference for idempotency
        $providerReference = $this->extractProviderReference($payload, $providerName);

        if (!$providerReference) {
            Log::error('Could not extract provider reference from webhook', [
                'provider' => $providerName,
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'error' => 'Missing provider reference'
            ];
        }

        // Step 3: Check for duplicate (idempotency)
        $existingEvent = WebhookEvent::where('event_id', $providerReference)
            ->where('direction', 'incoming')
            ->first();

        if ($existingEvent) {
            Log::info('Duplicate incoming webhook detected', [
                'event_id' => $providerReference,
                'provider' => $providerName
            ]);

            return [
                'success' => true,
                'duplicate' => true,
                'event_id' => $providerReference
            ];
        }

        // Step 4: Find associated transaction
        $transaction = $this->findTransaction($providerReference, $payload);

        // Step 5: Log webhook event
        $webhookEvent = $this->logWebhookEvent(
            $providerReference,
            $transaction,
            $providerName,
            $payload
        );

        Log::info('Incoming webhook processed successfully', [
            'event_id' => $providerReference,
            'transaction_id' => $transaction?->id,
            'provider' => $providerName
        ]);

        return [
            'success' => true,
            'duplicate' => false,
            'event_id' => $providerReference,
            'webhook_event_id' => $webhookEvent->id
        ];
    }

    /**
     * Verify provider signature
     */
    private function verifySignature(array $payload, string $signature, string $providerName): bool
    {
        if ($providerName === 'palmpay') {
            return $this->signatureService->verify($payload, $signature);
        }

        // Add other providers here
        return false;
    }

    /**
     * Extract provider reference from payload
     */
    private function extractProviderReference(array $payload, string $providerName): ?string
    {
        if ($providerName === 'palmpay') {
            return $payload['reference'] ?? $payload['transaction_reference'] ?? null;
        }

        // Add other providers here
        return null;
    }

    /**
     * Find transaction by provider reference
     */
    private function findTransaction(string $providerReference, array $payload): ?Transaction
    {
        return Transaction::where('provider_reference', $providerReference)
            ->orWhere('transaction_ref', $providerReference)
            ->first();
    }

    /**
     * Log webhook event to database
     */
    private function logWebhookEvent(
        string $providerReference,
        ?Transaction $transaction,
        string $providerName,
        array $payload
    ): WebhookEvent {
        return WebhookEvent::create([
            'event_id' => $providerReference,
            'transaction_id' => $transaction?->id,
            'direction' => 'incoming',
            'company_id' => $transaction?->company_id ?? 1, // Default to system
            'provider_name' => $providerName,
            'endpoint_url' => null, // Incoming webhooks don't have endpoint
            'event_type' => $payload['event_type'] ?? $payload['type'] ?? 'unknown',
            'payload' => $payload,
            'status' => 'delivered', // Incoming webhooks are immediately delivered
            'attempt_count' => 1,
            'last_attempt_at' => now(),
            'http_status' => 200,
        ]);
    }
}
