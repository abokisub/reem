<?php

namespace App\Services\PalmPay;

use App\Models\VirtualAccount;
use App\Services\PalmPay\PalmPayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PalmPay Virtual Account Service
 * 
 * Handles creation and management of PalmPay virtual accounts
 */
class VirtualAccountService
{
    private PalmPayClient $client;

    public function __construct(?PalmPayClient $client = null)
    {
        $this->client = $client ?? new PalmPayClient();
    }

    /**
     * Create a virtual account for a company's customer
     * 
     * @param int $companyId
     * @param string $userId Platform user ID
     * @param array $customerData
     * @param string $bankCode Bank code for the virtual account (default: 100033 - PalmPay)
     * @param int|null $companyUserId External customer ID
     * @return VirtualAccount
     */
    public function createVirtualAccount(int $companyId, string $userId, array $customerData, string $bankCode = '100033', ?int $companyUserId = null): VirtualAccount
    {
        try {
            // Deduplication Tier 1: Check for existing active virtual account by exact ID
            $existing = VirtualAccount::where('company_id', $companyId)
                ->where('bank_code', $bankCode)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('uuid', $userId);
                })
                ->first();

            // Deduplication Tier 2: Strict Identity Matching
            // Only match if customer data is EXACTLY the same (email AND phone)
            if (!$existing) {
                $email = $customerData['email'] ?? null;
                $phone = $customerData['phone'] ?? null;

                if ($email && $phone) {
                    // Strict matching: Both email AND phone must match exactly
                    $existing = VirtualAccount::where('company_id', $companyId)
                        ->where('bank_code', $bankCode)
                        ->where('status', 'active')
                        ->whereNull('deleted_at')
                        ->where('customer_email', $email)
                        ->where('customer_phone', $phone)
                        ->first();
                        
                    if ($existing) {
                        Log::info('VirtualAccount: Found existing account by exact identity match', [
                            'account_number' => $existing->account_number,
                            'customer_name' => $existing->customer_name,
                            'email' => $email,
                            'phone' => $phone,
                            'company_id' => $companyId
                        ]);
                    }
                }
            }

            if ($existing) {
                // Strict Customer Verification: Ensure this is the exact same customer
                $newCustomerName = $customerData['name'] ?? 'Customer';
                $newEmail = $customerData['email'] ?? null;
                $newPhone = $customerData['phone'] ?? null;
                
                // Security Check: Verify customer identity before any updates
                $isExactMatch = ($existing->customer_email === $newEmail) && 
                               ($existing->customer_phone === $newPhone);
                
                if (!$isExactMatch) {
                    // CRITICAL SECURITY VIOLATION: Different customer trying to access existing account
                    Log::critical('VirtualAccount: SECURITY VIOLATION - Identity mismatch detected', [
                        'account_number' => $existing->account_number,
                        'existing_customer' => [
                            'name' => $existing->customer_name,
                            'email' => $existing->customer_email,
                            'phone' => $existing->customer_phone
                        ],
                        'attempted_customer' => [
                            'name' => $newCustomerName,
                            'email' => $newEmail,
                            'phone' => $newPhone
                        ],
                        'company_id' => $companyId,
                        'user_id' => $userId,
                        'timestamp' => now()->toISOString()
                    ]);
                    
                    throw new \Exception(
                        "Security violation: Customer identity mismatch for account {$existing->account_number}. " .
                        "This indicates a critical deduplication bug that must be investigated immediately."
                    );
                }
                
                // Safe Name Update: Only if same customer with different name spelling
                if ($existing->customer_name !== $newCustomerName) {
                    Log::info('VirtualAccount: Updating customer name for verified identity', [
                        'account_number' => $existing->palmpay_account_number,
                        'old_name' => $existing->customer_name,
                        'new_name' => $newCustomerName,
                        'verified_phone' => $newPhone,
                        'verified_email' => $newEmail,
                        'company_id' => $companyId
                    ]);
                    
                    $existing->update([
                        'customer_name' => $newCustomerName,
                        'updated_at' => now()
                    ]);
                }
                
                Log::info('VirtualAccount: Returning existing account for verified customer', [
                    'account_number' => $existing->palmpay_account_number,
                    'customer_name' => $existing->customer_name,
                    'company_id' => $companyId,
                    'bank_code' => $bankCode
                ]);
                
                return $existing;
            }

