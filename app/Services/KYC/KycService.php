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
     * Verify BVN via EaseID
     */
    /**
     * Verify BVN via EaseID
     */
    public function verifyBVN(string $bvn, ?int $companyId = null): array
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
                    ];
                }
            }
        }

        try {
            // 2. Call EaseID API
            // Sandbox check logic is handled inside EaseIdClient or SandboxKycService depending on implementation
            // But if we are using the main KycService, we should use the EaseIdClient

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
                ];
            }

            // 3. Cache Result if companyId provided
            if ($companyId) {
                $company = Company::find($companyId);
                if ($company) {
                    $verificationData = $company->verification_data ?? [];
                    $verificationData['bvn'] = $result['data'];

                    $company->update([
                        'bvn' => $bvn, // Associate BVN with company
                        'verification_data' => $verificationData
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => 'BVN verified successfully',
                'data' => $result['data'],
            ];

        } catch (Exception $e) {
            Log::error('BVN Verification Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'BVN verification failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Verify NIN via EaseID
     */
    /**
     * Verify NIN via EaseID
     */
    public function verifyNIN(string $nin, ?int $companyId = null): array
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
                    ];
                }
            }
        }

        try {
            // 2. Call EaseID API
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
                ];
            }

            // 3. Cache Result if companyId provided
            if ($companyId) {
                $company = Company::find($companyId);
                if ($company) {
                    $verificationData = $company->verification_data ?? [];
                    $verificationData['nin'] = $result['data'];

                    $company->update([
                        'nin' => $nin, // Associate NIN with company
                        'verification_data' => $verificationData
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => 'NIN verified successfully',
                'data' => $result['data'],
            ];

        } catch (Exception $e) {
            Log::error('NIN Verification Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'NIN verification failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Verify bank account via EaseID
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $result = $this->easeIdClient->verifyBankAccount($accountNumber, $bankCode);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Bank account verification failed',
                    'data' => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Bank account verified successfully',
                'data' => $result['data'],
            ];

        } catch (Exception $e) {
            Log::error('Bank Account Verification Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Bank account verification failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
