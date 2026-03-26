<?php

namespace App\Services\PalmPay;

use App\Models\CardCheckoutOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * PalmPay Pay With Bank Transfer (Dynamic Virtual Account)
 * Creates a one-time virtual account per order that expires after payment or timeout.
 */
class DynamicAccountService
{
    public function __construct(private PalmPayClient $client) {}

    /**
     * Create a dynamic virtual account order
     * Returns a temporary account number the customer pays into
     */
    public function createOrder(int $companyId, array $params): array
    {
        $merchantOrderId = substr(strtoupper(Str::random(16)) . time(), 0, 32);
        $notifyUrl       = config('app.url') . '/api/webhooks/palmpay/bank-transfer';
        $callbackUrl     = $params['callback_url'] ?? config('app.url');

        $payload = [
            'orderId'     => $merchantOrderId,
            'title'       => $params['title'] ?? 'Payment',
            'description' => $params['description'] ?? '',
            'amount'      => (int) $params['amount'],
            'currency'    => $params['currency'] ?? 'NGN',
            'notifyUrl'   => $notifyUrl,
            'callBackUrl' => $callbackUrl,
            'productType' => 'bank_transfer',
        ];

        if (!empty($params['user_id']))       $payload['userId']       = $params['user_id'];
        if (!empty($params['user_mobile']))   $payload['userMobileNo'] = $params['user_mobile'];
        if (!empty($params['remark']))        $payload['remark']       = $params['remark'];
        if (!empty($params['goods_details'])) $payload['goodsDetails'] = json_encode($params['goods_details']);
        if (!empty($params['order_expire_time'])) {
            $payload['orderExpireTime'] = (int) $params['order_expire_time'];
        }

        // Save order before calling PalmPay
        $order = CardCheckoutOrder::create([
            'company_id'        => $companyId,
            'merchant_order_id' => $merchantOrderId,
            'amount'            => $payload['amount'],
            'currency'          => $payload['currency'],
            'order_status'      => CardCheckoutOrder::STATUS_UNPAID,
            'notify_url'        => $notifyUrl,
            'callback_url'      => $callbackUrl,
            'reference'         => $params['reference'] ?? null,
            'customer_info'     => ['product_type' => 'bank_transfer'],
        ]);

        try {
            $response = $this->client->post('/api/v2/payment/merchant/createorder', $payload);
            $data     = $response['data'] ?? [];

            $order->update([
                'palmpay_order_no' => $data['orderNo'] ?? null,
                'order_status'     => $data['orderStatus'] ?? CardCheckoutOrder::STATUS_UNPAID,
                'palmpay_response' => $data,
            ]);

            return [
                'success'              => true,
                'order_id'             => $merchantOrderId,
                'order_no'             => $data['orderNo'] ?? null,
                'order_status'         => $data['orderStatus'] ?? 0,
                'amount'               => $data['orderAmount'] ?? $payload['amount'],
                'currency'             => $data['currency'] ?? 'NGN',
                // Dynamic account details — customer pays to this account
                'account_number'       => $data['payerVirtualAccNo'] ?? null,
                'account_name'         => $data['payerAccountName'] ?? null,
                'bank_name'            => $data['payerBankName'] ?? null,
                'account_type'         => $data['payerAccountType'] ?? null,
                'account_id'           => $data['payerAccountId'] ?? null,
                'checkout_url'         => $data['checkoutUrl'] ?? null,
                'expires_in_seconds'   => $params['order_expire_time'] ?? 1800,
            ];

        } catch (\Exception $e) {
            $order->update(['order_status' => CardCheckoutOrder::STATUS_FAIL, 'error_msg' => $e->getMessage()]);
            Log::error('DynamicAccount createOrder failed', ['error' => $e->getMessage(), 'company_id' => $companyId]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Query order status — also returns current account validity
     */
    public function queryOrder(string $merchantOrderId): array
    {
        try {
            $response = $this->client->post('/api/v2/payment/merchant/order/queryStatus', [
                'orderId' => $merchantOrderId,
            ]);

            $data = $response['data'] ?? [];

            CardCheckoutOrder::where('merchant_order_id', $merchantOrderId)
                ->update(['order_status' => $data['orderStatus'] ?? CardCheckoutOrder::STATUS_UNPAID]);

            return ['success' => true, 'data' => $data];

        } catch (\Exception $e) {
            Log::error('DynamicAccount queryOrder failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if the temporary account is still valid
     */
    public function checkAccountValidity(string $accountNo, string $accountType, string $orderNo): array
    {
        try {
            $response = $this->client->post('/api/v2/member/h5/general/getTemporaryAccount', [
                'accountNo'   => $accountNo,
                'accountType' => $accountType,
                'orderNo'     => $orderNo,
            ]);

            $data = $response['data'] ?? [];

            return [
                'success'       => true,
                'account_no'    => $data['accountNo'] ?? $accountNo,
                'account_state' => $data['accountState'] ?? 0, // 1=available, 0=unavailable
                'expire_time'   => $data['expireTime'] ?? null,
                'is_available'  => ($data['accountState'] ?? 0) == 1,
            ];

        } catch (\Exception $e) {
            Log::error('DynamicAccount checkValidity failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle payment notification webhook from PalmPay
     */
    public function handleNotification(array $payload, PalmPaySignature $signer): bool
    {
        $sign = $payload['sign'] ?? '';
        $data = array_filter($payload, fn($k) => $k !== 'sign', ARRAY_FILTER_USE_KEY);

        if (!$signer->verifyWebhookSignature($data, $sign)) {
            Log::warning('DynamicAccount: invalid webhook signature', $payload);
            return false;
        }

        $order = CardCheckoutOrder::where('merchant_order_id', $payload['orderId'] ?? '')
            ->orWhere('palmpay_order_no', $payload['orderNo'] ?? '')
            ->first();

        if ($order) {
            $order->update([
                'order_status'     => $payload['orderStatus'] ?? $order->order_status,
                'palmpay_order_no' => $payload['orderNo'] ?? $order->palmpay_order_no,
                'completed_at'     => isset($payload['completeTime'])
                    ? \Carbon\Carbon::createFromTimestampMs($payload['completeTime'])
                    : $order->completed_at,
            ]);
        }

        return true;
    }
}
