<?php

namespace App\Http\Controllers\API\Gateway;

use App\Http\Controllers\Controller;
use App\Services\PalmPay\VirtualAccountService;
use App\Services\PalmPay\TransferService;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Transaction Controller
 * 
 * Handles transaction verification for gateway customers
 */
class TransactionController extends Controller
{
    private VirtualAccountService $virtualAccountService;
    private TransferService $transferService;

    public function __construct()
    {
        $this->virtualAccountService = new VirtualAccountService();
        $this->transferService = new TransferService();
    }

    /**
     * Verify a transaction (Collection or Payout)
     * 
     * GET /api/gateway/transactions/verify/{reference}
     * 
     * @param Request $request
     * @param string $reference External or Internal reference
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request, string $reference)
    {
        try {
            $companyId = $request->get('company_id');

            // Find transaction in local database first
            $transaction = Transaction::where('company_id', $companyId)
                ->where(function ($query) use ($reference) {
                    $query->where('transaction_id', $reference)
                        ->orWhere('reference', $reference)
                        ->orWhere('external_reference', $reference);
                })
                ->first();

            if (!$transaction) {
                // If not found in DB, try querying PalmPay directly as a collection reference
                $status = $this->virtualAccountService->queryCollectionStatus($reference);

                if ($status['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Transaction found on provider',
                        'data' => [
                            'reference' => $status['reference'],
                            'status' => $status['status'],
                            'amount' => $status['amount'],
                            'type' => 'COLLECTION',
                            'isUnknownToLocal' => true,
                        ]
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // If it's a Payout, query status from PalmPay to be sure
            if ($transaction->type === 'PAYOUT' && $transaction->status !== 'success' && $transaction->status !== 'failed') {
                try {
                    $liveStatus = $this->transferService->queryTransferStatus($transaction->reference);
                    // Update local status if changed
                    if (isset($liveStatus['status']) && $liveStatus['status'] !== $transaction->status) {
                        $transaction->update(['status' => $liveStatus['status']]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to query live status for payout', ['ref' => $transaction->reference]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'transactionId' => $transaction->transaction_id,
                    'reference' => $transaction->reference,
                    'externalReference' => $transaction->external_reference,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'fee' => $transaction->fee,
                    'totalAmount' => $transaction->total_amount,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'createdAt' => $transaction->created_at->toIso8601String(),
                    'processedAt' => $transaction->processed_at?->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Transaction Verification Failed', [
                'company_id' => $request->get('company_id'),
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify transaction'
            ], 500);
        }
    }
}
