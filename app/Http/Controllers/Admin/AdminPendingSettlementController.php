<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminPendingSettlementController extends Controller
{
    /**
     * Get pending settlements with date filter
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingSettlements(Request $request)
    {
        try {
            $filter = $request->input('filter', 'yesterday'); // yesterday or today
            
            // Calculate date range based on filter
            $now = Carbon::now('Africa/Lagos');
            
            if ($filter === 'yesterday') {
                // Yesterday: from yesterday 00:00 to yesterday 23:59
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
            } else {
                // Today: from today 00:00 to now
                $startDate = $now->copy()->startOfDay();
                $endDate = $now;
            }
            
            // Get all VA deposits for the period (regardless of settlement status)
            // This allows admin to see what will be settled and force early settlement
            $allTransactions = DB::table('transactions')
                ->join('companies', 'transactions.company_id', '=', 'companies.id')
                ->leftJoin('virtual_accounts', 'transactions.virtual_account_id', '=', 'virtual_accounts.id')
                ->where('transactions.transaction_type', 'va_deposit')
                ->where('transactions.status', 'success')
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->select(
                    'transactions.id',
                    'transactions.reference',
                    'transactions.amount',
                    'transactions.fee',
                    'transactions.net_amount',
                    'transactions.created_at',
                    'transactions.company_id',
                    'transactions.settlement_status',
                    'companies.name as company_name',
                    'companies.email as company_email',
                    'virtual_accounts.palmpay_account_number as va_account_number',
                    'virtual_accounts.palmpay_account_name as va_account_name'
                )
                ->orderBy('transactions.created_at', 'desc')
                ->get();
            
            // Convert settlement_status NULL to 'unsettled' for consistency
            $allTransactions = $allTransactions->map(function($tx) {
                if (empty($tx->settlement_status)) {
                    $tx->settlement_status = 'unsettled';
                }
                return $tx;
            });
            
            // Separate into settled and unsettled
            $pendingSettlements = $allTransactions->where('settlement_status', 'unsettled');
            $settledTransactions = $allTransactions->where('settlement_status', 'settled');
            
            // Calculate totals (only for unsettled to show what needs processing)
            $totalTransactions = $allTransactions->count();
            $totalPending = $pendingSettlements->count();
            $totalSettled = $settledTransactions->count();
            $totalGrossAmount = $allTransactions->sum('amount');
            $totalFees = $allTransactions->sum('fee');
            $totalNetAmount = $allTransactions->sum('net_amount');
            $pendingGrossAmount = $pendingSettlements->sum('amount');
            $pendingNetAmount = $pendingSettlements->sum('net_amount');
            
            // Group by company for summary (all transactions)
            $companySummary = $allTransactions->groupBy('company_id')->map(function ($transactions, $companyId) {
                $first = $transactions->first();
                $unsettled = $transactions->where('settlement_status', 'unsettled');
                $settled = $transactions->where('settlement_status', 'settled');
                
                return [
                    'company_id' => $companyId,
                    'company_name' => $first->company_name,
                    'company_email' => $first->company_email,
                    'transaction_count' => $transactions->count(),
                    'pending_count' => $unsettled->count(),
                    'settled_count' => $settled->count(),
                    'total_gross' => $transactions->sum('amount'),
                    'total_fees' => $transactions->sum('fee'),
                    'total_net' => $transactions->sum('net_amount'),
                    'pending_net' => $unsettled->sum('net_amount'),
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'filter' => $filter,
                'date_range' => [
                    'start' => $startDate->toDateTimeString(),
                    'end' => $endDate->toDateTimeString(),
                ],
                'summary' => [
                    'total_transactions' => $totalTransactions,
                    'pending_transactions' => $totalPending,
                    'settled_transactions' => $totalSettled,
                    'total_gross_amount' => $totalGrossAmount,
                    'total_fees' => $totalFees,
                    'total_net_amount' => $totalNetAmount,
                    'pending_gross_amount' => $pendingGrossAmount,
                    'pending_net_amount' => $pendingNetAmount,
                ],
                'company_summary' => $companySummary,
                'transactions' => $allTransactions,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching pending settlements: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching pending settlements',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process pending settlements manually
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processSettlements(Request $request)
    {
        $request->validate([
            'filter' => 'required|in:yesterday,today',
        ]);
        
        DB::beginTransaction();
        
        try {
            $filter = $request->input('filter');
            
            // Calculate date range based on filter
            $now = Carbon::now('Africa/Lagos');
            
            if ($filter === 'yesterday') {
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
            } else {
                $startDate = $now->copy()->startOfDay();
                $endDate = $now;
            }
            
            // Get pending settlements
            $pendingTransactions = DB::table('transactions')
                ->where('transaction_type', 'va_deposit')
                ->where('status', 'success')
                ->where('settlement_status', 'unsettled')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            if ($pendingTransactions->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No pending settlements found for the selected period'
                ], 404);
            }
            
            $processedCount = 0;
            $totalAmount = 0;
            $errors = [];
            
            // Group by company and process
            $byCompany = $pendingTransactions->groupBy('company_id');
            
            foreach ($byCompany as $companyId => $transactions) {
                try {
                    // Calculate total net amount for this company
                    $companyNetAmount = $transactions->sum('net_amount');
                    
                    // Get company wallet (not just company record)
                    $wallet = DB::table('company_wallets')
                        ->where('company_id', $companyId)
                        ->where('currency', 'NGN')
                        ->first();
                    
                    if (!$wallet) {
                        $errors[] = "Wallet not found for company ID {$companyId}";
                        continue;
                    }
                    
                    // Update company wallet balance
                    $newBalance = $wallet->balance + $companyNetAmount;
                    
                    DB::table('company_wallets')
                        ->where('company_id', $companyId)
                        ->where('currency', 'NGN')
                        ->update([
                            'balance' => $newBalance,
                            'updated_at' => now()
                        ]);
                    
                    // Mark all transactions as settled
                    $transactionIds = $transactions->pluck('id')->toArray();
                    
                    DB::table('transactions')
                        ->whereIn('id', $transactionIds)
                        ->update([
                            'settlement_status' => 'settled',
                            'updated_at' => now()
                        ]);
                    
                    // Remove from settlement queue
                    DB::table('settlement_queue')
                        ->whereIn('transaction_id', $transactionIds)
                        ->delete();
                    
                    $processedCount += count($transactionIds);
                    $totalAmount += $companyNetAmount;
                    
                    // Get company name for logging
                    $company = DB::table('companies')->where('id', $companyId)->first();
                    
                    Log::info("Manual settlement processed for company {$companyId}", [
                        'company_name' => $company->name ?? 'Unknown',
                        'transaction_count' => count($transactionIds),
                        'net_amount' => $companyNetAmount,
                        'old_balance' => $wallet->balance,
                        'new_balance' => $newBalance,
                        'admin_user' => auth()->user()->email ?? 'unknown'
                    ]);
                    
                } catch (\Exception $e) {
                    $errors[] = "Error processing company {$companyId}: " . $e->getMessage();
                    Log::error("Error in manual settlement for company {$companyId}: " . $e->getMessage());
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Settlements processed successfully',
                'processed_count' => $processedCount,
                'total_amount' => $totalAmount,
                'companies_affected' => $byCompany->count(),
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing manual settlements: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing settlements',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
