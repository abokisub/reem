<?php

namespace App\Jobs;

use App\Models\CompanyWebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOutgoingWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhookLog;

    /**
     * The number of times the job may be attempted.
     * We handle retries manually for custom backoff timing.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param CompanyWebhookLog $webhookLog
     * @return void
     */
    public function __construct(CompanyWebhookLog $webhookLog)
    {
        $this->webhookLog = $webhookLog;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Update attempt metadata
        $this->webhookLog->update([
            'last_attempt_at' => now(),
        ]);

        try {
            // Get company secret
            $company = $this->webhookLog->company;
            
            // Use Secret Key (API key) for webhook signatures - industry standard
            // Use api_secret_key (not webhook_secret) as it's the authoritative source
            $secret = $this->webhookLog->is_test ? $company->test_secret_key : $company->api_secret_key;
            
            if (!$secret) {
                Log::error('API secret key not configured for company', [
                    'company_id' => $company->id,
                    'is_test' => $this->webhookLog->is_test
                ]);
                
                $this->webhookLog->update([
                    'status' => 'failed',
                    'error_message' => 'API secret key not configured'
                ]);
                
                return;
            }

            // Encode payload with consistent formatting
            $payloadJson = json_encode($this->webhookLog->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Compute signature on the exact JSON string we're sending
            $signature = hash_hmac('sha256', $payloadJson, $secret);

            // Send the webhook with raw JSON body
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'PointWave-Webhook/1.0',
                    'X-PointWave-Signature' => $signature,  // No prefix - Kobopoint expects raw hash
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($this->webhookLog->webhook_url);

            $status = $response->successful() ? 'delivered' : 'failed';

            $this->webhookLog->update([
                'http_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 10000), // Limit size
                'status' => $status,
            ]);

            if ($response->successful()) {
                $this->webhookLog->update(['sent_at' => now()]);
                return;
            }

            $this->handleRetry();

        } catch (\Exception $e) {
            $this->webhookLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->handleRetry();
        }
    }

    /**
     * Logic for manual retries with exponential backoff
     */
    protected function handleRetry()
    {
        $maxAttempts = 5;

        if ($this->webhookLog->attempt_number >= $maxAttempts) {
            $this->webhookLog->update(['status' => 'delivery_failed']);

            // Move to Dead Letter Queue (DLQ)
            \Illuminate\Support\Facades\DB::table('dead_webhooks')->insert([
                'company_id' => $this->webhookLog->company_id,
                'webhook_url' => $this->webhookLog->webhook_url,
                'event_type' => $this->webhookLog->event_type,
                'payload' => json_encode($this->webhookLog->payload),
                'last_error' => $this->webhookLog->error_message ?? 'Max retries exceeded',
                'attempt_count' => $this->webhookLog->attempt_number,
                'failed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\AlertService::trigger(
                'WEBHOOK_DLQ',
                "Webhook permanently failed and moved to DLQ for company: {$this->webhookLog->company_id}",
                ['webhook_log_id' => $this->webhookLog->id, 'url' => $this->webhookLog->webhook_url],
                'critical'
            );

            Log::error("🛑 Webhook Delivery Permanently Failed (Moved to DLQ)", [
                'id' => $this->webhookLog->id,
                'company_id' => $this->webhookLog->company_id,
                'url' => $this->webhookLog->webhook_url
            ]);

            return;
        }

        // Backoff: 1m, 5m, 15m, 1h
        $delays = [
            1 => 60,    // After 1st attempt: 1 min
            2 => 300,   // After 2nd attempt: 5 min
            3 => 900,   // After 3rd attempt: 15 min
            4 => 3600,  // After 4th attempt: 1 hour
        ];

        $delaySeconds = $delays[$this->webhookLog->attempt_number] ?? 3600;
        $nextRetry = now()->addSeconds($delaySeconds);

        $this->webhookLog->update([
            'next_retry_at' => $nextRetry,
            'attempt_number' => $this->webhookLog->attempt_number + 1
        ]);

        // Re-dispatch with delay
        self::dispatch($this->webhookLog)->delay($nextRetry);
    }
}
