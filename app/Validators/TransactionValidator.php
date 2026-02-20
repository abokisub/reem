<?php

namespace App\Validators;

use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Support\Str;

class TransactionValidator
{
    private const REQUIRED_FIELDS = [
        'company_id', 'transaction_type', 'amount', 'status'
    ];
    
    private const TRANSACTION_TYPES = [
        'va_deposit',
        'company_withdrawal',
        'api_transfer',
        'kyc_charge',
        'refund',
        'fee_charge',
        'manual_adjustment'
    ];
    
    private const STATUSES = [
        'pending',
        'processing',
        'successful',
        'failed',
        'reversed'
    ];
    
    private const SETTLEMENT_STATUSES = [
        'settled',
        'unsettled',
        'not_applicable'
    ];
    
    /**
     * Validate transaction data
     * 
     * @param array $data Transaction data to validate
     * @return ValidationResult
     */
    public function validate(array $data): ValidationResult
    {
        $errors = [];
        
        // Check required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                $errors[$field] = "Field {$field} is required";
            }
        }
        
        // Validate amount
        if (isset($data['amount'])) {
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                $errors['amount'] = 'Amount must be greater than zero';
            }
        }
        
        // Validate transaction_type
        if (isset($data['transaction_type'])) {
            if (!in_array($data['transaction_type'], self::TRANSACTION_TYPES)) {
                $errors['transaction_type'] = 'Invalid transaction type. Must be one of: ' . implode(', ', self::TRANSACTION_TYPES);
            }
        }
        
        // Validate status
        if (isset($data['status'])) {
            if (!in_array($data['status'], self::STATUSES)) {
                $errors['status'] = 'Invalid status value. Must be one of: ' . implode(', ', self::STATUSES);
            }
        }
        
        // Validate settlement_status if provided
        if (isset($data['settlement_status'])) {
            if (!in_array($data['settlement_status'], self::SETTLEMENT_STATUSES)) {
                $errors['settlement_status'] = 'Invalid settlement status. Must be one of: ' . implode(', ', self::SETTLEMENT_STATUSES);
            }
        }
        
        // Validate foreign keys
        if (isset($data['company_id'])) {
            if (!Company::find($data['company_id'])) {
                $errors['company_id'] = 'Company does not exist';
            }
        }
        
        if (isset($data['customer_id']) && $data['customer_id'] !== null) {
            if (!CompanyUser::find($data['customer_id'])) {
                $errors['customer_id'] = 'Customer does not exist';
            }
        }
        
        // Validate fee and calculate net_amount
        $fee = $data['fee'] ?? 0;
        $amount = $data['amount'] ?? 0;
        
        if ($fee < 0) {
            $errors['fee'] = 'Fee cannot be negative';
        }
        
        $netAmount = $amount - $fee;
        if ($netAmount < 0) {
            $errors['net_amount'] = 'Net amount cannot be negative (fee exceeds amount)';
        }
        
        return new ValidationResult(empty($errors), $errors);
    }
    
    /**
     * Generate default values for transaction fields
     * 
     * @param array $data Transaction data
     * @return array Transaction data with defaults applied
     */
    public function generateDefaults(array $data): array
    {
        // Generate session_id if not provided
        if (!isset($data['session_id'])) {
            $data['session_id'] = 'sess_' . Str::uuid();
        }
        
        // Generate transaction_ref if not provided
        if (!isset($data['transaction_ref'])) {
            $data['transaction_ref'] = $this->generateTransactionRef();
        }
        
        // Set default fee
        if (!isset($data['fee'])) {
            $data['fee'] = 0.00;
        }
        
        // Calculate net_amount
        $data['net_amount'] = $data['amount'] - $data['fee'];
        
        // Set default settlement_status based on transaction_type and status
        if (!isset($data['settlement_status'])) {
            $data['settlement_status'] = $this->determineSettlementStatus(
                $data['transaction_type'] ?? '',
                $data['status'] ?? 'pending'
            );
        }
        
        return $data;
    }
    
    /**
     * Generate a unique transaction reference
     * 
     * @return string Transaction reference in format TXN + 12 uppercase alphanumeric characters
     */
    private function generateTransactionRef(): string
    {
        return 'TXN' . strtoupper(Str::random(12));
    }
    
    /**
     * Determine settlement status based on transaction type and status
     * 
     * @param string $type Transaction type
     * @param string $status Transaction status
     * @return string Settlement status
     */
    private function determineSettlementStatus(string $type, string $status): string
    {
        // Internal accounting entries don't require settlement
        if (in_array($type, ['fee_charge', 'kyc_charge', 'manual_adjustment'])) {
            return 'not_applicable';
        }
        
        // Failed/reversed transactions don't settle
        if (in_array($status, ['failed', 'reversed'])) {
            return 'not_applicable';
        }
        
        // Successful transactions are settled
        if ($status === 'successful') {
            return 'settled';
        }
        
        // Default to unsettled for pending/processing
        return 'unsettled';
    }
}
