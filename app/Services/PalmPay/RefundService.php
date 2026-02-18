<?php

namespace App\Services\PalmPay;

use App\Models\Refund;
use App\Services\PalmPay\PalmPayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PalmPay Refund Service
 * 
 * Handles refund operations for PalmPay pay-in transactions
 */
class RefundService
{
    private PalmPayClient $client;

    public function __construct()
    {
        $this->client = new PalmPayClient();
    }

    /**
     * Initiate a refund for a pay-in order
     * 
     * @param int $companyId
     * @param array $refundData
     * @return Refund
     */
    public function initiateRefund(int $companyId, array $refundData): Refund
    {
        try {
            DB::beginTransaction();

            // Generate unique refund ID
            $refundId = 'ref_' . uniqid() . rand(1000, 9999);

            // Prepare request data for PalmPay
            $requestData = [
                'orderNo' => $refundData['palmpay_order_no'],
                'refundAmount' => $refundData['amount'],
                'refundReason' => $refundData['reason'] ?? 'Customer request',
                'merchantRefundNo' => $refundId,
            ];

            Log::info('Initiating PalmPay Refund', [
                'company_id' => $companyId,
                'refund_id' => $refundId,
                'data' => $requestData
            ]);

            // Call PalmPay API
            // Path: /api/v2/virtual/account/refund/apply
            $response = $this->client->post('/api/v2/virtual/account/refund/apply', $requestData);

            // Extract refund details from response
            $palmpayRefundNo = $response['data']['refundNo'] ?? null;
            $status = $response['data']['status'] ?? 'pending';

            // Create refund record
            $refund = Refund::create([
                'company_id' => $companyId,
                'refund_id' => $refundId,
                'transaction_id' => $refundData['transaction_id'] ?? null,
                'palmpay_refund_no' => $palmpayRefundNo,
                'palmpay_order_no' => $refundData['palmpay_order_no'],
                'amount' => $refundData['amount'],
                'currency' => $refundData['currency'] ?? 'NGN',
                'reason' => $refundData['reason'] ?? 'Customer request',
                'status' => $this->mapPalmPayStatus($status),
                'metadata' => $refundData['metadata'] ?? null,
                'initiated_at' => now(),
            ]);

            DB::commit();

            Log::info('Refund Initiated Successfully', [
                'refund_id' => $refundId,
                'palmpay_refund_no' => $palmpayRefundNo
            ]);

            return $refund;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to Initiate Refund', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Query refund status from PalmPay
     * 
     * @param string $refundId Internal refund ID or PalmPay refund number
     * @return array
     */
    public function queryRefundStatus(string $refundId): array
    {
        try {
            $requestData = [
                'refundNo' => $refundId,
            ];

            Log::info('Querying PalmPay Refund Status', ['refund_id' => $refundId]);

            // Path: /api/v2/virtual/account/refund/query
            $response = $this->client->post('/api/v2/virtual/account/refund/query', $requestData);

            // Update local refund record if found
            $refund = Refund::where('palmpay_refund_no', $refundId)
                ->orWhere('refund_id', $refundId)
                ->first();

            if ($refund && isset($response['data']['status'])) {
                $status = $this->mapPalmPayStatus($response['data']['status']);
                $refund->update([
                    'status' => $status,
                    'completed_at' => in_array($status, ['completed', 'failed']) ? now() : null,
                ]);
            }

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Query Refund Status', [
                'refund_id' => $refundId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get refund by ID
     * 
     * @param int $companyId
     * @param string $refundId
     * @return Refund|null
     */
    public function getRefund(int $companyId, string $refundId): ?Refund
    {
        return Refund::where('company_id', $companyId)
            ->where('refund_id', $refundId)
            ->first();
    }

    /**
     * Map PalmPay status to internal status
     * 
     * @param string $palmpayStatus
     * @return string
     */
    private function mapPalmPayStatus(string $palmpayStatus): string
    {
        return match (strtolower($palmpayStatus)) {
            'success', 'completed' => 'completed',
            'failed', 'fail' => 'failed',
            'processing', 'in_progress' => 'processing',
            'cancelled', 'canceled' => 'cancelled',
            default => 'pending',
        };
    }
}
