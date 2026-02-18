<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EasyAccessWebhookController extends Controller
{
    /**
     * Handle Easy Access webhook notifications
     * 
     * Expected payload (Data purchase only):
     * {
     *   "status": "success|failed",
     *   "message": "Transaction completed successfully",
     *   "reference": "DATAeb84c154007xxx",
     *   "client_reference": "test_xxx",
     *   "transaction_date": "2025-12-02 05:26:00"
     * }
     */
    public function handle(Request $request)
    {
        try {
            // Log the incoming webhook
            Log::info('Easy Access Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Store webhook in webhook_logs table
            DB::table('webhook_logs')->insert([
                'provider' => 'easyaccess',
                'event_type' => 'transaction_status_update',
                'payload' => json_encode($request->all()),
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $status = $request->input('status');
            $reference = $request->input('reference');
            $clientReference = $request->input('client_reference');

            if (!$reference && !$clientReference) {
                Log::warning('Easy Access Webhook: No reference provided');
                return response()->json(['status' => 'error'], 400);
            }

            // Map Easy Access status to internal status
            $internalStatus = $this->mapStatus($status);

            // Try to find transaction by reference or client_reference
            $searchReference = $clientReference ?: $reference;

            // Search in data table (Easy Access webhooks are for data purchase only)
            $transaction = DB::table('data')
                ->where(function ($query) use ($searchReference, $reference) {
                    $query->where('transid', $searchReference)
                        ->orWhere('client_reference', $searchReference)
                        ->orWhere('api_reference', $reference);
                })
                ->first();

            if (!$transaction) {
                Log::warning('Easy Access Webhook: Transaction not found', [
                    'reference' => $reference,
                    'client_reference' => $clientReference
                ]);
                return response()->json(['status' => 'error'], 404);
            }

            // Update transaction status
            $updateData = [
                'plan_status' => $internalStatus,
                'updated_at' => now(),
            ];

            // Store Easy Access reference if available
            if ($reference) {
                $updateData['api_reference'] = $reference;
            }

            DB::table('data')
                ->where('id', $transaction->id)
                ->update($updateData);

            Log::info('Easy Access Webhook: Transaction updated', [
                'transaction_id' => $transaction->id,
                'status' => $internalStatus,
                'reference' => $reference
            ]);

            // Update webhook log status
            DB::table('webhook_logs')
                ->where('provider', 'easyaccess')
                ->where('payload', 'LIKE', '%' . $searchReference . '%')
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->update([
                    'status' => 'processed',
                    'updated_at' => now()
                ]);

            // Easy Access expects 200 OK response within 5 seconds
            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Easy Access Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Still return 200 to prevent retries
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Map Easy Access status to internal status codes
     * 
     * @param string $status
     * @return string
     */
    private function mapStatus($status)
    {
        $statusMap = [
            'success' => '1',      // Success
            'successful' => '1',   // Success
            'failed' => '2',       // Failed
        ];

        return $statusMap[strtolower($status)] ?? '0';
    }
}
