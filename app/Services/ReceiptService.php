<?php

namespace App\Services;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceiptService
{
    public function generateReceipt(Transaction $transaction): Response
    {
        $this->validateTransaction($transaction);
        $data = $this->getReceiptData($transaction);
        
        $pdf = Pdf::loadView('receipts.transaction', $data);
        
        return $pdf->download("receipt-{$transaction->transaction_id}-" . date('Ymd') . ".pdf");
    }
    
    protected function getReceiptData(Transaction $transaction): array
    {
        // Get metadata
        $metadata = is_array($transaction->metadata) ? $transaction->metadata : json_decode($transaction->metadata, true) ?? [];
        
        // Determine if this is a credit (deposit) or debit (transfer/withdrawal) transaction
        $isCredit = $transaction->type === 'credit' || $transaction->transaction_type === 'va_deposit';
        
        // Get customer/sender/recipient details based on transaction type
        if ($isCredit) {
            // For deposits: show sender information
            $customerName = $metadata['sender_name'] ?? $metadata['sender_account_name'] ?? '';
            $customerAccount = $metadata['sender_account'] ?? '';
            $customerBank = $metadata['sender_bank'] ?? $metadata['sender_bank_name'] ?? '';
        } else {
            // For transfers/withdrawals: show recipient information
            $customerName = $transaction->recipient_account_name ?? '';
            $customerAccount = $transaction->recipient_account_number ?? '';
            $customerBank = $transaction->recipient_bank_name ?? '';
        }
        
        // Get company information
        $company = $transaction->company;
        $companyName = $company->company_name ?? $company->name ?? '';
        $companyEmail = $company->email ?? '';
        $companyUsername = $company->username ?? '';
        
        return [
            'receipt_number' => $this->generateReceiptNumber($transaction),
            'transaction_id' => $transaction->transaction_id,
            'transaction_ref' => $transaction->transaction_ref ?? $transaction->reference,
            'amount' => number_format($transaction->amount, 2),
            'currency' => 'NGN',
            'date' => date('d/m/Y H:i:s', strtotime($transaction->created_at)) . ' WAT',
            'status' => ucfirst($transaction->status),
            'type' => $this->getTransactionTypeLabel($transaction),
            'customer' => [
                'name' => $customerName ?: '-',
                'account' => $customerAccount ?: '-',
                'bank' => $customerBank ?: '-',
            ],
            'company' => [
                'name' => $companyName ?: '-',
                'email' => $companyEmail ?: '-',
                'username' => $companyUsername ?: '-',
            ],
            'fee' => number_format($transaction->fee ?? 0, 2),
            'net_amount' => number_format($transaction->net_amount ?? ($transaction->amount - ($transaction->fee ?? 0)), 2),
            'description' => $transaction->description ?: '-',
            'generated_at' => date('d/m/Y H:i:s') . ' WAT',
            'is_credit' => $isCredit,
        ];
    }
    
    protected function getTransactionTypeLabel(Transaction $transaction): string
    {
        $typeLabels = [
            'va_deposit' => 'Virtual Account Deposit',
            'api_transfer' => 'API Transfer',
            'company_withdrawal' => 'Company Withdrawal',
            'refund' => 'Refund',
            'fee_charge' => 'Fee Charge',
            'kyc_charge' => 'KYC Charge',
            'manual_adjustment' => 'Manual Adjustment',
        ];
        
        if ($transaction->transaction_type && isset($typeLabels[$transaction->transaction_type])) {
            return $typeLabels[$transaction->transaction_type];
        }
        
        // Fallback to legacy type
        return ucfirst($transaction->type ?? 'Transaction');
    }
    
    protected function generateReceiptNumber(Transaction $transaction): string
    {
        return 'RCP-' . date('Ymd', strtotime($transaction->created_at)) . '-' . strtoupper($transaction->transaction_id);
    }
    
    protected function validateTransaction(Transaction $transaction): bool
    {
        if (!$transaction->transaction_id || !$transaction->amount || !$transaction->created_at) {
            throw new \Exception('Transaction missing required fields');
        }
        
        return true;
    }
}
