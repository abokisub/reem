<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PalmPayWebhook;
use Illuminate\Http\Request;

class AdminPalmPayWebhookController extends Controller
{
    /**
     * Get all PalmPay webhook logs (admin view)
     */
    public function index(Request $request)
    {
        $query = PalmPayWebhook::query()
            ->with(['transaction']);

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

        // Search by reference
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('palmpay_reference', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%");
            });
        }

        $webhooks = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        // Transform for admin view
        $webhooks->getCollection()->transform(function ($webhook) {
            return [
                'id' => $webhook->id,
                'event_type' => $webhook->event_type,
                'palmpay_reference' => $webhook->palmpay_reference,
                'transaction_id' => $webhook->transaction_id,
                'transaction_ref' => $webhook->transaction?->transaction_ref ?? null,
                'status' => $webhook->status,
                'verified' => $webhook->verified,
                'processed' => $webhook->processed,
                'retry_count' => $webhook->retry_count,
                'next_retry_at' => $webhook->next_retry_at?->toISOString(),
                'processing_error' => $webhook->processing_error,
                'payload' => $webhook->payload, // Full payload for admin
                'signature' => $webhook->signature,
                'processed_at' => $webhook->processed_at?->toISOString(),
                'created_at' => $webhook->created_at->toISOString(),
                'updated_at' => $webhook->updated_at->toISOString(),
            ];
        });

        return response()->json($webhooks);
    }

    /**
     * Get single PalmPay webhook details
     */
    public function show($id)
    {
        $webhook = PalmPayWebhook::with(['transaction'])->findOrFail($id);

        return response()->json([
            'id' => $webhook->id,
            'event_type' => $webhook->event_type,
            'palmpay_reference' => $webhook->palmpay_reference,
            'transaction_id' => $webhook->transaction_id,
            'transaction_ref' => $webhook->transaction?->transaction_ref ?? null,
            'status' => $webhook->status,
            'verified' => $webhook->verified,
            'processed' => $webhook->processed,
            'retry_count' => $webhook->retry_count,
            'next_retry_at' => $webhook->next_retry_at?->toISOString(),
            'processing_error' => $webhook->processing_error,
            'payload' => $webhook->payload,
            'signature' => $webhook->signature,
            'processed_at' => $webhook->processed_at?->toISOString(),
            'created_at' => $webhook->created_at->toISOString(),
            'updated_at' => $webhook->updated_at->toISOString(),
        ]);
    }

    /**
     * Get webhook statistics
     */
    public function stats()
    {
        $total = PalmPayWebhook::count();
        $processed = PalmPayWebhook::where('status', 'processed')->count();
        $failed = PalmPayWebhook::where('status', 'failed')->count();
        $pending = PalmPayWebhook::where('status', 'pending')->count();
        $exhausted = PalmPayWebhook::where('status', 'exhausted')->count();

        return response()->json([
            'total' => $total,
            'processed' => $processed,
            'failed' => $failed,
            'pending' => $pending,
            'exhausted' => $exhausted,
        ]);
    }
}
