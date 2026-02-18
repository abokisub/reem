<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmePlugWebhookController extends Controller
{
    /**
     * Handle SME Plug webhook notifications
     * 
     * Expected payload structure:
     * {
     *   "status": "success|failed",
     *   "reference": "smeplug_reference",
     *   "customer_reference": "your_transaction_id",
     *   "type": "Data Purchase|Airtime Purchase",
     *   "beneficiary": "phone_number",
     *   "memo": "transaction_description",
     *   "response": "response_message",
     *   "price": amount
     * }
     */
    public function handle(Request $request)
    {
        try {
            // Log the incoming webhook
            Log::info('SME Plug Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Store webhook in webhook_logs table
            DB::table('webhook_logs')->insert([
                'provider' => 'smeplug',
                'event_type' => $request->input('type', 'unknown'),
                'payload' => json_encode($request->all()),
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $status = $request->input('status');
            $customerReference = $request->input('customer_reference');
            $type = $request->input('type');
            $smePlugReference = $request->input('reference');

            if (!$customerReference) {
                Log::warning('SME Plug Webhook: No customer_reference provided');
                return response()->json([
                    'status' => 'error',
                    'message' => 'customer_reference is required'
                ], 400);
            }

            // Determine which table to update based on transaction type
            $table = null;
            if (str_contains(strtolower($type), 'data')) {
                $table = 'data';
            } elseif (str_contains(strtolower($type), 'airtime')) {
                $table = 'airtime';
            }

            if (!$table) {
                Log::warning('SME Plug Webhook: Unknown transaction type', ['type' => $type]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unknown transaction type'
                ], 400);
            }

            // Check if transaction exists
            $transaction = DB::table($table)
                ->where('transid', $customerReference)
                ->first();

            if (!$transaction) {
                Log::warning('SME Plug Webhook: Transaction not found', [
                    'customer_reference' => $customerReference,
                    'table' => $table
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Map SME Plug status to internal status
            $internalStatus = $this->mapStatus($status);

            // Update transaction status
            $updateData = [
                'plan_status' => $internalStatus,
                'updated_at' => now(),
            ];

            // Store SME Plug reference if available
            if ($smePlugReference) {
                $updateData['api_reference'] = $smePlugReference;
            }

            DB::table($table)
                ->where('transid', $customerReference)
                ->update($updateData);

            Log::info('SME Plug Webhook: Transaction updated', [
                'customer_reference' => $customerReference,
                'status' => $internalStatus,
                'table' => $table
            ]);

            // Update webhook log status
            DB::table('webhook_logs')
                ->where('payload', 'LIKE', '%' . $customerReference . '%')
                ->where('provider', 'smeplug')
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->update([
                    'status' => 'processed',
                    'updated_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('SME Plug Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Map SME Plug status to internal status codes
     * 
     * @param string $status
     * @return string
     */
    private function mapStatus($status)
    {
        $statusMap = [
            'success' => '1',  // Success
            'failed' => '2',   // Failed
            'pending' => '0',  // Pending/Processing
        ];

        return $statusMap[strtolower($status)] ?? '0';
    }
}
