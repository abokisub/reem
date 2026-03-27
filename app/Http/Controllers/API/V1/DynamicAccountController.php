<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CardCheckoutOrder;
use App\Services\PalmPay\DynamicAccountService;
use App\Services\PalmPay\PalmPaySignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DynamicAccountController extends Controller
{
    public function __construct(
        private DynamicAccountService $service,
        private PalmPaySignature $signer
    ) {}

    /**
     * POST /api/v1/checkout/bank-transfer/create
     * Create a dynamic virtual account for a specific order amount
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'amount'            => 'required|integer|min:10000', // min 100 NGN = 10000 kobo
            'currency'          => 'sometimes|string|in:NGN',
            'title'             => 'sometimes|string|max:100',
            'description'       => 'sometimes|string|max:200',
            'callback_url'      => 'required|url',
            'reference'         => 'sometimes|string|max:100',
            'user_id'           => 'sometimes|string|max:50',
            'user_mobile'       => 'sometimes|string|max:15',
            'remark'            => 'sometimes|string|max:200',
            'goods_details'     => 'sometimes|array',
            'order_expire_time' => 'sometimes|integer|min:1800|max:86400',
        ]);

        $company = $request->attributes->get('company');
        $result  = $this->service->createOrder($company->id, $validated);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json([
            'status'            => 'success',
            'order_id'          => $result['order_id'],
            'order_no'          => $result['order_no'],
            'amount'            => $result['amount'],
            'currency'          => $result['currency'],
            // What the customer pays to:
            'account_number'    => $result['account_number'],
            'account_name'      => $result['account_name'],
            'bank_name'         => $result['bank_name'],
            'expires_in_seconds'=> $result['expires_in_seconds'],
            'checkout_url'      => $result['checkout_url'], // fallback H5 if needed
        ]);
    }

    /**
     * POST /api/v1/checkout/bank-transfer/query
     * Query order payment status
     */
    public function query(Request $request)
    {
        $request->validate(['order_id' => 'required|string|max:32']);

        $order = CardCheckoutOrder::where('merchant_order_id', $request->order_id)
            ->where('company_id', $request->attributes->get('company')->id)
            ->firstOrFail();

        $result = $this->service->queryOrder($order->merchant_order_id);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json(['status' => 'success', 'data' => $result['data']]);
    }

    /**
     * POST /api/v1/checkout/bank-transfer/check-account
     * Check if the temporary account is still valid/active
     */
    public function checkAccount(Request $request)
    {
        $request->validate([
            'account_no'   => 'required|string',
            'account_type' => 'required|string',
            'order_no'     => 'required|string',
        ]);

        $result = $this->service->checkAccountValidity(
            $request->account_no,
            $request->account_type,
            $request->order_no
        );

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * POST /api/webhooks/palmpay/bank-transfer
     * PalmPay payment notification (no auth — verified by signature)
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        Log::info('DynamicAccount webhook received', $payload);

        $ok = $this->service->handleNotification($payload, $this->signer);

        return $ok ? response('success', 200) : response('fail', 400);
    }
}
