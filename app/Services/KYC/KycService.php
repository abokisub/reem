<?php

namespace App\Services\KYC;

use App\Models\Company;
use App\Models\CompanyKycApproval;
use App\Models\CompanyKycHistory;
use App\Services\KYC\SandboxKycService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Core KYC Service
 * Handles company KYC submission, approval, and status management
 */
class KycService
{
    protected $easeIdClient;

    public function __construct(EaseIdClient $easeIdClient)
    {
        $this->easeIdClient = $easeIdClient;
    }

    /**
     * Submit KYC section for review
     * In sandbox mode: Instantly auto-approved
     * In production: Pending admin review
     */
    public function submitKycSection(int $companyId, string $section, array $data): array
    {
        $validSections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];

        if (!in_array($section, $validSections)) {
            throw new Exception("Invalid KYC section: $section");
        }

        return DB::transaction(function () use ($companyId, $section, $data) {
            // Create or update approval record
            $approval = CompanyKycApproval::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'section' => $section,
                ],
                [
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'rejection_reason' => null,
                    'reviewed_at' => null,
                ]
            );

            // Log the submission
            CompanyKycHistory::logAction(
                $companyId,
                $section,
                'submitted',
                null,
                'KYC section submitted for review',
                $data
            );

            // SANDBOX MODE: Instant auto-approval
            if (SandboxKycService::isSandbox()) {
                $approval->update([
                    'status' => 'approved',
                    'reviewed_by' => 1, // Sandbox system
                    'reviewed_at' => now(),
                ]);

                CompanyKycHistory::logAction(
                    $companyId,
                    $section,
                    'approved',
                    1,
                    'Auto-approved by sandbox (instant)',
                    array_merge($data, ['sandbox' => true, 'auto_approved' => true])
                );

                // Update company status
                $this->updateCompanyKycStatusSandbox($companyId);

                Log::info('Sandbox KYC Auto-Approved on Submission', [
                    'company_id' => $companyId,
                    'section' => $section,
                ]);

                return [
                    'success' => true,
                    'message' => 'KYC section instantly approved (sandbox)',
                    'approval' => $approval->fresh(),
                    'sandbox' => true,
                ];
            }

            // PRODUCTION MODE: Update company KYC status to under_review
            $company = Company::find($companyId);
            if ($company && $company->kyc_status === 'pending') {
                $company->update(['kyc_status' => 'under_review']);
            }

            Log::info('KYC Section Submitted', [
                'company_id' => $companyId,
                'section' => $section,
            ]);

