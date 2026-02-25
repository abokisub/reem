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
            $filter = $request->input('filter', 'yesterday'); // yesterday, today, or all_pending
            
            // Calculate date range based on filter
            $now = Carbon::now('Africa/Lagos');
            
            if ($filter === 'yesterday') {
                // Yesterday: transactions created yesterday
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
            } elseif ($filter === 'today') {
                // Today: transactions created today
                $startDate = $now->copy()->startOfDay();
                $endDate = $now;
            } elseif ($filter === 'all_pending') {
                // All Pending: ALL pending settlements regardless of date
                $startDate = Carbon::parse('2020-01-01');
                $endDate = $now->copy()->addYears(10); // Far future to include everything
            } else {
                // Default to yesterday
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
            }
            
            // Query settlement_queue table directly for pending settlements
            $query = DB::table('settlement_queue')
                ->join('transactions', 'settlement_queue.transaction_id', '=', 'transactions.id')
                ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
                ->leftJoin('virtual_accounts', 'transactions.virtual_account_id', '=', 'virtual_accounts.id')
                ->where('settlement_queue.status', 'pending');
            
            // Apply date filter based on transaction creation date
            if ($filter !== 'all_pending') {
                $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
            }
            
            $allSettlements = $query->select(
                    'settlement_queue.id as queue_id',
                    'settlement_queue.status as settlement_status',
                    'settlement_queue.scheduled_settlement_date',
                    'transactions.id',
                    'transactions.reference',
                    'transactions.amount',
                    'transactions.fee',
                    'transactions.net_amount',
                    'transactions.created_at',
                    'transactions.company_id',
                    'companies.name as company_name',
                    'companies.email as company_email',
                    'virtual_accounts.palmpay_account_number as va_account_number',
                    'virtual_accounts.palmpay_account_name as va_account_name'
                )
                ->orderBy('settlement_queue.scheduled_settlement_date', 'asc')
                ->orderBy('transactions.created_at', 'desc')
                ->get();
            
            // Calculate totals
            $totalTransactions = $allSettlements->count();
            $totalGrossAmount = $allSettlements->sum('amount');
            $totalFees = $allSettlements->sum('fee');
            $totalNetAmount = $allSettlements->sum('net_amount');
            
            // Group by company for summary
            $companySummary = $allSettlements->groupBy('company_id')->map(function ($settlements, $companyId) {
                $first = $settlements->first();
                
                return [
                    'company_id' => $companyId,
                    'company_name' => $first->company_name,
                    'company_email' => $first->company_email,
                    'transaction_count' => $settlements->count(),
                    'pending_count' => $settlements->count(), // All are pending
                    'settled_count' => 0,
                    'total_gross' => $settlements->sum('amount'),
                    'total_fees' => $settlements->sum('fee'),
                    'total_net' => $settlements->sum('net_amount'),
                    'pending_net' => $settlements->sum('net_amount'),
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
                    'pending_transactions' => $totalTransactions,
                    'settled_transactions' => 0,
                    'total_gross_amount' => $totalGrossAmount,
                    'total_fees' => $totalFees,
                    'total_net_amount' => $totalNetAmount,
                    'pending_gross_amount' => $totalGrossAmount,
                    'pending_net_amount' => $totalNetAmount,
                ],
                'company_summary' => $companySummary,
                'transactions' => $allSettlements,
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
            'filter' => 'required|in:yesterday,today,all_pending',
        ]);
        
        DB::beginTransaction();
        
        try {
            $filter = $request->input('filter');
            
            // Calculate date range based on filter
            $now = Carbon::now('Africa/Lagos');
            
            if ($filter === 'yesterday') {
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
            } elseif ($filter === 'today') {
                $startDate = $now->copy()->startOfDay();
                $endDate = $now;
            } elseif ($filter === 'all_pending') {
                // All Pending: ALL pending settlements regardless of date
                $startDate = Carbon::parse('2020-01-01');
                $endDate = $now->copy()->addYears(10); // Far future
            } else {
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
            }
            
            // Get pending settlements from settlement_queue
            $query = DB::table('settlement_queue')
                ->join('transactions', 'settlement_queue.transaction_id', '=', 'transactions.id')
                ->where('settlement_queue.status', 'pending');
            
            // Apply date filter based on transaction creation date
            if ($filter !== 'all_pending') {
                $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
            }
            
            $pendingSettlements = $query->select(
                    'settlement_queue.id as queue_id',
                    'settlement_queue.company_id',
                    'settlement_queue.transaction_id',
                    'settlement_queue.amount as settlement_amount',
                    'transactions.id',
                    'transactions.reference',
                    'transactions.amount',
                    'transactions.fee',
                    'transactions.net_amount',
                    'transactions.created_at'
                )
                ->get();
            
            if ($pendingSettlements->isEmpty()) {
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
            $byCompany = $pendingSettlements->groupBy('company_id');
            
            foreach ($byCompany as $companyId => $settlements) {
                try {
                    // Calculate total net amount for this company
                    $companyNetAmount = $settlements->sum('net_amount');
                    
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
                    $transactionIds = $settlements->pluck('transaction_id')->toArray();
                    
                    DB::table('transactions')
                        ->whereIn('id', $transactionIds)
                        ->update([
                            'settlement_status' => 'settled',
                            'updated_at' => now()
                        ]);
                    
                    // Update settlement queue status to completed
                    $queueIds = $settlements->pluck('queue_id')->toArray();
                    DB::table('settlement_queue')
                        ->whereIn('id', $queueIds)
                        ->update([
                            'status' => 'completed',
                            'actual_settlement_date' => now(),
                            'updated_at' => now()
                        ]);
                    
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