            DB::beginTransaction();

            // Fetch company first (needed for KYC logic)
            $company = \App\Models\Company::find($companyId);
            if (!$company) {
                throw new \Exception("Company not found");
            }

            // Extract customer info
            $customerNameOnly = $customerData['name'] ?? 'Customer';
            
            // Extract customer KYC if provided
            $customerNin = $customerData['nin'] ?? null;
            $customerBvn = $customerData['bvn'] ?? null;
            
            // Enhanced KYC Selection with Multi-Director Backup Support + Global Fallback
            $kycResult = $this->selectOptimalKycMethodWithGlobalFallback($company, $customerBvn, $customerNin, $companyId);
            
            $licenseNumber = $kycResult['license_number'];
            $identityType = $kycResult['identity_type'];
            $kycSource = $kycResult['kyc_source'];
            $directorBvnUsed = $kycResult['director_used'] ?? null;
            $globalKycId = $kycResult['global_kyc_id'] ?? null;
            
            $email = $customerData['email'] ?? null;
            $phone = $customerData['phone'] ?? null;

            // Extract account options
            $accountType = $customerData['account_type'] ?? 'static';
            $amount = $customerData['amount'] ?? null;
            $expiresAt = $customerData['expires_at'] ?? ($accountType === 'dynamic' ? now()->addHours(1) : null);
            $externalRef = $customerData['external_reference'] ?? null;

            // Company name for branding
            $companyName = $company->name;

            // Format Name for PalmPay
            $formattedName = $companyName . '-' . $customerNameOnly;
            $formattedName = substr($formattedName, 0, 200);

            // Prepare account ID (External Reference for PalmPay)
            $accountId = $externalRef ?: 'va_' . bin2hex(random_bytes(8));
            if ($externalRef && !str_contains($accountId, $bankCode)) {
                $accountId .= '_' . $bankCode; // Suffix bank code for uniqueness if needed
            }

            // Validate license number
            if (!$licenseNumber) {
                throw new \Exception("Virtual account creation failed: No KYC available. Please configure company director BVN/NIN or provide customer BVN/NIN.");
            }

            \Log::info('Creating virtual account with KYC', [
                'company' => $company->name,
                'kyc_source' => $kycSource,
                'identity_type' => $identityType,
                'customer_provided_kyc' => !empty($customerBvn) || !empty($customerNin)
            ]);

            // CAC Prefix Enforcement for Companies:
            // Documentation requirements: "If identityType is company, licenseNumber must start with 'RC' or 'BN'"
            if ($identityType === 'company') {
                $licenseNumber = strtoupper(trim($licenseNumber));
                if (!str_starts_with($licenseNumber, 'RC') && !str_starts_with($licenseNumber, 'BN')) {
                    $licenseNumber = 'RC' . $licenseNumber;
                }
            }

            $requestData = [
                'virtualAccountName' => $formattedName,
                'identityType' => $identityType,
                'licenseNumber' => $licenseNumber,
                'customerName' => $customerNameOnly,
                'email' => $email,
                'phoneNumber' => $phone,
                'bankCode' => $bankCode,
                'accountReference' => $accountId,
            ];

