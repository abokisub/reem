<?php

namespace App\Http\Controllers\API\Gateway;

use App\Http\Controllers\Controller;
use App\Services\PalmPay\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Refund Controller
 * 
 * Handles refund operations for gateway customers
 */
class RefundController extends Controller
{
    private RefundService $refundService;

    public function __construct()
    {
        $this->refundService = new RefundService();
    }

    /**
     * Initiate a refund
     * 
     * POST /api/gateway/refunds
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'palmpayOrderNo' => 'required|string|max:100',
                'amount' => 'required|numeric|min:1',
                'reason' => 'nullable|string|max:500',
                'transactionId' => 'nullable|string|max:100',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = $request->get('company_id');

            // Initiate refund
            $refund = $this->refundService->initiateRefund($companyId, [
                'palmpay_order_no' => $request->input('palmpayOrderNo'),
                'amount' => $request->input('amount'),
                'reason' => $request->input('reason'),
                'transaction_id' => $request->input('transactionId'),
                'currency' => $request->input('currency', 'NGN'),
                'metadata' => $request->input('metadata'),
            ]);

            Log::info('Refund Initiated via API', [
                'company_id' => $companyId,
                'refund_id' => $refund->refund_id,
                'amount' => $refund->amount
            ]);

            \App\Services\AuditLogger::log('refund.initiate', $refund, null, [
                'amount' => $refund->amount,
                'order_no' => $refund->palmpay_order_no
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund initiated successfully',
                'data' => [
                    'refundId' => $refund->refund_id,
                    'palmpayRefundNo' => $refund->palmpay_refund_no,
                    'amount' => $refund->amount,
                    'status' => $refund->status,
                    'initiatedAt' => $refund->initiated_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Exception $e) {
            $message = $e->getMessage();

            Log::error('Refund Initiation Failed', [
                'company_id' => $request->get('company_id'),
                'error' => $message
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate refund',
                'error' => $message
            ], 500);
        }
    }

    /**
     * Get refund status
     * 
     * GET /api/gateway/refunds/{refundId}
     * 
     * @param Request $request
     * @param string $refundId
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request, string $refundId)
    {
        try {
            $companyId = $request->get('company_id');

            // Get local refund record
            $refund = $this->refundService->getRefund($companyId, $refundId);

            if (!$refund) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund not found'
                ], 404);
            }

            // Query latest status from PalmPay
            if ($refund->palmpay_refund_no && !in_array($refund->status, ['completed', 'failed', 'cancelled'])) {
                $this->refundService->queryRefundStatus($refund->palmpay_refund_no);
                $refund->refresh();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'refundId' => $refund->refund_id,
                    'palmpayRefundNo' => $refund->palmpay_refund_no,
                    'palmpayOrderNo' => $refund->palmpay_order_no,
                    'transactionId' => $refund->transaction_id,
                    'amount' => $refund->amount,
                    'currency' => $refund->currency,
                    'reason' => $refund->reason,
                    'status' => $refund->status,
                    'errorMessage' => $refund->error_message,
                    'initiatedAt' => $refund->initiated_at?->toIso8601String(),
                    'completedAt' => $refund->completed_at?->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Fetch Refund Status', [
                'company_id' => $request->get('company_id'),
                'refund_id' => $refundId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch refund status'
            ], 500);
        }
    }
}
