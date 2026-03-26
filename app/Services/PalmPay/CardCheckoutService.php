<?php

namespace App\Services\PalmPay;

use App\Models\CardCheckoutOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CardCheckoutService
{
    public function __construct(private PalmPayClient $client) {}

    /**
     * Create a card checkout order and return the checkoutUrl
     */
    public function createOrder(int $companyId, array $params): array
    {
        if (!config('services.palmpay.card_checkout_enabled', false)) {
            return ['success' => false, 'message' => 'Card checkout is not enabled.'];
        }

        // Generate a unique merchant order ID
        $merchantOrderId = strtoupper(Str::random(16)) . time();
        $merchantOrderId = substr($merchantOrderId, 0, 32);

        $notifyUrl  = config('app.url') . '/api/webhooks/palmpay/card-payment';
        $callbackUrl = $params['callback_url'] ?? config('app.url');

        $payload = [
            'orderId'      => $merchantOrderId,
            'title'        => $params['title'] ?? 'Payment',
            'description'  => $params['description'] ?? '',
            'amount'       => (int) $params['amount'],
            'currency'     => $params['currency'] ?? 'NGN',
            'notifyUrl'    => $notifyUrl,
            'callBackUrl'  => $callbackUrl,
            'productType'  => 'bank_card',
            'customerInfo' => json_encode($params['customer_info'] ?? []),
        ];

        if (!empty($params['goods_details'])) {
            $payload['goodsDetails'] = json_encode($params['goods_details']);
        }

        if (!empty($params['order_expire_time'])) {
            $payload['orderExpireTime'] = (int) $params['order_expire_time'];
        }

        // Persist order before calling PalmPay
        $order = CardCheckoutOrder::create([
            'company_id'       => $companyId,
            'merchant_order_id'=> $merchantOrderId,
            'amount'           => $payload['amount'],
            'currency'         => $payload['currency'],
            'order_status'     => CardCheckoutOrder::STATUS_UNPAID,
            'notify_url'       => $notifyUrl,
            'callback_url'     => $callbackUrl,
            'customer_info'    => $params['customer_info'] ?? [],
            'reference'        => $params['reference'] ?? null,
        ]);

        try {
            $response = $this->client->post('/api/v2/payment/merchant/createorder', $payload);

            $data = $response['data'] ?? [];

            $order->update([
                'palmpay_order_no' => $data['orderNo'] ?? null,
                'checkout_url'     => $data['checkoutUrl'] ?? null,
                'order_status'     => $data['orderStatus'] ?? CardCheckoutOrder::STATUS_UNPAID,
                'palmpay_response' => $data,
            ]);

            return [
                'success'      => true,
                'order_id'     => $merchantOrderId,
                'checkout_url' => $data['checkoutUrl'] ?? null,
                'order_no'     => $data['orderNo'] ?? null,
                'order_status' => $data['orderStatus'] ?? 0,
                'currency'     => $data['currency'] ?? 'NGN',
                'amount'       => $data['orderAmount'] ?? $payload['amount'],
            ];

        } catch (\Exception $e) {
            $order->update(['order_status' => CardCheckoutOrder::STATUS_FAIL, 'error_msg' => $e->getMessage()]);
            Log::error('CardCheckout createOrder failed', ['error' => $e->getMessage(), 'company_id' => $companyId]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Query order status from PalmPay
     */
    public function queryOrder(string $merchantOrderId): array
    {
        try {
            $response = $this->client->post('/api/v2/payment/merchant/order/queryStatus', [
                'orderId' => $merchantOrderId,
            ]);

            $data = $response['data'] ?? [];

            // Sync local record
            CardCheckoutOrder::where('merchant_order_id', $merchantOrderId)
                ->update(['order_status' => $data['orderStatus'] ?? CardCheckoutOrder::STATUS_UNPAID]);

            return ['success' => true, 'data' => $data];

        } catch (\Exception $e) {
            Log::error('CardCheckout queryOrder failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Initiate a refund for a completed order
     */
    public function refund(int $companyId, string $originOrderId, int $amount, array $options = []): array
    {
        $refundOrderId = 'REF' . strtoupper(Str::random(16));
        $refundOrderId = substr($refundOrderId, 0, 32);

        $payload = [
            'appId'         => config('services.palmpay.app_id'),
            'orderId'       => $refundOrderId,
            'originOrderId' => $originOrderId,
            'amount'        => $amount,
            'notifyUrl'     => config('app.url') . '/api/webhooks/palmpay/card-refund',
            'remark'        => $options['remark'] ?? 'Refund',
        ];

        try {
            $response = $this->client->post('/api/v2/payment/merchant/refund', $payload);
            return ['success' => true, 'data' => $response['data'] ?? []];
        } catch (\Exception $e) {
            Log::error('CardCheckout refund failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle incoming payment notification from PalmPay
     */
    public function handlePaymentNotification(array $payload, PalmPaySignature $signer): bool
    {
        $sign = $payload['sign'] ?? '';
        $data = array_filter($payload, fn($k) => $k !== 'sign', ARRAY_FILTER_USE_KEY);

        if (!$signer->verifyWebhookSignature($data, $sign)) {
            Log::warning('CardCheckout: invalid webhook signature', $payload);
            return false;
        }

        $order = CardCheckoutOrder::where('merchant_order_id', $payload['orderId'] ?? '')
            ->orWhere('palmpay_order_no', $payload['orderNo'] ?? '')
            ->first();

        if ($order) {
            $order->update([
                'order_status'  => $payload['orderStatus'] ?? $order->order_status,
                'palmpay_order_no' => $payload['orderNo'] ?? $order->palmpay_order_no,
                'completed_at'  => isset($payload['completeTime'])
                    ? \Carbon\Carbon::createFromTimestampMs($payload['completeTime'])
                    : $order->completed_at,
            ]);
        }

        return true;
    }
}
