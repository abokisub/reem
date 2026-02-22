<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyWebhookLog;
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
        $query = CompanyWebhookLog::query()
            ->with(['transaction', 'company']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by event type
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
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
                'event_id' => 'WH_' . $webhook->id, // Generate event ID for compatibility
                'transaction_id' => $webhook->transaction_id,
                'transaction_ref' => $webhook->transaction?->transaction_ref,
                'direction' => 'outgoing', // All company webhooks are outgoing
                'company_id' => $webhook->company_id,
                'company_name' => $webhook->company?->company_name,
                'provider_name' => 'pointwave', // Provider is PointWave
                'endpoint_url' => $webhook->webhook_url,
                'event_type' => $webhook->event_type,
                'payload' => $webhook->payload, // Full payload for admin
                'status' => $webhook->status,
                'attempt_count' => $webhook->attempt_number ?? 0,
                'last_attempt_at' => $webhook->last_attempt_at ? 
                    (is_string($webhook->last_attempt_at) ? $webhook->last_attempt_at : $webhook->last_attempt_at->toISOString()) : null,
                'next_retry_at' => $webhook->next_retry_at ? 
                    (is_string($webhook->next_retry_at) ? $webhook->next_retry_at : $webhook->next_retry_at->toISOString()) : null,
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
    public function show($id)
    {
        $webhook = CompanyWebhookLog::with(['transaction', 'company'])->findOrFail($id);

        return response()->json([
            'id' => $webhook->id,
            'event_id' => 'WH_' . $webhook->id,
            'transaction_id' => $webhook->transaction_id,
            'transaction_ref' => $webhook->transaction?->transaction_ref,
            'direction' => 'outgoing',
            'company_id' => $webhook->company_id,
            'company_name' => $webhook->company?->company_name,
            'provider_name' => 'pointwave',
            'endpoint_url' => $webhook->webhook_url,
            'event_type' => $webhook->event_type,
            'payload' => $webhook->payload,
            'status' => $webhook->status,
            'attempt_count' => $webhook->attempt_number ?? 0,
            'last_attempt_at' => $webhook->last_attempt_at ? 
                (is_string($webhook->last_attempt_at) ? $webhook->last_attempt_at : $webhook->last_attempt_at->toISOString()) : null,
            'next_retry_at' => $webhook->next_retry_at ? 
                (is_string($webhook->next_retry_at) ? $webhook->next_retry_at : $webhook->next_retry_at->toISOString()) : null,
            'http_status' => $webhook->http_status,
            'response_body' => $webhook->response_body,
            'error_message' => $webhook->error_message,
            'created_at' => $webhook->created_at->toISOString(),
            'updated_at' => $webhook->updated_at->toISOString(),
        ]);
    }

    /**
     * Manually retry a webhook
     */
    public function retry($id)
    {
        $webhook = CompanyWebhookLog::findOrFail($id);
        
        // Re-dispatch the webhook job
        \App\Jobs\SendOutgoingWebhook::dispatch($webhook);

        return response()->json([
            'success' => true,
            'message' => 'Webhook retry queued successfully',
            'webhook' => [
                'id' => $webhook->id,
                'status' => $webhook->status,
                'attempt_count' => $webhook->attempt_number,
            ]
        ]);
    }
}
