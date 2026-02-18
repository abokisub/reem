<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutopilotWebhookController extends Controller
{
    /**
     * Handle Autopilot webhook notifications
     * 
     * Supported events: DATA, AIRTIME
     * 
     * Expected payload:
     * {
     *   "status": true,
     *   "code": 200,
     *   "data": {
     *     "type": "Data|Airtime",
     *     "message": "...",
     *     "yourReference": "...",
     *     "ourReference": "..."
     *   },
     *   "time": "2023-28-06 09:42:16"
     * }
     */
    public function handle(Request $request)
    {
        try {
            // Log the incoming webhook
            Log::info('Autopilot Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Store webhook in webhook_logs table
            DB::table('webhook_logs')->insert([
                'provider' => 'autopilot',
                'event_type' => $request->input('data.type', 'unknown'),
                'payload' => json_encode($request->all()),
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $status = $request->input('status');
            $code = $request->input('code');
            $type = $request->input('data.type');
            $yourReference = $request->input('data.yourReference');
            $ourReference = $request->input('data.ourReference');

            if (!$yourReference) {
                Log::warning('Autopilot Webhook: No reference provided');
                return response()->json(['status' => 'error'], 400);
            }

            // Map Autopilot status to internal status
            $internalStatus = $this->mapStatus($status, $code);

            // Determine table based on type
            $table = null;
            if (strtolower($type) === 'data') {
                $table = 'data';
            } elseif (strtolower($type) === 'airtime') {
                $table = 'airtime';
            }

            if (!$table) {
                Log::warning('Autopilot Webhook: Unknown type', ['type' => $type]);
                return response()->json(['status' => 'error'], 400);
            }

            // Find transaction by reference
            $transaction = DB::table($table)
                ->where(function ($query) use ($yourReference, $ourReference) {
                    $query->where('transid', $yourReference)
                        ->orWhere('client_reference', $yourReference)
                        ->orWhere('api_reference', $ourReference);
                })
                ->first();

            if (!$transaction) {
                Log::warning('Autopilot Webhook: Transaction not found', [
                    'yourReference' => $yourReference,
                    'ourReference' => $ourReference,
                    'table' => $table
                ]);
                return response()->json(['status' => 'error'], 404);
            }

            // Update transaction status
            $updateData = [
                'plan_status' => $internalStatus,
                'updated_at' => now(),
            ];

            // Store Autopilot reference if available
            if ($ourReference) {
                $updateData['api_reference'] = $ourReference;
            }

            DB::table($table)
                ->where('id', $transaction->id)
                ->update($updateData);

            Log::info('Autopilot Webhook: Transaction updated', [
                'transaction_id' => $transaction->id,
                'status' => $internalStatus,
                'type' => $type,
                'table' => $table
            ]);

            // Update webhook log status
            DB::table('webhook_logs')
                ->where('provider', 'autopilot')
                ->where('payload', 'LIKE', '%' . $yourReference . '%')
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->update([
                    'status' => 'processed',
                    'updated_at' => now()
                ]);

            // Autopilot expects 200 OK response
            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Autopilot Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Still return 200 to prevent retries
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Map Autopilot status to internal status codes
     * 
     * @param bool $status
     * @param int $code
     * @return string
     */
    private function mapStatus($status, $code)
    {
        // Success: status=true AND code=200 or 201
        if ($status === true && in_array($code, [200, 201])) {
            return '1'; // Success
        }

        // Failed: status=false OR error codes
        if ($status === false || in_array($code, [401, 409, 424, 429]) || $code >= 500) {
            return '2'; // Failed
        }

        return '0'; // Pending/Processing
    }
}
