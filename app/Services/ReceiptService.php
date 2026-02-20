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
        return [
            'receipt_number' => $this->generateReceiptNumber($transaction),
            'transaction_id' => $transaction->transaction_id,
            'amount' => number_format($transaction->amount, 2),
            'currency' => 'NGN',
            'date' => date('d/m/Y H:i:s', strtotime($transaction->created_at)) . ' WAT',
            'status' => ucfirst($transaction->status),
            'type' => ucfirst($transaction->type ?? 'N/A'),
            'customer' => [
                'name' => $transaction->recipient_account_name ?? 'N/A',
                'account' => $transaction->recipient_account_number ?? 'N/A',
                'bank' => $transaction->recipient_bank_name ?? 'N/A',
            ],
            'company' => [
                'name' => $transaction->company->name ?? 'N/A',
                'email' => $transaction->company->email ?? 'N/A',
            ],
            'fee' => number_format($transaction->fee ?? 0, 2),
            'net_amount' => number_format($transaction->net_amount ?? $transaction->amount, 2),
            'description' => $transaction->description ?? 'N/A',
            'generated_at' => date('d/m/Y H:i:s') . ' WAT',
        ];
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
