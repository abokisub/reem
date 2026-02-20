<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\Webhook\WebhookRetryService;
use Illuminate\Http\Request;

class AdminWebhookController extends Controller
{
    private WebhookRetryService $retryService;

    public function __construct(WebhookRetryService $retryService)
    {
        $this->retryService = $retryService;
    }

    /**
     * Get all webhook events (admin view)
     */
    public function index(Request $request)
    {
        $query = WebhookEvent::query()
            ->with(['transaction', 'company']);

        // Filter by direction
        if ($request->has('direction')) {
            $query->where('direction', $request->direction);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by provider
        if ($request->has('provider')) {
            $query->where('provider_name', $request->provider);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $webhooks = $query->orderBy('created_at', 'desc')
            ->paginate(50);

        // Transform for admin view (full access)
        $webhooks->getCollection()->transform(function ($webhook) {
            return [
                'id' => $webhook->id,
                'event_id' => $webhook->event_id,
                'transaction_id' => $webhook->transaction_id,
                'transaction_ref' => $webhook->transaction?->transaction_ref,
                'direction' => $webhook->direction,
                'company_id' => $webhook->company_id,
                'company_name' => $webhook->company?->company_name,
                'provider_name' => $webhook->provider_name,
                'endpoint_url' => $webhook->endpoint_url,
                'event_type' => $webhook->event_type,
                'payload' => $webhook->payload, // Full payload for admin
                'status' => $webhook->status,
                'attempt_count' => $webhook->attempt_count,
                'last_attempt_at' => $webhook->last_attempt_at?->toISOString(),
                'next_retry_at' => $webhook->next_retry_at?->toISOString(),
                'http_status' => $webhook->http_status,
                'response_body' => $webhook->response_body, // Full response for admin
                'created_at' => $webhook->created_at->toISOString(),
            ];
        });

        return response()->json($webhooks);
    }

    /**
     * Get single webhook event details
     */
    public function show(WebhookEvent $webhook)
    {
        $webhook->load(['transaction', 'company']);

        return response()->json([
            'id' => $webhook->id,
            'event_id' => $webhook->event_id,
            'transaction_id' => $webhook->transaction_id,
            'transaction_ref' => $webhook->transaction?->transaction_ref,
            'direction' => $webhook->direction,
            'company_id' => $webhook->company_id,
            'company_name' => $webhook->company?->company_name,
            'provider_name' => $webhook->provider_name,
            'endpoint_url' => $webhook->endpoint_url,
            'event_type' => $webhook->event_type,
            'payload' => $webhook->payload,
            'status' => $webhook->status,
            'attempt_count' => $webhook->attempt_count,
            'last_attempt_at' => $webhook->last_attempt_at?->toISOString(),
            'next_retry_at' => $webhook->next_retry_at?->toISOString(),
            'http_status' => $webhook->http_status,
            'response_body' => $webhook->response_body,
            'created_at' => $webhook->created_at->toISOString(),
            'updated_at' => $webhook->updated_at->toISOString(),
        ]);
    }

    /**
     * Manually retry a webhook
     */
    public function retry(WebhookEvent $webhook)
    {
        $success = $this->retryService->retryWebhook($webhook);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Webhook retried successfully' : 'Webhook retry failed',
            'webhook' => [
                'event_id' => $webhook->event_id,
                'status' => $webhook->fresh()->status,
                'attempt_count' => $webhook->fresh()->attempt_count,
            ]
        ]);
    }
}
