<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VTPassWebhookController extends Controller
{
    /**
     * Handle VTPass webhook notifications
     * 
     * Expected payload structure:
     * {
     *   "type": "transaction_status_update|variation_codes_update",
     *   "data": {
     *     ...transaction or variation data
     *   }
     * }
     */
    public function handle(Request $request)
    {
        try {
            // Log the incoming webhook
            Log::info('VTPass Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Store webhook in webhook_logs table
            DB::table('webhook_logs')->insert([
                'provider' => 'vtpass',
                'event_type' => $request->input('type', 'unknown'),
                'payload' => json_encode($request->all()),
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $type = $request->input('type');
            $data = $request->input('data', []);

            // Handle different webhook types
            switch ($type) {
                case 'transaction_status_update':
                    $this->handleTransactionStatusUpdate($data);
                    break;

                case 'variation_codes_update':
                    $this->handleVariationCodesUpdate($data);
                    break;

                default:
                    Log::warning('VTPass Webhook: Unknown type', ['type' => $type]);
            }

            // Update webhook log status
            DB::table('webhook_logs')
                ->where('provider', 'vtpass')
                ->where('payload', 'LIKE', '%' . $request->input('type') . '%')
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->update([
                    'status' => 'processed',
                    'updated_at' => now()
                ]);

            // VTPass requires this specific response
            return response()->json([
                'response' => 'success'
            ], 200);

        } catch (\Exception $e) {
            Log::error('VTPass Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Still return success to prevent VTPass from retrying
            return response()->json([
                'response' => 'success'
            ], 200);
        }
    }

    /**
     * Handle transaction status update
     */
    private function handleTransactionStatusUpdate($data)
    {
        $requestId = $data['request_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$requestId) {
            Log::warning('VTPass Webhook: No request_id provided');
            return;
        }

        // Map VTPass status to internal status
        $internalStatus = $this->mapStatus($status);

        // Try to find transaction in different tables
        $tables = ['data', 'airtime', 'cable', 'bill', 'exam'];

        foreach ($tables as $table) {
            $updated = DB::table($table)
                ->where('request_id', $requestId)
                ->update([
                    'plan_status' => $internalStatus,
                    'updated_at' => now()
                ]);

            if ($updated > 0) {
                Log::info('VTPass Webhook: Transaction updated', [
                    'request_id' => $requestId,
                    'status' => $internalStatus,
                    'table' => $table
                ]);
                return;
            }
        }

        Log::warning('VTPass Webhook: Transaction not found', [
            'request_id' => $requestId
        ]);
    }

    /**
     * Handle variation codes update
     */
    private function handleVariationCodesUpdate($data)
    {
        $serviceID = $data['serviceID'] ?? null;

        Log::info('VTPass Webhook: Variation codes updated', [
            'serviceID' => $serviceID,
            'data' => $data
        ]);

        // TODO: Implement automatic plan sync when variation codes change
        // This would trigger: php artisan vtpass:sync-plans {serviceID}
    }

    /**
     * Map VTPass status to internal status codes
     */
    private function mapStatus($status)
    {
        $statusMap = [
            'delivered' => '1',     // Success
            'successful' => '1',    // Success
            'failed' => '2',        // Failed
            'pending' => '0',       // Pending/Processing
        ];

        return $statusMap[strtolower($status)] ?? '0';
    }
}
