<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Transaction Controller
 * 
 * Provides admin access to ALL transaction types including internal ledger entries.
 * This controller shows all 7 transaction types without filtering.
 * 
 * Requirements: 3.1, 3.2, 3.3, 3.4, 4.8, 6.2, 6.3
 */
class AdminTransactionController extends Controller
{
    /**
     * Display a listing of all transactions with comprehensive filtering
     * 
     * Shows ALL 7 transaction types:
     * - va_deposit (customer-facing)
     * - api_transfer (customer-facing)
     * - company_withdrawal (customer-facing)
     * - refund (customer-facing)
     * - fee_charge (internal)
     * - kyc_charge (internal)
     * - manual_adjustment (internal)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query();
        
        // Filter by company_id (optional for admin)
        if ($request->has('company_id') && $request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        
        // Filter by transaction_type
        if ($request->has('transaction_type') && $request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }
        
        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by session_id (exact match)
        if ($request->has('session_id') && $request->session_id) {
            $query->where('session_id', $request->session_id);
        }
        
        // Filter by transaction_ref (exact match)
        if ($request->has('transaction_ref') && $request->transaction_ref) {
            $query->where('transaction_ref', $request->transaction_ref);
        }
        
        // Filter by provider_reference (exact match)
        if ($request->has('provider_reference') && $request->provider_reference) {
            $query->where('provider_reference', $request->provider_reference);
        }
        
        // Date range filtering
        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }
        
        // Eager load relationships to avoid N+1 queries
        $query->with(['company', 'customer']);
        
        // Order by created_at DESC (most recent first)
        $query->orderBy('created_at', 'desc');
        
        // Paginate results (100 per page default)
        $perPage = $request->input('per_page', 100);
        $transactions = $query->paginate($perPage);
        
        // Format response to ensure no N/A values
        $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_ref' => $transaction->transaction_ref ?? '',
                'session_id' => $transaction->session_id ?? '',
                'transaction_type' => $transaction->transaction_type ?? '',
                'status' => $transaction->status ?? '',
                'settlement_status' => $transaction->settlement_status ?? '',
                'amount' => number_format($transaction->amount, 2, '.', ''),
                'fee' => number_format($transaction->fee ?? 0, 2, '.', ''),
                'net_amount' => number_format($transaction->net_amount ?? 0, 2, '.', ''),
                'currency' => $transaction->currency ?? 'NGN',
                'provider_reference' => $transaction->provider_reference ?? '',
                'company_id' => $transaction->company_id,
                'company_name' => $transaction->company->name ?? '',
                'customer_id' => $transaction->company_user_id,
                'customer_name' => $transaction->customer ? 
                    ($transaction->customer->first_name . ' ' . $transaction->customer->last_name) : '',
                'description' => $transaction->description ?? '',
                'recipient' => $transaction->recipient_account_number ? [
                    'account_number' => $transaction->recipient_account_number,
                    'account_name' => $transaction->recipient_account_name ?? '',
                    'bank_code' => $transaction->recipient_bank_code ?? '',
                    'bank_name' => $transaction->recipient_bank_name ?? '',
                ] : null,
                'beneficiary' => $this->formatBeneficiary($transaction),
                'created_at' => $transaction->created_at?->toIso8601String(),
                'updated_at' => $transaction->updated_at?->toIso8601String(),
                'processed_at' => $transaction->processed_at?->toIso8601String(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedTransactions,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }
    
    /**
     * Display a single transaction by ID or transaction_ref
     * 
     * @param Request $request
     * @param string $identifier
     * @return JsonResponse
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        // Try to find by transaction_ref first, then by ID
        $transaction = Transaction::where('transaction_ref', $identifier)
            ->orWhere('id', $identifier)
            ->with(['company', 'customer', 'virtualAccount'])
            ->first();
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }
        
        // Format response to ensure no N/A values
        $formattedTransaction = [
            'id' => $transaction->id,
            'transaction_ref' => $transaction->transaction_ref ?? '',
            'session_id' => $transaction->session_id ?? '',
            'transaction_type' => $transaction->transaction_type ?? '',
            'status' => $transaction->status ?? '',
            'settlement_status' => $transaction->settlement_status ?? '',
            'amount' => number_format($transaction->amount, 2, '.', ''),
            'fee' => number_format($transaction->fee ?? 0, 2, '.', ''),
            'net_amount' => number_format($transaction->net_amount ?? 0, 2, '.', ''),
            'currency' => $transaction->currency ?? 'NGN',
            'provider_reference' => $transaction->provider_reference ?? '',
            'provider' => $transaction->provider ?? '',
            'company_id' => $transaction->company_id,
            'company_name' => $transaction->company->name ?? '',
            'customer_id' => $transaction->company_user_id,
            'customer_name' => $transaction->customer ? 
                ($transaction->customer->first_name . ' ' . $transaction->customer->last_name) : '',
            'virtual_account_id' => $transaction->virtual_account_id,
            'virtual_account_number' => $transaction->virtualAccount->account_number ?? '',
            'description' => $transaction->description ?? '',
            'recipient' => $transaction->recipient_account_number ? [
                'account_number' => $transaction->recipient_account_number,
                'account_name' => $transaction->recipient_account_name ?? '',
                'bank_code' => $transaction->recipient_bank_code ?? '',
                'bank_name' => $transaction->recipient_bank_name ?? '',
            ] : null,
            'metadata' => $transaction->metadata ?? [],
            'error_message' => $transaction->error_message ?? '',
            'balance_before' => $transaction->balance_before ? 
                number_format($transaction->balance_before, 2, '.', '') : '',
            'balance_after' => $transaction->balance_after ? 
                number_format($transaction->balance_after, 2, '.', '') : '',
            'created_at' => $transaction->created_at?->toIso8601String(),
            'updated_at' => $transaction->updated_at?->toIso8601String(),
            'processed_at' => $transaction->processed_at?->toIso8601String(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $formattedTransaction,
        ]);
    }

    /**
     * Format beneficiary display based on transaction type
     */
    protected function formatBeneficiary($transaction): ?string
    {
        // For transfers, show recipient account name
        if ($transaction->recipient_account_name) {
            return $transaction->recipient_account_name;
        }

        // For KYC transactions, show identifier from metadata
        if ($transaction->transaction_type === 'kyc_charge' && $transaction->metadata) {
            $metadata = is_string($transaction->metadata) ? json_decode($transaction->metadata, true) : $transaction->metadata;
            
            if (isset($metadata['identifier']) && isset($metadata['identifier_type'])) {
                return $metadata['identifier_type'] . ': ' . $metadata['identifier'];
            }
        }

        // For virtual account deposits, show customer name
        if ($transaction->transaction_type === 'va_deposit' && $transaction->customer) {
            return $transaction->customer->first_name . ' ' . $transaction->customer->last_name;
        }

        return null;
    }
}
