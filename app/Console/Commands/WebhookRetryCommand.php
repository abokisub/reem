<?php

namespace App\Console\Commands;

use App\Models\PalmPayWebhook;
use App\Services\PalmPay\WebhookHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * WebhookRetryCommand
 *
 * Retries failed webhooks using exponential backoff.
 * Schedule: every minute (`* * * * *`)
 */
class WebhookRetryCommand extends Command
{
    protected $signature = 'webhooks:retry';
    protected $description = 'Retry failed PalmPay webhooks with exponential backoff';

    // Retry schedule in minutes after previous attempt
    private const RETRY_DELAYS = [0, 1, 5, 15, 60]; // 5 retries total
    private const MAX_RETRIES = 5;

    public function handle(WebhookHandler $handler): int
    {
        $webhooks = PalmPayWebhook::where('status', 'pending')
            ->where('processed', false)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->get();

        if ($webhooks->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Processing {$webhooks->count()} pending webhooks...");

        foreach ($webhooks as $webhook) {
            try {
                // Mark as processing to prevent parallel runs
                $webhook->update(['status' => 'processing']);

                $handler->processWebhook($webhook, $webhook->payload);

                $webhook->update([
                    'status' => 'processed',
                    'processed' => true,
                    'processed_at' => now(),
                ]);

                Log::info("Webhook processed successfully", ['webhook_id' => $webhook->id]);

            } catch (\Exception $e) {
                $newRetryCount = $webhook->retry_count + 1;

                if ($newRetryCount >= self::MAX_RETRIES) {
                    // Exhausted — notify admin, mark as failed
                    $webhook->update([
                        'status' => 'exhausted',
                        'retry_count' => $newRetryCount,
                        'processing_error' => $e->getMessage(),
                    ]);

                    Log::critical("Webhook exhausted after max retries. Admin action required.", [
                        'webhook_id' => $webhook->id,
                        'reference' => $webhook->palmpay_reference,
                        'last_error' => $e->getMessage(),
                    ]);

                    // Log to failed_transactions for admin dashboard
                    \App\Models\FailedTransaction::firstOrCreate(
                        ['transaction_reference' => $webhook->palmpay_reference],
                        [
                            'company_id' => null,
                            'type' => 'webhook_failure',
                            'amount' => $webhook->payload['orderAmount'] ?? 0,
                            'payload' => $webhook->payload,
                            'failure_reason' => "Webhook exhausted: " . $e->getMessage(),
                            'status' => 'pending',
                        ]
                    );
                } else {
                    // Schedule next retry
                    $delayMinutes = self::RETRY_DELAYS[$newRetryCount] ?? 60;
                    $webhook->update([
                        'status' => 'pending',
                        'retry_count' => $newRetryCount,
                        'next_retry_at' => now()->addMinutes($delayMinutes),
                        'processing_error' => $e->getMessage(),
                    ]);

                    Log::warning("Webhook retry scheduled", [
                        'webhook_id' => $webhook->id,
                        'attempt' => $newRetryCount,
                        'next_retry_at' => now()->addMinutes($delayMinutes),
                    ]);
                }
            }
        }

        return Command::SUCCESS;
    }
}