            return [
                'success' => true,
                'message' => 'KYC section submitted successfully',
                'approval' => $approval,
            ];
        });
    }

    /**
     * Update company KYC status in sandbox (instant approval logic)
     */
    protected function updateCompanyKycStatusSandbox(int $companyId): void
    {
        $company = Company::find($companyId);
        $approvedCount = CompanyKycApproval::where('company_id', $companyId)
            ->where('status', 'approved')
            ->count();

        if (CompanyKycApproval::allSectionsApproved($companyId)) {
            $company->update([
                'kyc_status' => 'verified',
                'kyc_reviewed_by' => 1,
                'kyc_reviewed_at' => now(),
            ]);
        } elseif ($approvedCount > 0) {
            $company->update(['kyc_status' => 'partial']);
        }
    }

    /**
     * Approve KYC section
     */
    public function approveSection(int $companyId, string $section, int $adminId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($companyId, $section, $adminId, $notes) {
            $approval = CompanyKycApproval::where('company_id', $companyId)
                ->where('section', $section)
                ->first();

            if (!$approval) {
                throw new Exception("KYC section not found");
            }

            $approval->update([
                'status' => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            // Log the approval
            CompanyKycHistory::logAction(
                $companyId,
                $section,
                'approved',
                $adminId,
                $notes,
                null
            );

            // Update company KYC status based on approval progress
            $company = Company::find($companyId);
            $approvedCount = CompanyKycApproval::where('company_id', $companyId)
                ->where('status', 'approved')
                ->count();

            if (CompanyKycApproval::allSectionsApproved($companyId)) {
                // All 5 sections approved - full approval
                $company->update([
                    'kyc_status' => 'verified',
                    'kyc_reviewed_by' => $adminId,
                    'kyc_reviewed_at' => now(),
                ]);

                Log::info('Company KYC Fully Approved', ['company_id' => $companyId]);
            } elseif ($approvedCount > 0) {
                // At least one section approved but not all - partial status
                $company->update([
                    'kyc_status' => 'partial',
                ]);

                Log::info('Company KYC Partially Approved', [
                    'company_id' => $companyId,
                    'approved_sections' => $approvedCount,
                ]);
            }

            Log::info('KYC Section Approved', [
                'company_id' => $companyId,
                'section' => $section,
                'admin_id' => $adminId,
            ]);

            return [
                'success' => true,
                'message' => 'KYC section approved successfully',
                'approval' => $approval,
            ];
        });
    }

    /**
     * Reject KYC section
     */
    public function rejectSection(int $companyId, string $section, int $adminId, string $reason): array
    {
        return DB::transaction(function () use ($companyId, $section, $adminId, $reason) {
            $approval = CompanyKycApproval::where('company_id', $companyId)
                ->where('section', $section)
                ->first();

            if (!$approval) {
                throw new Exception("KYC section not found");
            }

            $approval->update([
                'status' => 'rejected',
                'reviewed_by' => $adminId,
                'rejection_reason' => $reason,
                'reviewed_at' => now(),
            ]);

            // Log the rejection
            CompanyKycHistory::logAction(
                $companyId,
                $section,
                'rejected',
                $adminId,
                $reason,
                null
            );

            // Update company KYC status to rejected
            $company = Company::find($companyId);
            $company->update([
                'kyc_status' => 'rejected',
                'kyc_reviewed_by' => $adminId,
                'kyc_reviewed_at' => now(),
                'kyc_rejection_reason' => $reason,
            ]);

            Log::info('KYC Section Rejected', [
                'company_id' => $companyId,
                'section' => $section,
                'admin_id' => $adminId,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'KYC section rejected',
                'approval' => $approval,
            ];
        });
    }

    /**
     * Get KYC status for a company
     */
    public function getKycStatus(int $companyId): array
    {
        $company = Company::find($companyId);

        if (!$company) {
            throw new Exception("Company not found");
        }

        $approvals = CompanyKycApproval::getApprovalSummary($companyId);
        $history = CompanyKycHistory::getCompanyHistory($companyId);

        return [
            'overall_status' => $company->kyc_status,
            'reviewed_at' => $company->kyc_reviewed_at,
            'reviewed_by' => $company->kyc_reviewed_by,
            'rejection_reason' => $company->kyc_rejection_reason ?? null,
            'sections' => $approvals,
            'history' => $history,
        ];
    }

    /**
     * Verify BVN via EaseID with optional charge deduction
     * 
     * @param string $bvn
     * @param int|null $companyId - If provided, charges will be deducted (API usage)
     * @param bool $chargeForVerification - Set to false for internal/onboarding KYC (no charge)
     */
    public function verifyBVN(string $bvn, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // 1. Check Cache if companyId provided
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->bvn === $bvn) {
                // Check if we have cached verification data
                $verificationData = $company->verification_data ?? [];
                if (isset($verificationData['bvn']) && !empty($verificationData['bvn'])) {
                    return [
                        'success' => true,
                        'message' => 'BVN verified successfully (Cached)',
                        'data' => $verificationData['bvn'],
                        'charged' => false,
                    ];
                }
            }
        }

        // 2. Deduct KYC Charge ONLY if requested (API usage, not onboarding)
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'enhanced_bvn');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            // 3. Call EaseID API
            if (SandboxKycService::isSandbox()) {
                $sandboxService = new SandboxKycService();
                $result = $sandboxService->mockBVNVerification($bvn);
            } else {
                $result = $this->easeIdClient->verifyBVN($bvn);
            }

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'BVN verification failed',
                    'data' => null,
                    'charged' => $chargeResult ? true : false,
                    'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                ];
            }

            // 4. Cache Result if companyId provided
            if ($companyId) {
                $company = Company::find($companyId);
                if ($company) {
                    $verificationData = $company->verification_data ?? [];
                    $verificationData['bvn'] = $result['data'];

                    $company->update([
                        'bvn' => $bvn,
                        'verification_data' => $verificationData
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => 'BVN verified successfully',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('BVN Verification Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'BVN verification failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
            ];
        }
    }

    /**
     * Verify NIN via EaseID with optional charge deduction
     * 
     * @param string $nin
     * @param int|null $companyId - If provided, charges will be deducted (API usage)
     * @param bool $chargeForVerification - Set to false for internal/onboarding KYC (no charge)
     */
    public function verifyNIN(string $nin, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // 1. Check Cache if companyId provided
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->nin === $nin) {
                // Check if we have cached verification data
                $verificationData = $company->verification_data ?? [];
                if (isset($verificationData['nin']) && !empty($verificationData['nin'])) {
                    return [
                        'success' => true,
                        'message' => 'NIN verified successfully (Cached)',
                        'data' => $verificationData['nin'],
                        'charged' => false,
                    ];
                }
            }
        }

        // 2. Deduct KYC Charge ONLY if requested (API usage, not onboarding)
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'enhanced_nin');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            // 3. Call EaseID API
            if (SandboxKycService::isSandbox()) {
                $sandboxService = new SandboxKycService();
                $result = $sandboxService->mockNINVerification($nin);
            } else {
                $result = $this->easeIdClient->verifyNIN($nin);
            }

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'NIN verification failed',
                    'data' => null,
                    'charged' => $chargeResult ? true : false,
                    'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                ];
            }

            // 4. Cache Result if companyId provided
            if ($companyId) {
                $company = Company::find($companyId);
                if ($company) {
                    $verificationData = $company->verification_data ?? [];
                    $verificationData['nin'] = $result['data'];

                    $company->update([
                        'nin' => $nin,
                        'verification_data' => $verificationData
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => 'NIN verified successfully',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('NIN Verification Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'NIN verification failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
            ];
        }
    }

    /**
     * Verify bank account via EaseID with optional charge deduction
     * 
     * @param string $accountNumber
     * @param string $bankCode
     * @param int|null $companyId - If provided, charges will be deducted (API usage)
     * @param bool $chargeForVerification - Set to false for internal/onboarding KYC (no charge)
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // 1. Deduct KYC Charge ONLY if requested (API usage, not onboarding)
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'bank_account_verification');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            $result = $this->easeIdClient->verifyBankAccount($accountNumber, $bankCode);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Bank account verification failed',
                    'data' => null,
                    'charged' => $chargeResult ? true : false,
                    'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                ];
            }

            return [
                'success' => true,
                'message' => 'Bank account verified successfully',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('Bank Account Verification Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Bank account verification failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
            ];
        }
    }

    /**
     * Deduct KYC charge from company wallet
     * 
     * IMPORTANT: Only charges VERIFIED companies (after onboarding is complete).
     * New companies during onboarding (kyc_status = 'pending', 'under_review', 'partial') are NOT charged.
     */
    protected function deductKycCharge(int $companyId, string $serviceName): array
    {
        try {
            // 0. Check if company is still onboarding (FREE KYC during onboarding)
            $company = Company::find($companyId);
            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found',
                ];
            }

            // FREE KYC for companies still in onboarding process
            $onboardingStatuses = ['pending', 'under_review', 'partial', 'unverified'];
            if (in_array($company->kyc_status, $onboardingStatuses)) {
                Log::info('KYC Verification - FREE (Company Onboarding)', [
                    'company_id' => $companyId,
                    'company_name' => $company->name,
                    'kyc_status' => $company->kyc_status,
                    'service_name' => $serviceName,
                    'reason' => 'Company is still in onboarding process'
                ]);

                return [
                    'success' => true,
                    'message' => 'KYC verification completed (Free during onboarding)',
                    'charge_amount' => 0,
                    'transaction_id' => null,
                    'transaction_reference' => null,
                    'free_onboarding' => true,
                ];
            }

            // 1. Get charge configuration (only for VERIFIED companies)
            $charge = DB::table('service_charges')
                ->where('company_id', $companyId)
                ->where('service_category', 'kyc')
                ->where('service_name', $serviceName)
                ->where('is_active', true)
                ->first();

            // Fallback to global default if company-specific not found
            if (!$charge) {
                $charge = DB::table('service_charges')
                    ->where('company_id', 1)
                    ->where('service_category', 'kyc')
                    ->where('service_name', $serviceName)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$charge) {
                return [
                    'success' => false,
                    'message' => 'KYC charge configuration not found for ' . $serviceName,
                ];
            }

            $chargeAmount = $charge->charge_value;

            // 2. Check wallet balance
            $wallet = DB::table('company_wallets')
                ->where('company_id', $companyId)
                ->first();

            if (!$wallet || $wallet->balance < $chargeAmount) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Insufficient balance. Required: ₦%.2f, Available: ₦%.2f',
                        $chargeAmount,
                        $wallet->balance ?? 0
                    ),
                ];
            }

            // 3. Deduct from wallet and create transaction
            return DB::transaction(function () use ($companyId, $serviceName, $chargeAmount, $wallet) {
                // Deduct from wallet
                DB::table('company_wallets')
                    ->where('company_id', $companyId)
                    ->decrement('balance', $chargeAmount);

                // Create transaction record
                $reference = 'KYC_' . strtoupper($serviceName) . '_' . time() . '_' . rand(1000, 9999);
                
                $transactionId = DB::table('transactions')->insertGetId([
                    'company_id' => $companyId,
                    'reference' => $reference,
                    'type' => 'debit',
                    'category' => 'kyc_charge',
                    'amount' => $chargeAmount,
                    'fee' => 0,
                    'net_amount' => $chargeAmount,
                    'balance_before' => $wallet->balance,
                    'balance_after' => $wallet->balance - $chargeAmount,
                    'status' => 'success',
                    'description' => 'KYC Verification Charge - ' . ucwords(str_replace('_', ' ', $serviceName)),
                    'metadata' => json_encode([
                        'service_name' => $serviceName,
                        'service_category' => 'kyc',
                        'charge_type' => 'flat',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('KYC Charge Deducted', [
                    'company_id' => $companyId,
                    'service_name' => $serviceName,
                    'amount' => $chargeAmount,
                    'transaction_id' => $transactionId,
                    'reference' => $reference,
                ]);

                return [
                    'success' => true,
                    'message' => 'KYC charge deducted successfully',
                    'charge_amount' => $chargeAmount,
                    'transaction_id' => $transactionId,
                    'transaction_reference' => $reference,
                    'free_onboarding' => false,
                ];
            });

        } catch (Exception $e) {
            Log::error('KYC Charge Deduction Error', [
                'company_id' => $companyId,
                'service_name' => $serviceName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to deduct KYC charge: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Face Recognition - Compare two faces
     */
    public function compareFaces(string $sourceImage, string $targetImage, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // Deduct charge if requested
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'face_recognition');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            $result = $this->easeIdClient->compareFaces($sourceImage, $targetImage);

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Face comparison completed',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Face Recognition Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Face recognition failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
            ];
        }
    }

    /**
     * Initialize Liveness Detection
     */
    public function initializeLiveness(string $bizId, string $redirectUrl, ?string $userId, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // Deduct charge if requested
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'liveness_detection');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            $result = $this->easeIdClient->initializeLiveness($bizId, $redirectUrl, $userId);

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Liveness initialized',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Liveness Initialize Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Liveness initialization failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
            ];
        }
    }

    /**
     * Check Blacklist
     */
    public function checkBlacklist(?string $phoneNumber, ?string $bvn, ?string $nin, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // Deduct charge if requested
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'blacklist_check');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            $result = $this->easeIdClient->checkBlacklist($phoneNumber, $bvn, $nin);

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Blacklist check completed',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Blacklist Check Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Blacklist check failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
            ];
        }
    }

    /**
     * Get Credit Score (Nigeria)
     */
    public function getCreditScore(string $mobileNo, string $idNumber, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // Deduct charge if requested
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'credit_score');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            $result = $this->easeIdClient->getCreditScoreNigeria($mobileNo, $idNumber);

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Credit score retrieved',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Credit Score Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Credit score query failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
            ];
        }
    }

    /**
     * Get Loan Features
     */
    public function getLoanFeatures(string $value, int $type, string $accessType, ?int $companyId = null, bool $chargeForVerification = true): array
    {
        // Deduct charge if requested
        $chargeResult = null;
        if ($companyId && $chargeForVerification) {
            $chargeResult = $this->deductKycCharge($companyId, 'loan_features');
            if (!$chargeResult['success']) {
                return [
                    'success' => false,
                    'message' => $chargeResult['message'],
                    'data' => null,
                    'charged' => false,
                ];
            }
        }

        try {
            $result = $this->easeIdClient->getLoanFeatures($value, $type, $accessType);

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Loan features retrieved',
                'data' => $result['data'],
                'charged' => $chargeResult ? true : false,
                'charge_amount' => $chargeResult['charge_amount'] ?? 0,
                'transaction_reference' => $chargeResult['transaction_reference'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Loan Features Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Loan features query failed: ' . $e->getMessage(),
                'data' => null,
                'charged' => $chargeResult ? true : false,
            ];
        }
    }
}
