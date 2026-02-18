<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\CompanyWallet;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Initiate refund for a transaction
     */
    public function initiateRefund(Request $request, $id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
            
            // Verify transaction belongs to user's company
            $user = $request->user();
            if ($transaction->company_id !== $user->active_company_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            // Check if transaction is eligible for refund
            if ($transaction->status !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only successful transactions can be refunded'
                ], 400);
            }
            
            // Check if already refunded
            if ($transaction->category === 'refund' || $transaction->is_refunded) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction already refunded'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Create refund transaction
            $refundTransaction = Transaction::create([
                'transaction_id' => 'RFD_' . strtoupper(uniqid()),
                'company_id' => $transaction->company_id,
                'type' => 'debit',
                'category' => 'refund',
                'amount' => $transaction->amount,
                'fee' => 0,
                'net_amount' => $transaction->amount,
                'total_amount' => $transaction->amount,
                'currency' => 'NGN',
                'status' => 'success', // Changed from 'pending' to 'success'
                'reference' => 'REFUND_' . $transaction->reference,
                'description' => 'Refund for transaction ' . $transaction->transaction_id,
                'metadata' => json_encode([
                    'original_transaction_id' => $transaction->id,
                    'original_reference' => $transaction->reference,
                    'refund_initiated_by' => $user->email,
                    'refund_initiated_at' => now()->toDateTimeString()
                ]),
                'processed_at' => now()
            ]);
            
            // Mark original transaction as refunded
            $transaction->update([
                'is_refunded' => true,
                'refund_transaction_id' => $refundTransaction->id
            ]);
            
            // Update wallet balance
            $wallet = CompanyWallet::where('company_id', $transaction->company_id)->first();
            if ($wallet) {
                $wallet->debit($transaction->amount);
            }
            
            DB::commit();
            
            // Log refund initiation
            Log::info('Refund initiated', [
                'original_transaction' => $transaction->transaction_id,
                'refund_transaction' => $refundTransaction->transaction_id,
                'amount' => $transaction->amount,
                'initiated_by' => $user->email
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Refund initiated successfully',
                'data' => [
                    'refund_transaction_id' => $refundTransaction->transaction_id,
                    'amount' => $transaction->amount,
                    'status' => 'pending'
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund initiation failed', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate refund: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Resend webhook notification for a transaction
     */
    public function resendNotification(Request $request, $id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
            
            // Verify transaction belongs to user's company
            $user = $request->user();
            if ($transaction->company_id !== $user->active_company_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            // Get company webhook URL
            $company = DB::table('companies')->where('id', $transaction->company_id)->first();
            
            if (!$company || !$company->webhook_url) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No webhook URL configured for this company'
                ], 400);
            }
            
            // Prepare webhook payload
            $payload = [
                'event' => 'transaction.success',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                    'fee' => $transaction->fee,
                    'net_amount' => $transaction->net_amount,
                    'status' => $transaction->status,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->toIso8601String(),
                    'metadata' => json_decode($transaction->metadata, true)
                ]
            ];
            
            // Send webhook
            $ch = curl_init($company->webhook_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . hash_hmac('sha256', json_encode($payload), $company->webhook_secret ?? '')
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log webhook attempt
            DB::table('webhook_logs')->insert([
                'company_id' => $transaction->company_id,
                'transaction_id' => $transaction->id,
                'event' => 'transaction.success',
                'url' => $company->webhook_url,
                'payload' => json_encode($payload),
                'response' => $response,
                'status_code' => $httpCode,
                'status' => $httpCode >= 200 && $httpCode < 300 ? 'success' : 'failed',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook notification sent successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Webhook delivery failed with status code: ' . $httpCode
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Webhook resend failed', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to resend notification: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export transactions to CSV
     */
    public function exportTransactions(Request $request)
    {
        try {
            $user = $request->user();
            
            $transactions = DB::table('transactions')
                ->leftJoin('virtual_accounts', 'transactions.virtual_account_id', '=', 'virtual_accounts.id')
                ->where('transactions.company_id', $user->active_company_id)
                ->select(
                    'transactions.transaction_id',
                    'transactions.reference',
                    'transactions.amount',
                    'transactions.fee',
                    'transactions.net_amount',
                    'transactions.status',
                    'transactions.type',
                    'transactions.category',
                    'transactions.description',
                    'transactions.created_at',
                    'virtual_accounts.account_name',
                    'virtual_accounts.account_number'
                )
                ->orderBy('transactions.id', 'desc')
                ->get();
            
            // Create CSV
            $filename = 'transactions_' . date('Y-m-d_His') . '.csv';
            $handle = fopen('php://temp', 'r+');
            
            // Add CSV headers
            fputcsv($handle, [
                'Transaction ID',
                'Reference',
                'Customer Name',
                'Amount',
                'Fee',
                'Net Amount',
                'Status',
                'Type',
                'Category',
                'Description',
                'Date'
            ]);
            
            // Add data rows
            foreach ($transactions as $transaction) {
                $metadata = json_decode($transaction->metadata ?? '{}', true);
                $customerName = $metadata['sender_name'] ?? $transaction->account_name ?? 'Unknown';
                
                fputcsv($handle, [
                    $transaction->transaction_id,
                    $transaction->reference,
                    $customerName,
                    $transaction->amount,
                    $transaction->fee,
                    $transaction->net_amount,
                    $transaction->status,
                    $transaction->type,
                    $transaction->category,
                    $transaction->description,
                    $transaction->created_at
                ]);
            }
            
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);
            
            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