            Log::info('Creating PalmPay Virtual Account', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'kyc_source' => $kycSource,
                'identity_type' => $identityType,
                'data' => $requestData
            ]);

            // Call PalmPay API with automatic retry on KYC failures + Global KYC tracking
            $response = $this->callPalmPayWithKycFallback($requestData, $company, $companyId, $globalKycId);

            // Extract account details from response
            $accountNumber = $response['data']['virtualAccountNo'] ?? null;
            $accountName = $response['data']['virtualAccountName'] ?? $customerNameOnly;
            $status = $response['data']['status'] ?? 'active';

            if (!$accountNumber) {
                throw new \Exception('PalmPay did not return virtual account number: ' . ($response['respMsg'] ?? 'Unknown error'));
            }

            // Determine Bank Name
            $bankName = match ($bankCode) {
                '100033' => 'PalmPay',
                '090743' => 'Blooms MFB',
                default => 'PalmPay'
            };

            // Create virtual account record
            $virtualAccount = VirtualAccount::create([
                'account_id' => $accountId,
                'company_id' => $companyId,
                'company_user_id' => $companyUserId,
                'user_id' => $userId,
                'bank_code' => $bankCode,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'account_type' => $accountType,
                'amount' => $amount,
                'palmpay_account_number' => $accountNumber,
                'palmpay_account_name' => $accountName,
                'palmpay_bank_name' => $bankName,
                'palmpay_status' => $status,
                'provider' => 'palmpay',
                'provider_reference' => $response['data']['orderNo'] ?? null,
                'customer_name' => $customerNameOnly,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'bvn' => $customerBvn,
                'nin' => $customerNin,
                'identity_type' => $identityType,
                'kyc_source' => $kycSource,
                'kyc_upgraded' => false,
                'director_bvn' => $directorBvnUsed,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'activated_at' => now(),
            ]);

            // Sync with User model for platform users (backward compatibility)
            if ($companyId === 0) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $user->update([
                        'palmpay_account_number' => $accountNumber,
                        'palmpay_account_name' => $accountName,
                        'palmpay_bank_name' => $bankName,
                    ]);
                }
            }

            DB::commit();

            Log::info('Virtual Account Created Successfully', [
                'account_id' => $accountId,
                'account_number' => $accountNumber
            ]);

            return $virtualAccount;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to Create Virtual Account', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get virtual account by account number
     * 
     * @param string $accountNumber
     * @return VirtualAccount|null
     */
    public function getByAccountNumber(string $accountNumber): ?VirtualAccount
    {
        return VirtualAccount::where('palmpay_account_number', $accountNumber)->first();
    }

    /**
     * Get virtual account by company and user ID
     * 
     * @param int $companyId
     * @param string $userId
     * @return VirtualAccount|null
     */
    public function getByCompanyAndUser(int $companyId, string $userId): ?VirtualAccount
    {
        return VirtualAccount::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Delete/Close a virtual account on PalmPay
     * 
     * @param string $accountNumber
     * @return array
     */
    public function deleteVirtualAccount(string $accountNumber): array
    {
        try {
            $requestData = [
                'virtualAccountNo' => $accountNumber,
            ];

            Log::info('Deleting PalmPay Virtual Account', ['account_number' => $accountNumber]);

            // Path: /api/v2/virtual/account/label/delete
            $response = $this->client->post('/api/v2/virtual/account/label/delete', $requestData);

            return [
                'success' => true,
                'message' => $response['message'] ?? 'Virtual account deleted successfully',
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Delete Virtual Account', [
                'account_number' => $accountNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update virtual account status on PalmPay (Enable/Disable)
     * 
     * @param string $accountNumber PalmPay virtual account number
     * @param string $status 'Enabled' or 'Disabled'
     * @return array
     */
    public function updateVirtualAccountStatus(string $accountNumber, string $status): array
    {
        try {
            // Validate status
            if (!in_array($status, ['Enabled', 'Disabled'])) {
                throw new \InvalidArgumentException('Status must be either "Enabled" or "Disabled"');
            }

            $requestData = [
                'virtualAccountNo' => $accountNumber,
                'status' => $status,
            ];

            Log::info('Updating PalmPay Virtual Account Status', [
                'account_number' => $accountNumber,
                'status' => $status
            ]);

            // Path: /api/v2/virtual/account/label/update
            $response = $this->client->post('/api/v2/virtual/account/label/update', $requestData);

            // Update local database
            $virtualAccount = VirtualAccount::where('palmpay_account_number', $accountNumber)->first();
            if ($virtualAccount) {
                $localStatus = $status === 'Enabled' ? 'active' : 'inactive';
                $virtualAccount->update([
                    'status' => $localStatus,
                    'palmpay_status' => $status,
                ]);
            }

            Log::info('Virtual Account Status Updated Successfully', [
                'account_number' => $accountNumber,
                'status' => $status
            ]);

            return [
                'success' => true,
                'message' => 'Virtual account status updated successfully',
                'status' => $status,
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Update Virtual Account Status', [
                'account_number' => $accountNumber,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Query status of a collection transaction
     * 
     * @param string $orderNo PalmPay order number or account reference
     * @return array
     */
    public function queryCollectionStatus(string $orderNo): array
    {
        try {
            // Path: /api/v2/virtual/account/query-list (or similar for settlement)
            // PalmPay documentation uses /api/v2/payment/query for general transaction query
            $requestData = [
                'orderNo' => $orderNo,
                'requestTime' => (int) (microtime(true) * 1000),
            ];

            Log::info('Querying PalmPay Collection Status', ['orderNo' => $orderNo]);

            $response = $this->client->post('/api/v2/payment/query', $requestData);

            return [
                'success' => true,
                'status' => $response['data']['status'] ?? 'unknown',
                'amount' => $response['data']['amount'] ?? 0,
                'fee' => $response['data']['fee'] ?? 0,
                'reference' => $response['data']['orderNo'] ?? $orderNo,
                'raw' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Query Collection Status', [
                'orderNo' => $orderNo,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get virtual account details from PalmPay
     * 
     * @param string $accountNumber
     * @return array
     */
    public function getVirtualAccountDetails(string $accountNumber): array
    {
        try {
            // Path: /api/v2/virtual/account/label/queryOne
            $requestData = [
                'virtualAccountNo' => $accountNumber,
            ];

            Log::info('Querying PalmPay Virtual Account Details', ['account_number' => $accountNumber]);

            $response = $this->client->post('/api/v2/virtual/account/label/queryOne', $requestData);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Query Virtual Account Details', [
                'account_number' => $accountNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Query a single pay-in order
     * 
     * @param string $orderNo Merchant order number or PalmPay order number
     * @return array
     */
    public function queryPayInOrder(string $orderNo): array
    {
        try {
            $requestData = [
                'orderNo' => $orderNo,
            ];

            Log::info('Querying PalmPay Pay-In Order', ['orderNo' => $orderNo]);

            // Path: /api/v2/virtual/account/query-list (single query)
            $response = $this->client->post('/api/v2/virtual/account/query-list', $requestData);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Query Pay-In Order', [
                'orderNo' => $orderNo,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk query pay-in orders
     * 
     * @param array $orderNos Array of order numbers
     * @return array
     */
    public function bulkQueryPayInOrders(array $orderNos): array
    {
        try {
            $requestData = [
                'orderNos' => $orderNos,
            ];

            Log::info('Bulk Querying PalmPay Pay-In Orders', ['count' => count($orderNos)]);

            // Path: /api/v2/virtual/account/query-list (bulk query)
            $response = $this->client->post('/api/v2/virtual/account/query-list', $requestData);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Bulk Query Pay-In Orders', [
                'count' => count($orderNos),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upgrade customer KYC from director BVN to customer's own BVN/NIN
     * 
     * @param string $accountNumber Virtual account number
     * @param array $kycData ['bvn' => '...'] or ['nin' => '...']
     * @return array
     */
    public function upgradeCustomerKyc(string $accountNumber, array $kycData): array
    {
        try {
            // Find virtual account
            $virtualAccount = VirtualAccount::where('account_number', $accountNumber)
                ->orWhere('palmpay_account_number', $accountNumber)
                ->first();

            if (!$virtualAccount) {
                throw new \Exception('Virtual account not found');
            }

            // Check if already using customer KYC
            if (in_array($virtualAccount->kyc_source, ['customer_bvn', 'customer_nin'])) {
                return [
                    'success' => false,
                    'message' => 'Virtual account already using customer KYC',
                    'current_kyc_source' => $virtualAccount->kyc_source
                ];
            }

            // Determine new KYC type
            $newBvn = $kycData['bvn'] ?? null;
            $newNin = $kycData['nin'] ?? null;

            if (!$newBvn && !$newNin) {
                throw new \Exception('Please provide customer BVN or NIN');
            }

            // Note: PalmPay doesn't have an "update KYC" endpoint
            // The virtual account will continue using director BVN on PalmPay side
            // But we track the customer's KYC upgrade in our database for compliance
            
            $oldKycSource = $virtualAccount->kyc_source;
            $newKycSource = $newBvn ? 'customer_bvn' : 'customer_nin';

            // Update virtual account record
            $virtualAccount->update([
                'bvn' => $newBvn ?? $virtualAccount->bvn,
                'nin' => $newNin ?? $virtualAccount->nin,
                'kyc_source' => $newKycSource,
                'kyc_upgraded' => true,
                'kyc_upgraded_at' => now(),
            ]);

            Log::info('Customer KYC Upgraded', [
                'account_number' => $accountNumber,
                'old_kyc_source' => $oldKycSource,
                'new_kyc_source' => $newKycSource,
            ]);

            return [
                'success' => true,
                'message' => 'Customer KYC upgraded successfully',
                'old_kyc_source' => $oldKycSource,
                'new_kyc_source' => $newKycSource,
                'upgraded_at' => $virtualAccount->kyc_upgraded_at,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to Upgrade Customer KYC', [
                'account_number' => $accountNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enhanced KYC Selection with Multi-Director Backup Support
     * Automatically selects the best available KYC method with fallback
     */
    private function selectOptimalKycMethod($company, $customerBvn, $customerNin, $companyId)
    {
        // Priority 1: Customer-provided KYC (highest success rate, unlimited capacity)
        if ($customerBvn) {
            Log::info('VirtualAccount: Using customer BVN', ['company_id' => $companyId]);
            return [
                'license_number' => $customerBvn,
                'identity_type' => 'personal',
                'kyc_source' => 'customer_bvn',
                'director_used' => null
            ];
        }
        
        if ($customerNin) {
            Log::info('VirtualAccount: Using customer NIN', ['company_id' => $companyId]);
            return [
                'license_number' => $customerNin,
                'identity_type' => 'personal_nin',
                'kyc_source' => 'customer_nin',
                'director_used' => null
            ];
        }
        
        // Priority 2: Director KYC with intelligent selection and backup support
        $availableKycMethods = $this->getAllAvailableKycMethods($company, $companyId);
        
        // Get blacklisted methods (methods that have failed recently)
        $blacklistedMethods = $this->getBlacklistedKycMethods($company);
        
        // Filter out blacklisted methods
        $viableKycMethods = array_filter($availableKycMethods, function($method) use ($blacklistedMethods) {
            return !in_array($method['method_key'], $blacklistedMethods);
        });
        
        // If no viable methods (all blacklisted), use all methods (reset blacklist)
        if (empty($viableKycMethods)) {
            Log::warning('VirtualAccount: All KYC methods blacklisted, resetting blacklist', [
                'company_id' => $companyId,
                'blacklisted_methods' => $blacklistedMethods
            ]);
            $viableKycMethods = $availableKycMethods;
            $this->resetKycBlacklist($company);
        }
        
        // Sort by success rate and preference
        usort($viableKycMethods, function($a, $b) {
            // Prefer NIN over BVN (more stable)
            if ($a['type'] === 'nin' && $b['type'] === 'bvn') return -1;
            if ($a['type'] === 'bvn' && $b['type'] === 'nin') return 1;
            
            // Then by success rate
            return $b['success_rate'] <=> $a['success_rate'];
        });
        
        // Select the best method
        $selectedMethod = $viableKycMethods[0];
        
        Log::info('VirtualAccount: Selected KYC method', [
            'company_id' => $companyId,
            'method' => $selectedMethod['method_key'],
            'type' => $selectedMethod['type'],
            'success_rate' => $selectedMethod['success_rate'],
            'total_available' => count($availableKycMethods),
            'viable_methods' => count($viableKycMethods)
        ]);
        
        return [
            'license_number' => $selectedMethod['license_number'],
            'identity_type' => $selectedMethod['identity_type'],
            'kyc_source' => $selectedMethod['kyc_source'],
            'director_used' => $selectedMethod['license_number']
        ];
    }
    
    /**
     * Get all available KYC methods for a company (including backup directors)
     */
    private function getAllAvailableKycMethods($company, $companyId)
    {
        $methods = [];
        
        // Primary Director Methods
        if ($company->director_bvn) {
            $methods[] = [
                'method_key' => 'director_bvn',
                'license_number' => $company->director_bvn,
                'identity_type' => 'personal',
                'kyc_source' => 'director_bvn',
                'type' => 'bvn',
                'success_rate' => $this->getKycSuccessRate('director_bvn', $companyId),
                'director_number' => 1
            ];
        }
        
        if ($company->director_nin) {
            $methods[] = [
                'method_key' => 'director_nin',
                'license_number' => $company->director_nin,
                'identity_type' => 'personal_nin',
                'kyc_source' => 'director_nin',
                'type' => 'nin',
                'success_rate' => $this->getKycSuccessRate('director_nin', $companyId),
                'director_number' => 1
            ];
        }
        
        // Backup Directors (2-10)
        for ($i = 2; $i <= 10; $i++) {
            $bvnField = "backup_director_{$i}_bvn";
            $ninField = "backup_director_{$i}_nin";
            
            if ($company->$bvnField) {
                $methodKey = "backup_director_{$i}_bvn";
                $methods[] = [
                    'method_key' => $methodKey,
                    'license_number' => $company->$bvnField,
                    'identity_type' => 'personal',
                    'kyc_source' => $methodKey,
                    'type' => 'bvn',
                    'success_rate' => $this->getKycSuccessRate($methodKey, $companyId),
                    'director_number' => $i
                ];
            }
            
            if ($company->$ninField) {
                $methodKey = "backup_director_{$i}_nin";
                $methods[] = [
                    'method_key' => $methodKey,
                    'license_number' => $company->$ninField,
                    'identity_type' => 'personal_nin',
                    'kyc_source' => $methodKey,
                    'type' => 'nin',
                    'success_rate' => $this->getKycSuccessRate($methodKey, $companyId),
                    'director_number' => $i
                ];
            }
        }
        
        // Business RC (Corporate fallback)
        if ($company->business_registration_number) {
            $rcNumber = $company->business_registration_number;
            
            // Add RC prefix if needed
            if (!str_starts_with(strtoupper($rcNumber), 'RC') && !str_starts_with(strtoupper($rcNumber), 'BN')) {
                $rcNumber = 'RC' . $rcNumber;
            }
            
            $methods[] = [
                'method_key' => 'company_rc',
                'license_number' => $rcNumber,
                'identity_type' => 'company',
                'kyc_source' => 'company_rc',
                'type' => 'corporate',
                'success_rate' => $this->getKycSuccessRate('company_rc', $companyId),
                'director_number' => 0
            ];
        }
        
        return $methods;
    }
    
    /**
     * Get success rate for a specific KYC method (enhanced version)
     */
    private function getKycSuccessRate($kycSource, $companyId)
    {
        $total = VirtualAccount::where('company_id', $companyId)
            ->where('kyc_source', $kycSource)
            ->count();
            
        if ($total === 0) {
            return 50; // Default success rate for untested methods
        }
        
        $successful = VirtualAccount::where('company_id', $companyId)
            ->where('kyc_source', $kycSource)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();
            
        return ($successful / $total) * 100;
    }
    
    /**
     * Get blacklisted KYC methods for a company
     */
    private function getBlacklistedKycMethods($company)
    {
        if (!$company->kyc_method_blacklist) {
            return [];
        }
        
        $blacklist = json_decode($company->kyc_method_blacklist, true) ?? [];
        
        // Remove methods that were blacklisted more than 24 hours ago (auto-recovery)
        $cutoffTime = now()->subHours(24);
        $activeBlacklist = [];
        
        foreach ($blacklist as $method => $timestamp) {
            if (strtotime($timestamp) > $cutoffTime->timestamp) {
                $activeBlacklist[] = $method;
            }
        }
        
        return $activeBlacklist;
    }
    
    /**
     * Add a KYC method to blacklist (when it fails)
     */
    private function blacklistKycMethod($company, $methodKey, $errorMessage)
    {
        $blacklist = json_decode($company->kyc_method_blacklist, true) ?? [];
        $blacklist[$methodKey] = now()->toISOString();
        
        $company->update([
            'kyc_method_blacklist' => json_encode($blacklist),
            'kyc_last_updated' => now()
        ]);
        
        Log::warning('VirtualAccount: KYC method blacklisted', [
            'company_id' => $company->id,
            'method' => $methodKey,
            'error' => $errorMessage,
            'blacklist_until' => now()->addHours(24)->toISOString()
        ]);
    }
    
    /**
     * Reset KYC blacklist (when all methods are blacklisted)
     */
    private function resetKycBlacklist($company)
    {
        $company->update([
            'kyc_method_blacklist' => null,
            'kyc_last_updated' => now()
        ]);
        
        Log::info('VirtualAccount: KYC blacklist reset', [
            'company_id' => $company->id,
            'reason' => 'All methods were blacklisted'
        ]);
    }
    
    /**
     * Call PalmPay API with automatic KYC fallback on failures + Global KYC tracking
     */
    private function callPalmPayWithKycFallback($requestData, $company, $companyId, $globalKycId = null, $attempt = 1)
    {
        $maxAttempts = 5;
        
        try {
            Log::info('PalmPay API Call', [
                'attempt' => $attempt,
                'company_id' => $companyId,
                'kyc_method' => $requestData['identityType'] ?? 'unknown',
                'license_number' => substr($requestData['licenseNumber'] ?? '', 0, 5) . '***'
            ]);
            
            // Call PalmPay API
            $response = $this->client->post('/api/v2/virtual/account/label/create', $requestData);
            
            Log::info('PalmPay API Success', [
                'attempt' => $attempt,
                'company_id' => $companyId,
                'account_number' => $response['data']['virtualAccountNo'] ?? 'unknown',
                'global_kyc_used' => $globalKycId ? true : false
            ]);
            
            // Record successful global KYC usage
            if ($globalKycId) {
                $globalKycService = new \App\Services\GlobalKycService();
                $globalKycService->recordUsage(
                    $globalKycId,
                    $companyId,
                    true, // success
                    null, // no error
                    null, // virtual account ID will be set later
                    $requestData
                );
            }
            
            return $response;
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            Log::warning('PalmPay API Failed', [
                'attempt' => $attempt,
                'company_id' => $companyId,
                'error' => $errorMessage,
                'kyc_method' => $requestData['identityType'] ?? 'unknown',
                'global_kyc_used' => $globalKycId ? true : false
            ]);
            
            // Record failed global KYC usage
            if ($globalKycId) {
                $globalKycService = new \App\Services\GlobalKycService();
                $globalKycService->recordUsage(
                    $globalKycId,
                    $companyId,
                    false, // failed
                    $errorMessage,
                    null, // no virtual account created
                    $requestData
                );
            }
            
            // Check if this is a KYC-related error that we can retry with different method
            if ($this->isKycRelatedError($errorMessage) && $attempt < $maxAttempts) {
                
                // Blacklist the current KYC method
                $currentKycSource = $this->determineKycSourceFromRequest($requestData);
                if ($currentKycSource) {
                    $this->blacklistKycMethod($company, $currentKycSource, $errorMessage);
                }
                
                Log::info('Retrying with different KYC method', [
                    'attempt' => $attempt + 1,
                    'blacklisted_method' => $currentKycSource,
                    'company_id' => $companyId
                ]);
                
                // Get new KYC method (excluding blacklisted ones) - try global fallback
                $company->refresh(); // Refresh to get updated blacklist
                $kycResult = $this->selectOptimalKycMethodWithGlobalFallback($company, null, null, $companyId);
                
                // Update request data with new KYC method
                $requestData['identityType'] = $kycResult['identity_type'];
                $requestData['licenseNumber'] = $kycResult['license_number'];
                $newGlobalKycId = $kycResult['global_kyc_id'] ?? null;
                
                // CAC Prefix Enforcement for Companies
                if ($kycResult['identity_type'] === 'company') {
                    $licenseNumber = strtoupper(trim($kycResult['license_number']));
                    if (!str_starts_with($licenseNumber, 'RC') && !str_starts_with($licenseNumber, 'BN')) {
                        $licenseNumber = 'RC' . $licenseNumber;
                    }
                    $requestData['licenseNumber'] = $licenseNumber;
                }
                
                // Retry with new KYC method
                return $this->callPalmPayWithKycFallback($requestData, $company, $companyId, $newGlobalKycId, $attempt + 1);
            }
            
            // Non-retryable error or max attempts reached
            throw $e;
        }
    }
    
    /**
     * Check if error is KYC-related and retryable
     */
    private function isKycRelatedError($errorMessage)
    {
        $kycErrors = [
            'licenseNumber duplicate',
            'BVN already exists',
            'NIN already exists',
            'Invalid license number',
            'KYC verification failed',
            'License number not found',
            'Identity verification failed'
        ];
        
        foreach ($kycErrors as $error) {
            if (stripos($errorMessage, $error) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Determine KYC source from request data
     */
    private function determineKycSourceFromRequest($requestData)
    {
        $identityType = $requestData['identityType'] ?? '';
        $licenseNumber = $requestData['licenseNumber'] ?? '';
        
        if ($identityType === 'company') {
            return 'company_rc';
        }
        
        if ($identityType === 'personal_nin') {
            return 'director_nin'; // This could be more specific with backup director detection
        }
        
        if ($identityType === 'personal') {
            return 'director_bvn'; // This could be more specific with backup director detection
        }
        
        return null;
    }
    
    /**
     * Enhanced KYC Selection with Multi-Director Backup Support + Global Fallback
     * Tries company KYC first, then global pool as fallback
     */
    private function selectOptimalKycMethodWithGlobalFallback($company, $customerBvn, $customerNin, $companyId)
    {
        // Priority 1: Customer-provided KYC (highest success rate, unlimited capacity)
        if ($customerBvn) {
            Log::info('VirtualAccount: Using customer BVN', ['company_id' => $companyId]);
            return [
                'license_number' => $customerBvn,
                'identity_type' => 'personal',
                'kyc_source' => 'customer_bvn',
                'director_used' => null,
                'global_kyc_id' => null
            ];
        }
        
        if ($customerNin) {
            Log::info('VirtualAccount: Using customer NIN', ['company_id' => $companyId]);
            return [
                'license_number' => $customerNin,
                'identity_type' => 'personal_nin',
                'kyc_source' => 'customer_nin',
                'director_used' => null,
                'global_kyc_id' => null
            ];
        }
        
        // Priority 2: Try company's own KYC methods first
        try {
            $companyKyc = $this->selectOptimalKycMethod($company, null, null, $companyId);
            $companyKyc['global_kyc_id'] = null; // Mark as company KYC
            return $companyKyc;
        } catch (\Exception $e) {
            Log::info('VirtualAccount: Company KYC methods exhausted, trying global fallback', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
        }
        
        // Priority 3: Fallback to global KYC pool
        $globalKycService = new \App\Services\GlobalKycService();
        $globalKyc = $globalKycService->selectOptimalGlobalKyc();
        
        if (!$globalKyc) {
            throw new \Exception('No KYC methods available: Company KYC exhausted and global pool empty');
        }
        
        Log::info('VirtualAccount: Using global KYC fallback', [
            'company_id' => $companyId,
            'global_kyc_id' => $globalKyc->id,
            'kyc_type' => $globalKyc->kyc_type,
            'kyc_number' => substr($globalKyc->kyc_number, 0, 5) . '***'
        ]);
        
        return [
            'license_number' => $globalKyc->kyc_number,
            'identity_type' => $globalKyc->kyc_type === 'nin' ? 'personal_nin' : 'personal',
            'kyc_source' => 'global_' . $globalKyc->kyc_type,
            'director_used' => $globalKyc->kyc_number,
            'global_kyc_id' => $globalKyc->id
        ];
    }
}
