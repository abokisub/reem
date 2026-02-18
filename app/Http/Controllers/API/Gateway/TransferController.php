<?php

namespace App\Http\Controllers\API\Gateway;

use App\Http\Controllers\Controller;
use App\Services\PalmPay\TransferService;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Transfer Controller
 * 
 * Handles bank transfers for gateway customers
 */
class TransferController extends Controller
{
    private TransferService $transferService;

    public function __construct()
    {
        $this->transferService = new TransferService();
    }

    /**
     * Initiate a bank transfer
     * 
     * POST /api/gateway/transfers
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:100|max:5000000',
                'accountNumber' => 'required|string|size:10',
                'bankCode' => 'required|string|max:10',
                'accountName' => 'nullable|string|max:255',
                'narration' => 'nullable|string|max:255',
                'reference' => 'nullable|string|max:100',
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

            // Check for duplicate reference
            if ($request->has('reference')) {
                $existing = Transaction::where('company_id', $companyId)
                    ->where('external_reference', $request->input('reference'))
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Duplicate reference',
                        'data' => [
                            'transactionId' => $existing->transaction_id,
                            'status' => $existing->status,
                        ]
                    ], 409);
                }
            }

            // Initiate transfer
            $transaction = $this->transferService->initiateTransfer($companyId, [
                'amount' => $request->input('amount'),
                'account_number' => $request->input('accountNumber'),
                'bank_code' => $request->input('bankCode'),
                'account_name' => $request->input('accountName'),
                'bank_name' => $request->input('bankName'),
                'narration' => $request->input('narration', 'Bank Transfer'),
                'reference' => $request->input('reference'),
                'metadata' => $request->input('metadata'),
            ]);

            Log::info('Transfer Initiated via API', [
                'company_id' => $companyId,
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->amount
            ]);

            \App\Services\AuditLogger::log('transfer.initiate', $transaction, null, [
                'amount' => $transaction->amount,
                'account' => $transaction->recipient_account_number,
                'bank' => $transaction->recipient_bank_code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfer initiated successfully',
                'data' => [
                    'transactionId' => $transaction->transaction_id,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                    'fee' => $transaction->fee,
                    'totalAmount' => $transaction->total_amount,
                    'status' => $transaction->status,
                    'recipientAccount' => $transaction->recipient_account_number,
                    'recipientName' => $transaction->recipient_account_name,
                    'createdAt' => $transaction->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Transfer Initiation Failed', [
                'company_id' => $request->get('company_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transfer status
     * 
     * GET /api/gateway/transfers/{transactionId}
     * 
     * @param Request $request
     * @param string $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request, string $transactionId)
    {
        try {
            $companyId = $request->get('company_id');

            $transaction = Transaction::where('company_id', $companyId)
                ->where('transaction_id', $transactionId)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'transactionId' => $transaction->transaction_id,
                    'reference' => $transaction->reference,
                    'externalReference' => $transaction->external_reference,
                    'amount' => $transaction->amount,
                    'fee' => $transaction->fee,
                    'totalAmount' => $transaction->total_amount,
                    'status' => $transaction->status,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'recipientAccount' => $transaction->recipient_account_number,
                    'recipientName' => $transaction->recipient_account_name,
                    'recipientBank' => $transaction->recipient_bank_name,
                    'description' => $transaction->description,
                    'errorMessage' => $transaction->error_message,
                    'createdAt' => $transaction->created_at->toIso8601String(),
                    'processedAt' => $transaction->processed_at?->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Fetch Transaction Status', [
                'company_id' => $request->get('company_id'),
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transaction status'
            ], 500);
        }
    }

    /**
     * Get wallet balance
     * 
     * GET /api/gateway/balance
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(Request $request)
    {
        try {
            $company = $request->attributes->get('company');
            $wallet = $company->wallet;

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $wallet->balance,
                    'ledgerBalance' => $wallet->ledger_balance,
                    'pendingBalance' => $wallet->pending_balance,
                    'availableBalance' => $wallet->balance - $wallet->pending_balance,
                    'currency' => $wallet->currency,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Fetch Balance', [
                'company_id' => $request->get('company_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance'
            ], 500);
        }
    }
}