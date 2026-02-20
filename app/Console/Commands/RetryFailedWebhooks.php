<?php

namespace App\Console\Commands;

use App\Services\Webhook\WebhookRetryService;
use Illuminate\Console\Command;

class RetryFailedWebhooks extends Command
{
    protected $signature = 'webhooks:retry';
    protected $description = 'Retry failed outgoing webhooks that are due for retry';

    public function handle(WebhookRetryService $service)
    {
        $this->info('Starting webhook retry process...');

        $results = $service->retryDue();

        $this->info("Webhook retry completed:");
        $this->info("  Checked: {$results['checked']}");
        $this->info("  Retried: {$results['retried']}");
        $this->info("  Succeeded: {$results['succeeded']}");
        $this->info("  Failed: {$results['failed']}");
        $this->info("  Max attempts reached: {$results['max_attempts_reached']}");

        return 0;
    }
}
