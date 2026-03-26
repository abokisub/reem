<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CardCheckoutOrder;
use App\Services\PalmPay\CardCheckoutService;
use App\Services\PalmPay\PalmPaySignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardCheckoutController extends Controller
{
    public function __construct(
        private CardCheckoutService $service,
        private PalmPaySignature $signer
    ) {}

    /**
     * POST /api/v1/checkout/card/create
     * Merchant creates a card checkout order
     */
    public function create(Request $request)
    {
        if (!config('services.palmpay.card_checkout_enabled', false)) {
            return response()->json(['status' => 'error', 'message' => 'Card checkout is not available.'], 503);
        }

        $validated = $request->validate([
            'amount'                  => 'required|integer|min:100',
            'currency'                => 'sometimes|string|in:NGN',
            'title'                   => 'sometimes|string|max:100',
            'description'             => 'sometimes|string|max:200',
            'callback_url'            => 'required|url',
            'reference'               => 'sometimes|string|max:100',
            'customer_info'           => 'sometimes|array',
            'customer_info.userId'    => 'sometimes|string|max:15',
            'customer_info.userName'  => 'sometimes|string|max:15',
            'customer_info.phone'     => 'sometimes|string|max:15',
            'customer_info.email'     => 'sometimes|email|max:50',
            'customer_info.firstName' => 'sometimes|string|max:50',
            'customer_info.lastName'  => 'sometimes|string|max:50',
            'goods_details'           => 'sometimes|array',
            'order_expire_time'       => 'sometimes|integer|min:1800|max:86400',
        ]);

        $company = $request->user(); // resolved via auth.token middleware
        $result  = $this->service->createOrder($company->id, $validated);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json([
            'status'       => 'success',
            'order_id'     => $result['order_id'],
            'checkout_url' => $result['checkout_url'],
            'order_no'     => $result['order_no'],
            'amount'       => $result['amount'],
            'currency'     => $result['currency'],
        ]);
    }

    /**
     * POST /api/v1/checkout/card/query
     * Query order status
     */
    public function query(Request $request)
    {
        $request->validate(['order_id' => 'required|string|max:32']);

        $order = CardCheckoutOrder::where('merchant_order_id', $request->order_id)
            ->where('company_id', $request->user()->id)
            ->firstOrFail();

        $result = $this->service->queryOrder($order->merchant_order_id);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json(['status' => 'success', 'data' => $result['data']]);
    }

    /**
     * POST /api/v1/checkout/card/refund
     * Initiate a refund
     */
    public function refund(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string|max:32',
            'amount'   => 'required|integer|min:1',
            'remark'   => 'sometimes|string|max:200',
        ]);

        $order = CardCheckoutOrder::where('merchant_order_id', $request->order_id)
            ->where('company_id', $request->user()->id)
            ->where('order_status', CardCheckoutOrder::STATUS_SUCCESS)
            ->firstOrFail();

        $result = $this->service->refund(
            $request->user()->id,
            $order->merchant_order_id,
            $request->amount,
            ['remark' => $request->remark ?? 'Refund']
        );

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json(['status' => 'success', 'data' => $result['data']]);
    }

    /**
     * POST /api/webhooks/palmpay/card-payment
     * PalmPay payment notification (no auth middleware)
     */
    public function paymentWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('CardCheckout payment webhook received', $payload);

        $ok = $this->service->handlePaymentNotification($payload, $this->signer);

        if (!$ok) {
            return response('fail', 400);
        }

        return response('success', 200);
    }

    /**
     * POST /api/webhooks/palmpay/card-refund
     * PalmPay refund notification (no auth middleware)
     */
    public function refundWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('CardCheckout refund webhook received', $payload);

        // Reuse same handler — both payment and refund notifications have same structure
        $ok = $this->service->handlePaymentNotification($payload, $this->signer);

        return $ok ? response('success', 200) : response('fail', 400);
    }
}
