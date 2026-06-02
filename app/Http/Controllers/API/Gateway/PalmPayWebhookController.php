<?php

namespace App\Http\Controllers\API\Gateway;

use App\Http\Controllers\Controller;
use App\Services\PalmPay\WebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PalmPay Webhook Controller
 * 
 * Receives webhooks from PalmPay
 */
class PalmPayWebhookController extends Controller
{
    private WebhookHandler $webhookHandler;

    public function __construct()
    {
        $this->webhookHandler = new WebhookHandler();
    }

    /**
     * Handle incoming webhook from PalmPay
     * 
     * POST /api/webhooks/palmpay
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        try {
            Log::info('PalmPay Webhook Received', [
                'ip' => $request->ip(),
                'payload' => $request->all()
            ]);

            // Get signature from header
            $signature = $request->header('X-PalmPay-Signature') ?? $request->header('Sign');

            // Get payload
            $payload = $request->all();

            // Process webhook
            $result = $this->webhookHandler->handle($payload, $signature);

            // PalmPay requires the plain string "success" for a successful acknowledgement
            if ($result['success']) {
                $this->broadcastToKobopoint($payload, $signature);

                return response('success', 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response()->json($result, 400);

        } catch (\Exception $e) {
            Log::error('PalmPay Webhook Error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Broadcast processed PalmPay webhook to Kobopoint shadow receiver
     *
     * @param array $payload
     * @param string|null $signature
     * @return void
     */
    private function broadcastToKobopoint(array $payload, ?string $signature): void
    {
        try {
            $url = config('services.kobopoint.webhook_url');
            if (!$url) {
                return;
            }

            Log::info('Broadcasting PalmPay webhook to Kobopoint shadow', ['url' => $url]);

            \Illuminate\Support\Facades\Http::withHeaders([
                'X-PalmPay-Signature' => $signature,
                'Sign' => $signature,
                'Signature' => $signature,
                'X-Shadow-Broadcast' => 'true',
            ])
            ->timeout(5) // Fast timeout: do not block Pointwave response
            ->post($url, $payload);

            Log::info('PalmPay Webhook Broadcasted to Kobopoint Shadow successfully');
        } catch (\Exception $e) {
            Log::error('PalmPay Webhook Broadcast to Kobopoint Failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}