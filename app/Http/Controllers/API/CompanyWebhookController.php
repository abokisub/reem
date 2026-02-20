<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyWebhookController extends Controller
{
    /**
     * Get company's outgoing webhook events (sanitized view)
     */
    public function index(Request $request)
    {
        $company = Auth::user()->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $query = WebhookEvent::query()
            ->where('company_id', $company->id)
            ->where('direction', 'outgoing') // Only outgoing webhooks
            ->with('transaction:id,transaction_ref');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
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

        // Transform for company view (sanitized)
        $webhooks->getCollection()->transform(function ($webhook) {
            return [
                'id' => $webhook->id,
                'event_id' => $webhook->event_id,
                'transaction_ref' => $webhook->transaction?->transaction_ref,
                'event_type' => $webhook->event_type,
                'status' => $webhook->status,
                'delivery_status' => $this->getDeliveryStatusLabel($webhook->status),
                'attempt_count' => $webhook->attempt_count,
                'last_attempt_at' => $webhook->last_attempt_at?->format('Y-m-d H:i:s'),
                'next_retry_at' => $webhook->next_retry_at?->format('Y-m-d H:i:s'),
                'http_status' => $webhook->http_status,
                'created_at' => $webhook->created_at->format('Y-m-d H:i:s'),
                // NO raw payload
                // NO raw response_body
                // NO internal logs
                // NO provider details
            ];
        });

        return response()->json($webhooks);
    }

    /**
     * Get single webhook event details (sanitized)
     */
    public function show(WebhookEvent $webhook)
    {
        $company = Auth::user()->company;

        // Verify webhook belongs to company
        if ($webhook->company_id !== $company->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only show outgoing webhooks
        if ($webhook->direction !== 'outgoing') {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $webhook->id,
            'event_id' => $webhook->event_id,
            'transaction_ref' => $webhook->transaction?->transaction_ref,
            'event_type' => $webhook->event_type,
            'status' => $webhook->status,
            'delivery_status' => $this->getDeliveryStatusLabel($webhook->status),
            'attempt_count' => $webhook->attempt_count,
            'last_attempt_at' => $webhook->last_attempt_at?->format('Y-m-d H:i:s'),
            'next_retry_at' => $webhook->next_retry_at?->format('Y-m-d H:i:s'),
            'http_status' => $webhook->http_status,
            'created_at' => $webhook->created_at->format('Y-m-d H:i:s'),
            // Sanitized response (no raw data)
            'response_summary' => $this->getResponseSummary($webhook),
        ]);
    }

    /**
     * Get delivery status label
     */
    private function getDeliveryStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'Pending',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'duplicate' => 'Duplicate',
            default => ucfirst($status)
        };
    }

    /**
     * Get sanitized response summary
     */
    private function getResponseSummary(WebhookEvent $webhook): string
    {
        if ($webhook->status === 'delivered') {
            return 'Webhook delivered successfully';
        }

        if ($webhook->status === 'failed') {
            if ($webhook->attempt_count >= 5) {
                return 'Max retry attempts reached';
            }
            return 'Delivery failed, will retry';
        }

        if ($webhook->status === 'pending') {
            return 'Pending delivery';
        }

        return 'Unknown status';
    }
}
