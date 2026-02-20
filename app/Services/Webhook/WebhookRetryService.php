<?php

namespace App\Services\Webhook;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;

class WebhookRetryService
{
    private OutgoingWebhookService $outgoingService;

    public function __construct(OutgoingWebhookService $outgoingService)
    {
        $this->outgoingService = $outgoingService;
    }

    /**
     * Retry all failed webhooks that are due for retry
     */
    public function retryDue(): array
    {
        $results = [
            'checked' => 0,
            'retried' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'max_attempts_reached' => 0,
        ];

        // Find webhooks due for retry
        $webhooks = WebhookEvent::where('direction', 'outgoing')
            ->where('status', 'failed')
            ->where('attempt_count', '<', 5)
            ->where('next_retry_at', '<=', now())
            ->orderBy('next_retry_at', 'asc')
            ->limit(100)
            ->get();

        foreach ($webhooks as $webhook) {
            $results['checked']++;

            if ($webhook->attempt_count >= 5) {
                $results['max_attempts_reached']++;
                continue;
            }

            $results['retried']++;

            $success = $this->outgoingService->deliver($webhook);

            if ($success) {
                $results['succeeded']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info('Webhook retry batch completed', $results);

        return $results;
    }

    /**
     * Manually retry a specific webhook
     */
    public function retryWebhook(WebhookEvent $webhook): bool
    {
        if (!$webhook->isOutgoing()) {
            Log::warning('Cannot retry incoming webhook', [
                'event_id' => $webhook->event_id
            ]);
            return false;
        }

        if ($webhook->attempt_count >= 5) {
            Log::warning('Webhook has reached max retry attempts', [
                'event_id' => $webhook->event_id,
                'attempts' => $webhook->attempt_count
            ]);
            return false;
        }

        Log::info('Manual webhook retry initiated', [
            'event_id' => $webhook->event_id,
            'attempt' => $webhook->attempt_count + 1
        ]);

        return $this->outgoingService->deliver($webhook);
    }
}
