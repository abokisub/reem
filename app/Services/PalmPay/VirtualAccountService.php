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

            // Deduplication Tier 2: Check by Email or Phone (Identity Guarding)
            if (!$existing) {
                $email = $customerData['email'] ?? null;
                $phone = $customerData['phone'] ?? null;

                if ($email || $phone) {
                    // This is more complex because email/phone aren't in virtual_accounts table directly.
                    // We check if any existing account for this company/bank belongs to someone with this email/phone.
                    $existing = VirtualAccount::where('company_id', $companyId)
                        ->where('bank_code', $bankCode)
                        ->where('status', 'active')
                        ->whereNull('deleted_at')
                        ->where(function ($query) use ($email, $phone, $companyId) {
                            // Check platform users
                            $query->whereIn('user_id', function ($q) use ($email, $phone) {
                                $q->select('id')->from('users')->where('email', $email);
                                if ($phone)
                                    $q->orWhere('phone', $phone);
                            })
                                // OR check merchant customers (CompanyUser)
                                ->orWhereIn('user_id', function ($q) use ($email, $phone, $companyId) {
                                $q->select('uuid')->from('company_users')->where('company_id', $companyId)->where('email', $email);
                                if ($phone)
                                    $q->orWhere('phone', $phone);
                            });
                        })
                        ->first();
                }
            }

            if ($existing) {
                Log::info('Returning existing virtual account (resolved by identity)', [
                    'identity' => $customerData['email'] ?? $customerData['phone'] ?? $userId,
                    'bank_code' => $bankCode,
                    'account_number' => $existing->palmpay_account_number
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
            
            // Determine KYC source and identity type
            $kycSource = 'director_bvn'; // Default: use director BVN
            $licenseNumber = null;
            $identityType = 'personal';
            $directorBvnUsed = null;
            
            // Priority 1: Customer provides their own BVN
            if ($customerBvn) {
                $licenseNumber = $customerBvn;
                $identityType = 'personal';
                $kycSource = 'customer_bvn';
            }
            // Priority 2: Customer provides their own NIN
            elseif ($customerNin) {
                $licenseNumber = $customerNin;
                $identityType = 'personal_nin'; // PalmPay requires "personal_nin" for NIN
                $kycSource = 'customer_nin';
            }
            // Priority 3: Use company director's BVN (aggregator model)
            elseif ($company->director_bvn) {
                $licenseNumber = $company->director_bvn;
                $identityType = 'personal';
                $kycSource = 'director_bvn';
                $directorBvnUsed = $company->director_bvn;
            }
            // Priority 4: Use company director's NIN
            elseif ($company->director_nin) {
                $licenseNumber = $company->director_nin;
                $identityType = 'personal_nin';
                $kycSource = 'director_nin';
                $directorBvnUsed = $company->director_nin;
            }
            // Fallback: Use company RC number (corporate mode)
            else {
                $licenseNumber = $company->business_registration_number;
                $identityType = 'company';
                $kycSource = 'company_rc';
            }
            
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

            // Call PalmPay API
            // Path: /api/v2/virtual/account/label/create
            $response = $this->client->post('/api/v2/virtual/account/label/create', $requestData);

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
}
