<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use App\Services\LedgerService;
use App\Services\PalmPay\PalmPayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MerchantApiController extends Controller
{
    protected $palmPay;

    public function __construct(PalmPayClient $palmPay)
    {
        $this->palmPay = $palmPay;
    }

    /**
     * Standard response helper
     */
    protected function respond($status, $message, $data = [], $code = 200, $requestId = null)
    {
        return response()->json([
            'status' => $status,
            'request_id' => $requestId ?: request()->attributes->get('request_id'),
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Create a new customer (company_user)
     * Basic info only - KYC documents are optional for upgrade later
     */
    public function createCustomer(Request $request)
    {
        $company = $request->attributes->get('company');

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone_number' => 'required|string',
            'external_customer_id' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, $validator->errors()->first(), [], 422);
        }

        $customer = CompanyUser::create([
            'company_id' => $company->id,
            'external_customer_id' => $request->external_customer_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone_number,
            'kyc_status' => 'unverified',
            'is_test' => $request->attributes->get('is_test', false),
        ]);

        return $this->respond(true, 'Customer created successfully', [
            'customer_id' => $customer->uuid,
            'email' => $customer->email,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'phone' => $customer->phone,
            'kyc_status' => $customer->kyc_status,
            'created_at' => $customer->created_at->toIso8601String()
        ], 201);
    }

    /**
     * Update existing customer
     */
    public function updateCustomer(Request $request, $customerId)
    {
        $company = $request->attributes->get('company');
        $isTest = $request->attributes->get('is_test', false);
        $customer = CompanyUser::where('uuid', $customerId)
            ->where('company_id', $company->id)
            ->where('is_test', $isTest)
            ->first();

        if (!$customer)
            return $this->respond(false, 'Customer not found', [], 404);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'email' => 'sometimes|email',
            'phone_number' => 'sometimes|string',
            'address' => 'sometimes|string',
            'state' => 'sometimes|string',
            'city' => 'sometimes|string',
            'postal_code' => 'sometimes|string',
            'date_of_birth' => 'sometimes|date_format:Y-m-d',
            'id_type' => 'sometimes|string|in:bvn,nin',
            'id_number' => 'sometimes|string',
            'id_card' => 'sometimes|file|mimes:jpg,png,pdf|max:5120',
            'utility_bill' => 'sometimes|file|mimes:jpg,png,pdf|max:5120',
        ]);

        if ($validator->fails())
            return $this->respond(false, $validator->errors()->first(), [], 422);

        $data = $request->except(['id_card', 'utility_bill']);
        if ($request->hasFile('id_card')) {
            $data['id_card_path'] = $request->file('id_card')->store('kyc/id_cards', 'public');
            $data['kyc_status'] = 'pending';
        }
        if ($request->hasFile('utility_bill')) {
            $data['utility_bill_path'] = $request->file('utility_bill')->store('kyc/utility_bills', 'public');
            $data['kyc_status'] = 'pending';
        }
        if (isset($data['phone_number'])) {
            $data['phone'] = $data['phone_number'];
            unset($data['phone_number']);
        }

        $customer->update($data);
        return $this->respond(true, 'Customer updated successfully', [
            'customer_id' => $customer->uuid,
            'kyc_status' => $customer->kyc_status
        ]);
    }

    /**
     * Get Customer Profile
     */
    public function getCustomer(Request $request, $customerId)
    {
        $company = $request->attributes->get('company');
        $isTest = $request->attributes->get('is_test', false);
        $customer = CompanyUser::where('uuid', $customerId)
            ->where('company_id', $company->id)
            ->where('is_test', $isTest)
            ->first();

        if (!$customer)
            return $this->respond(false, 'Customer not found', [], 404);

        return $this->respond(true, 'Customer details retrieved', $customer);
    }

    /**
     * Create a Virtual Account (Enterprise Version)
     */
    public function createVirtualAccount(Request $request)
    {
        $company = $request->attributes->get('company');

        $validator = Validator::make($request->all(), [
            // Option 1: Existing Customer
            'customer_id' => 'sometimes|string|exists:company_users,uuid',

            // Option 2: Shared Fields
            'bank_codes' => 'sometimes|array',
            'account_type' => 'required|string|in:static,dynamic',
            'amount' => 'required_if:account_type,dynamic|numeric',
            'external_reference' => 'sometimes|string',

            // New Customer Details (If customer_id is missing)
            'first_name' => 'required_without:customer_id|string',
            'last_name' => 'required_without:customer_id|string',
            'email' => 'required_without:customer_id|email',
            'phone_number' => 'required_without:customer_id|string',
            'id_type' => 'sometimes|string|in:bvn,nin',
            'id_number' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, $validator->errors()->first(), [], 422);
        }

        // 1. Resolve or Create Customer (Identity Guarding)
        $customer = null;
        if ($request->customer_id) {
            $customer = CompanyUser::where('uuid', $request->customer_id)->first();
        }

        // If not found by ID or no ID provided, check by email OR phone (Deduplication)
        if (!$customer) {
            $customerQuery = CompanyUser::where('company_id', $company->id)
                ->where(function ($query) use ($request) {
                    $query->where('email', $request->email);
                    if ($request->phone_number) {
                        $query->orWhere('phone', $request->phone_number);
                    }
                });

            $customer = $customerQuery->first();

            if ($customer) {
                \Log::info('Resolved existing customer by identity attributes', [
                    'email' => $request->email,
                    'phone' => $request->phone_number,
                    'existing_uuid' => $customer->uuid
                ]);
            } else {
                // Create new customer if truly unique
                $customer = CompanyUser::create([
                    'company_id' => $company->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone_number,
                    'id_type' => $request->id_type,
                    'id_number' => $request->id_number,
                    'is_test' => $request->attributes->get('is_test', false),
                ]);
            }
        }

        // 2. Prevent duplicates for dynamic accounts (Idempotency)
        if ($request->account_type === 'dynamic' && $request->external_reference) {
            $existing = VirtualAccount::where('provider_reference', $request->external_reference)
                ->where('company_id', $company->id)
                ->first();
            if ($existing)
                return $this->respond(true, 'Retrieved existing dynamic account', ['virtual_accounts' => [$this->formatVa($existing)]]);
        }

        // Initialize Service
        $virtualAccountService = new \App\Services\PalmPay\VirtualAccountService();

        // 3. Provision Accounts (Get from configuration)
        $defaultBankCode = config('services.palmpay.bank_code', '100033');
        $bankCodes = $request->bank_codes ?? [$defaultBankCode];
        $createdAccounts = [];
        $errors = [];

        foreach ($bankCodes as $bankCode) {
            try {
                // Get bank name from banks table
                $bank = \App\Models\Bank::where('code', $bankCode)->first();
                $bankName = $bank ? $bank->name : config('services.palmpay.bank_name', 'PalmPay');

                if ($request->attributes->get('is_test')) {
                    // MOCK Sandbox Provisioning
                    $va = VirtualAccount::create([
                        'company_id' => $company->id,
                        'company_user_id' => $customer->id,
                        'bank_code' => $bankCode,
                        'bank_name' => $bankName,
                        'account_number' => '99' . rand(10000000, 99999999),
                        'account_name' => "TS_" . strtoupper($customer->last_name) . " " . strtoupper($customer->first_name),
                        'account_type' => $request->account_type,
                        'amount' => $request->amount,
                        'provider' => 'sandbox',
                        'status' => 'active',
                        'is_test' => true,
                    ]);
                } else {
                    $customerData = [
                        'name' => "{$customer->first_name} {$customer->last_name}",
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'identity_type' => $request->id_type === 'rc' ? 'company' : 'personal',
                        'license_number' => $request->id_number,
                        'account_type' => $request->account_type,
                        'amount' => $request->amount,
                        'external_reference' => $request->external_reference,
                    ];

                    $va = $virtualAccountService->createVirtualAccount(
                        $company->id,
                        $customer->uuid,
                        $customerData,
                        $bankCode,
                        $customer->id // company_user_id
                    );
                }

                $createdAccounts[] = $this->formatVa($va);

            } catch (\Exception $e) {
                Log::error("Failed to create $bankName account", [
                    'error' => $e->getMessage(),
                    'customer' => $customer->id
                ]);
                $errors[] = "$bankName: " . $e->getMessage();
            }
        }

        if (empty($createdAccounts)) {
            return $this->respond(false, 'Provider Error: ' . implode(', ', $errors), [], 500);
        }

        return $this->respond(true, 'Virtual accounts created successfully', [
            'customer' => [
                'customer_id' => $customer->uuid,
                'name' => "{$customer->first_name} {$customer->last_name}",
                'email' => $customer->email,
            ],
            'virtual_accounts' => $createdAccounts
        ], 201);
    }

    /**
     * Update VA Status (PATCH)
     */
    public function updateVirtualAccount(Request $request, $vaId)
    {
        $company = $request->attributes->get('company');
        $isTest = $request->attributes->get('is_test', false);
        $va = VirtualAccount::where('uuid', $vaId)
            ->where('company_id', $company->id)
            ->where('is_test', $isTest)
            ->first();

        if (!$va)
            return $this->respond(false, 'Virtual account not found', [], 404);

        if ($va->account_type === 'dynamic') {
            return $this->respond(false, 'Cannot update status of dynamic accounts', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,deactivated',
            'reason' => 'sometimes|string',
        ]);

        if ($validator->fails())
            return $this->respond(false, $validator->errors()->first(), [], 422);

        $va->update(['status' => $request->status]);

        // Audit Trail (Log)
        Log::info("VA Status Updated", ['va' => $va->uuid, 'status' => $request->status, 'reason' => $request->reason, 'user' => $company->name]);

        return $this->respond(true, 'Virtual account status updated', [
            'virtual_account_id' => $va->uuid,
            'status' => $va->status
        ]);
    }

    protected function formatVa($va)
    {
        return [
            'bank_code' => $va->bank_code,
            'bank_name' => $va->bank_name,
            'account_number' => $va->account_number,
            'account_name' => $va->account_name,
            'account_type' => $va->account_type,
            'virtual_account_id' => $va->uuid,
        ];
    }

    public function getTransactions(Request $request)
    {
        $company = $request->attributes->get('company');
        $isTest = $request->attributes->get('is_test', false);

        $transactions = Transaction::where('company_id', $company->id)
            ->where('is_test', $isTest)
            ->latest()
            ->paginate(20);

        return $this->respond(true, 'Transactions retrieved', $transactions);
    }

    /**
     * GET /api/v1/banks
     * Get list of all supported banks
     */
    public function getBanks(Request $request)
    {
        try {
            // Check if slug column exists (for backward compatibility)
            $columns = ['id', 'name', 'code'];
            $hasSlug = Schema::hasColumn('banks', 'slug');
            if ($hasSlug) {
                $columns[] = 'slug';
            }

            // Get banks
            $banks = DB::table('banks')
                ->where('active', 1)
                ->orderBy('name')
                ->get($columns);

            // Format response - return banks array directly in data
            $formattedBanks = $banks->map(function($bank) use ($hasSlug) {
                $data = [
                    'name' => $bank->name,
                    'code' => $bank->code,
                    'bank_code' => $bank->code, // Alias for compatibility
                ];
                
                // Add slug if available
                if ($hasSlug && isset($bank->slug)) {
                    $data['slug'] = $bank->slug;
                }
                
                return $data;
            })->values()->toArray();

            // Return banks array directly in data field (not nested)
            return response()->json([
                'success' => true,
                'data' => $formattedBanks
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get Banks Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve banks',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/balance
     * Get company wallet balance
     */
    public function getBalance(Request $request, LedgerService $ledger)
    {
        try {
            $company = $request->attributes->get('company');
            
            // Get wallet account
            $wallet = $ledger->getOrCreateAccount($company->name . ' Wallet', 'company_wallet', $company->id);

            return $this->respond(true, 'Balance retrieved successfully', [
                'balance' => $wallet->balance,
                'currency' => 'NGN',
                'formatted_balance' => '₦' . number_format($wallet->balance, 2)
            ]);
        } catch (\Exception $e) {
            Log::error('Get Balance Error', ['error' => $e->getMessage()]);
            return $this->respond(false, 'Failed to retrieve balance', [], 500);
        }
    }

    public function initiateTransfer(Request $request, LedgerService $ledger)
    {
        $company = $request->attributes->get('company');
        $isTest = $request->attributes->get('is_test', false);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'bank_code' => 'required|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
        ]);

        if ($validator->fails())
            return $this->respond(false, $validator->errors()->first(), [], 422);

        // Get payout charges from settings
        $settings = DB::table('settings')->first();
        $chargeType = $settings->payout_palmpay_charge_type ?? 'FLAT';
        $chargeValue = $settings->payout_palmpay_charge_value ?? 15;
        $chargeCap = $settings->payout_palmpay_charge_cap ?? null;
        
        // Calculate payout fee
        $payoutFee = 0;
        if ($chargeType === 'PERCENT') {
            $payoutFee = ($request->amount * $chargeValue) / 100;
            if ($chargeCap && $payoutFee > $chargeCap) {
                $payoutFee = $chargeCap;
            }
        } else {
            $payoutFee = $chargeValue;
        }
        
        $totalDeduction = $request->amount + $payoutFee;

        // Check balance
        $wallet = $ledger->getOrCreateAccount($company->name . ' Wallet', 'company_wallet', $company->id);

        if ($wallet->balance < $totalDeduction && !$isTest)
            return $this->respond(false, 'Insufficient balance. Required: ' . $totalDeduction . ' (Amount: ' . $request->amount . ' + Fee: ' . $payoutFee . ')', [], 400);

        $internalRef = ($isTest ? 'TS_' : 'PWV_OUT_') . strtoupper(Str::random(10));

        try {
            if (!$isTest) {
                // Deduct total amount (amount + fee) from wallet
                $settlementClearing = $ledger->getOrCreateAccount('Settlement Clearing', 'settlement');
                $ledger->recordEntry($internalRef, $wallet->id, $settlementClearing->id, $totalDeduction, "Payout Initialized (Amount: {$request->amount} + Fee: {$payoutFee})");

                // Record fee as revenue
                if ($payoutFee > 0) {
                    $revenueAccount = $ledger->getOrCreateAccount('Revenue', 'revenue');
                    $ledger->recordEntry($internalRef . '-FEE', $wallet->id, $revenueAccount->id, $payoutFee, "Payout Fee");
                }

                $response = $this->palmPay->post('/transfer/v1/initiate', [
                    'amount' => $request->amount,
                    'bankCode' => $request->bank_code,
                    'accountNumber' => $request->account_number,
                    'accountName' => $request->account_name,
                    'reference' => $internalRef,
                ]);
                $providerRef = $response['data']['orderNo'] ?? null;
                
                // Extract PalmPay provider fee
                $palmpayFee = 0;
                $palmpayVat = 0;
                if (isset($response['data']['fee'])) {
                    $palmpayFee = ($response['data']['fee']['fee'] ?? 0) / 100;
                    $palmpayVat = ($response['data']['fee']['vat'] ?? 0) / 100;
                }
                $totalProviderFee = $palmpayFee + $palmpayVat;
                
                Log::info('Company Payout - PalmPay Provider Fee', [
                    'company_id' => $company->id,
                    'reference' => $internalRef,
                    'our_fee_charged' => $payoutFee,
                    'palmpay_fee' => $palmpayFee,
                    'palmpay_vat' => $palmpayVat,
                    'total_provider_fee' => $totalProviderFee,
                    'net_profit' => $payoutFee - $totalProviderFee
                ]);
            } else {
                // Mock Success for Sandbox
                $providerRef = 'MOCK_TS_' . Str::random(12);
                $totalProviderFee = 0;
            }

            Transaction::create([
                'transaction_id' => Transaction::generateTransactionId(),
                'company_id' => $company->id,
                'reference' => $internalRef,
                'amount' => $request->amount,
                'fee' => $payoutFee,
                'provider_fee' => $totalProviderFee ?? 0,
                'total_amount' => $totalDeduction,
                'type' => 'debit',
                'category' => 'transfer_out',
                'status' => 'success',
                'palmpay_reference' => $providerRef,
                'provider_reference' => $providerRef,
                'recipient_account_number' => $request->account_number,
                'recipient_account_name' => $request->account_name,
                'recipient_bank_code' => $request->bank_code,
                'is_test' => $isTest,
                'metadata' => !$isTest ? [
                    'payout_fee_charged' => $payoutFee,
                    'palmpay_provider_fee' => $palmpayFee ?? 0,
                    'palmpay_vat' => $palmpayVat ?? 0,
                    'total_provider_fee' => $totalProviderFee ?? 0,
                    'net_profit' => $payoutFee - ($totalProviderFee ?? 0)
                ] : null,
            ]);

            return $this->respond(true, 'Transfer successful' . ($isTest ? ' (SANDBOX)' : ''), [
                'reference' => $internalRef,
                'status' => 'successful',
                'amount' => $request->amount,
                'fee' => $payoutFee,
                'total_deducted' => $totalDeduction
            ]);
        } catch (\Exception $e) {
            return $this->respond(false, 'Transfer failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * DELETE /api/v1/customers/{customerId}
     * Delete a customer
     */
    public function deleteCustomer(Request $request, $customerId)
    {
        $company = $request->attributes->get('company');

        $customer = CompanyUser::where('uuid', $customerId)
            ->where('company_id', $company->id)
            ->first();

        if (!$customer) {
            return $this->respond(false, 'Customer not found', [], 404);
        }

        // Check if customer has active virtual accounts
        $activeVAs = VirtualAccount::where('company_user_id', $customer->id)
            ->where('status', 'active')
            ->count();

        if ($activeVAs > 0) {
            return $this->respond(false, 'Cannot delete customer with active virtual accounts. Please deactivate all virtual accounts first.', [], 400);
        }

        // Soft delete customer
        $customer->delete();

        return $this->respond(true, 'Customer deleted successfully', [
            'customer_id' => $customerId,
            'deleted_at' => now()->toIso8601String()
        ]);
    }

    /**
     * GET /api/v1/virtual-accounts
     * List all virtual accounts for the company
     */
    public function listVirtualAccounts(Request $request)
    {
        $company = $request->attributes->get('company');

        $query = VirtualAccount::where('company_id', $company->id)
            ->with('companyUser');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $customer = CompanyUser::where('uuid', $request->customer_id)
                ->where('company_id', $company->id)
                ->first();
            
            if ($customer) {
                $query->where('company_user_id', $customer->id);
            }
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $virtualAccounts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Format the virtual accounts - FIX: Use collection properly
        $formattedVAs = [];
        foreach ($virtualAccounts as $va) {
            $formattedVAs[] = $this->formatVa($va);
        }

        return $this->respond(true, 'Virtual accounts retrieved successfully', [
            'current_page' => $virtualAccounts->currentPage(),
            'data' => $formattedVAs,
            'total' => $virtualAccounts->total(),
            'per_page' => $virtualAccounts->perPage(),
            'last_page' => $virtualAccounts->lastPage()
        ]);
    }

    /**
     * GET /api/v1/virtual-accounts/{vaId}
     * Get a single virtual account
     */
    public function getVirtualAccount(Request $request, $vaId)
    {
        $company = $request->attributes->get('company');

        // Try to find by UUID first, then by account_number
        $virtualAccount = VirtualAccount::where('company_id', $company->id)
            ->where(function($query) use ($vaId) {
                $query->where('uuid', $vaId)
                      ->orWhere('account_number', $vaId);
            })
            ->with('companyUser')
            ->first();

        if (!$virtualAccount) {
            return $this->respond(false, 'Virtual account not found', [], 404);
        }

        return $this->respond(true, 'Virtual account retrieved successfully', $this->formatVa($virtualAccount));
    }

    /**
     * DELETE /api/v1/virtual-accounts/{vaId}
     * Delete (deactivate) a virtual account
     */
    public function deleteVirtualAccount(Request $request, $vaId)
    {
        try {
            $company = $request->attributes->get('company');

            // Try to find by UUID first, then by account_number
            $virtualAccount = VirtualAccount::where('company_id', $company->id)
                ->where(function($query) use ($vaId) {
                    $query->where('uuid', $vaId)
                          ->orWhere('account_number', $vaId);
                })
                ->first();

            if (!$virtualAccount) {
                return $this->respond(false, 'Virtual account not found', [], 404);
            }

            // Only static accounts can be deactivated
            if ($virtualAccount->account_type === 'dynamic') {
                return $this->respond(false, 'Dynamic virtual accounts cannot be deleted', [], 400);
            }

            // Update status to inactive (common enum value)
            $virtualAccount->status = 'inactive';
            $virtualAccount->save();

            return $this->respond(true, 'Virtual account deleted successfully', [
                'virtual_account_id' => $virtualAccount->uuid,
                'account_number' => $virtualAccount->account_number,
                'status' => 'inactive',
                'deleted_at' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Virtual Account Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'va_id' => $vaId
            ]);
            return $this->respond(false, 'Failed to delete virtual account: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Verify BVN
     * POST /api/v1/kyc/verify-bvn
     * 
     * CHARGING LOGIC:
     * - Companies in onboarding (pending/under_review/partial/unverified) → FREE
     * - Verified companies using API to verify customers → CHARGED
     */
    public function verifyBVN(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bvn' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $companyId = $request->input('company_id'); // Get from request merge
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->verifyBVN($request->bvn, $companyId);

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'verified' => true,
                'bvn' => $request->bvn,
                'data' => $result['data'],
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
                'transaction_reference' => $result['transaction_reference'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('BVN Verification API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->respond(false, 'BVN verification failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Verify NIN
     * POST /api/v1/kyc/verify-nin
     */
    public function verifyNIN(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nin' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $companyId = $request->input('company_id'); // Get from request merge
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->verifyNIN($request->nin, $companyId);

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'verified' => true,
                'nin' => $request->nin,
                'data' => $result['data'],
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
                'transaction_reference' => $result['transaction_reference'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('NIN Verification API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->respond(false, 'NIN verification failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Verify Bank Account (Name Inquiry for Transfers)
     * POST /api/v1/banks/verify
     * 
     * This endpoint verifies bank account details before transfers.
     * Returns account name for confirmation.
     */
    public function verifyBankAccountForTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|size:10',
            'bank_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
                'status' => 422
            ], 422);
        }

        try {
            // Verify bank code exists
            $bank = DB::table('banks')->where('code', $request->bank_code)->first();
            if (!$bank) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid bank code',
                    'error_code' => 'INVALID_BANK_CODE',
                    'status' => 400
                ], 400);
            }

            // Use PalmPay Account Verification Service
            $verificationService = new \App\Services\PalmPay\AccountVerificationService();
            $result = $verificationService->verifyAccount($request->account_number, $request->bank_code);

            if (!$result['success']) {
                // Parse error message to provide proper error codes
                $errorMessage = $result['message'] ?? 'Account verification failed';
                $errorCode = 'VERIFICATION_FAILED';
                
                // Detect specific error types
                if (stripos($errorMessage, 'not found') !== false || stripos($errorMessage, 'invalid account') !== false) {
                    $errorCode = 'ACCOUNT_NOT_FOUND';
                    $errorMessage = 'Account not found';
                } elseif (stripos($errorMessage, 'invalid bank') !== false) {
                    $errorCode = 'INVALID_BANK_CODE';
                    $errorMessage = 'Invalid bank code';
                } elseif (stripos($errorMessage, 'unauthorized') !== false || stripos($errorMessage, 'credentials') !== false) {
                    $errorCode = 'INVALID_CREDENTIALS';
                    $errorMessage = 'Invalid API credentials';
                } elseif (stripos($errorMessage, 'timeout') !== false || stripos($errorMessage, 'unavailable') !== false) {
                    $errorCode = 'SERVICE_UNAVAILABLE';
                    $errorMessage = 'Service temporarily unavailable';
                }

                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'status' => 400
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'account_name' => $result['account_name'],
                    'account_number' => $result['account_number'],
                    'bank_code' => $result['bank_code'],
                    'bank_name' => $bank->name
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Bank Account Verification Error', [
                'account_number' => $request->account_number,
                'bank_code' => $request->bank_code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Account verification failed',
                'error_code' => 'INTERNAL_ERROR',
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Verify Bank Account (KYC Verification)
     * POST /api/v1/kyc/verify-bank-account
     */
    public function verifyBankAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|size:10',
            'bank_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $companyId = $request->attributes->get('company_id');
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->verifyBankAccount(
                $request->account_number,
                $request->bank_code,
                $companyId
            );

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'verified' => true,
                'account_number' => $request->account_number,
                'bank_code' => $request->bank_code,
                'data' => $result['data'],
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
                'transaction_reference' => $result['transaction_reference'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Bank Account Verification API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->respond(false, 'Bank account verification failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Face Recognition - Compare two face images
     * POST /api/v1/kyc/face-compare
     */
    public function compareFaces(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_image' => 'required|string',
            'target_image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $companyId = $request->attributes->get('company_id');
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->compareFaces(
                $request->source_image,
                $request->target_image,
                $companyId
            );

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'similarity' => $result['data']['similarity'] ?? 0,
                'match' => ($result['data']['similarity'] ?? 0) > 60,
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
                'transaction_reference' => $result['transaction_reference'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Face Recognition API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->respond(false, 'Face recognition failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Initialize Liveness Detection
     * POST /api/v1/kyc/liveness/initialize
     */
    public function initializeLiveness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'biz_id' => 'required|string',
            'redirect_url' => 'required|url',
            'user_id' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $companyId = $request->attributes->get('company_id');
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->initializeLiveness(
                $request->biz_id,
                $request->redirect_url,
                $request->user_id ?? null,
                $companyId
            );

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'jump_url' => $result['data']['jumpUrl'] ?? null,
                'transaction_id' => $result['data']['transactionId'] ?? null,
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Liveness Initialize API Error', [
                'error' => $e->getMessage()
            ]);
            return $this->respond(false, 'Liveness initialization failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Query Liveness Detection Result
     * POST /api/v1/kyc/liveness/query
     */
    public function queryLivenessResult(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $easeIdClient = app(\App\Services\KYC\EaseIdClient::class);
            $result = $easeIdClient->queryLivenessResult($request->transaction_id);

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, 'Liveness result retrieved', [
                'photo_base64' => $result['data']['photoBase64'] ?? null,
                'biz_id' => $result['data']['bizId'] ?? null,
                'user_id' => $result['data']['userId'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Liveness Query API Error', [
                'error' => $e->getMessage()
            ]);
            return $this->respond(false, 'Liveness query failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Check Blacklist
     * POST /api/v1/kyc/blacklist-check
     */
    public function checkBlacklist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'sometimes|string',
            'bvn' => 'sometimes|string',
            'nin' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        // At least one parameter required
        if (!$request->phone_number && !$request->bvn && !$request->nin) {
            return $this->respond(false, 'At least one of phone_number, bvn, or nin is required', [], 422);
        }

        try {
            $companyId = $request->attributes->get('company_id');
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->checkBlacklist(
                $request->phone_number ?? null,
                $request->bvn ?? null,
                $request->nin ?? null,
                $companyId
            );

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'result' => $result['data']['result'] ?? 'no_hit',
                'hit_time' => $result['data']['hitTime'] ?? null,
                'on_blacklist' => ($result['data']['result'] ?? 'no_hit') === 'hit',
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
                'transaction_reference' => $result['transaction_reference'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Blacklist Check API Error', [
                'error' => $e->getMessage()
            ]);
            return $this->respond(false, 'Blacklist check failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get Credit Score (Nigeria)
     * POST /api/v1/kyc/credit-score
     */
    public function getCreditScore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_no' => 'required|string',
            'id_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $companyId = $request->attributes->get('company_id');
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->getCreditScore(
                $request->mobile_no,
                $request->id_number,
                $companyId
            );

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'credit_score' => $result['data']['creditScore'] ?? null,
                'credit_score_v3' => $result['data']['creditScoreV3'] ?? null,
                'version' => $result['data']['version'] ?? null,
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
                'transaction_reference' => $result['transaction_reference'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Credit Score API Error', [
                'error' => $e->getMessage()
            ]);
            return $this->respond(false, 'Credit score query failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get Loan Features
     * POST /api/v1/kyc/loan-features
     */
    public function getLoanFeatures(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string',
            'type' => 'sometimes|integer|in:1,2,3',
            'access_type' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, 'Validation failed', $validator->errors(), 422);
        }

        try {
            $companyId = $request->attributes->get('company_id');
            $kycService = app(\App\Services\KYC\KycService::class);
            
            $result = $kycService->getLoanFeatures(
                $request->value,
                $request->type ?? 1,
                $request->access_type ?? '01',
                $companyId
            );

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, $result['message'], [
                'features' => $result['data'] ?? [],
                'charged' => $result['charged'] ?? false,
                'charge_amount' => $result['charge_amount'] ?? 0,
                'transaction_reference' => $result['transaction_reference'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Loan Features API Error', [
                'error' => $e->getMessage()
            ]);
            return $this->respond(false, 'Loan features query failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get EaseID Balance
     * GET /api/v1/kyc/easeid-balance
     */
    public function getEaseIDBalance(Request $request)
    {
        try {
            $easeIdClient = app(\App\Services\KYC\EaseIdClient::class);
            $result = $easeIdClient->getBalance();

            if (!$result['success']) {
                return $this->respond(false, $result['message'], [], 400);
            }

            return $this->respond(true, 'EaseID balance retrieved', [
                'app_id' => $result['data']['appID'] ?? null,
                'currency' => $result['data']['currency'] ?? 'NGN',
                'balance_amount' => $result['data']['balanceAmount'] ?? 0,
                'query_time' => $result['data']['queryTime'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('EaseID Balance API Error', [
                'error' => $e->getMessage()
            ]);
            return $this->respond(false, 'Balance query failed: ' . $e->getMessage(), [], 500);
        }
    }
}
