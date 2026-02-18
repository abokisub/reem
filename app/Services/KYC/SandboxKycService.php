<?php

namespace App\Services\KYC;

use App\Models\Company;
use App\Models\CompanyKycApproval;
use App\Models\CompanyKycHistory;
use Illuminate\Support\Facades\Log;

/**
 * Sandbox KYC Service
 * Provides mock KYC approval/rejection flows for sandbox testing
 */
class SandboxKycService
{
    /**
     * Check if environment is sandbox
     */
    public static function isSandbox(): bool
    {
        return config('app.env') === 'sandbox' ||
            config('app.sandbox_mode', false) === true;
    }

    /**
     * Auto-approve KYC section (sandbox only)
     * Simulates admin approval after a delay
     */
    public function autoApproveSection(int $companyId, string $section): array
    {
        if (!self::isSandbox()) {
            return [
                'success' => false,
                'message' => 'Auto-approval only available in sandbox mode',
            ];
        }

        // Simulate processing delay
        sleep(2);

        $approval = CompanyKycApproval::where('company_id', $companyId)
            ->where('section', $section)
            ->first();

        if (!$approval) {
            return [
                'success' => false,
                'message' => 'Section not found',
            ];
        }

        $approval->update([
            'status' => 'approved',
            'reviewed_by' => 1, // Sandbox admin ID
            'reviewed_at' => now(),
        ]);

        CompanyKycHistory::logAction(
            $companyId,
            $section,
            'approved',
            1,
            'Auto-approved by sandbox system',
            ['sandbox' => true]
        );

        // Check if all sections approved
        if (CompanyKycApproval::allSectionsApproved($companyId)) {
            $company = Company::find($companyId);
            $company->update([
                'kyc_status' => 'approved',
                'kyc_reviewed_by' => 1,
                'kyc_reviewed_at' => now(),
            ]);
        } else {
            // Set to partial if at least one approved
            $approvedCount = CompanyKycApproval::where('company_id', $companyId)
                ->where('status', 'approved')
                ->count();

            if ($approvedCount > 0) {
                Company::find($companyId)->update(['kyc_status' => 'partial']);
            }
        }

        Log::info('Sandbox KYC Auto-Approved', [
            'company_id' => $companyId,
            'section' => $section,
        ]);

        return [
            'success' => true,
            'message' => 'Section auto-approved (sandbox)',
            'approval' => $approval,
        ];
    }

    /**
     * Auto-reject KYC section (sandbox only)
     * For testing rejection flows
     */
    public function autoRejectSection(int $companyId, string $section, string $reason = 'Sandbox test rejection'): array
    {
        if (!self::isSandbox()) {
            return [
                'success' => false,
                'message' => 'Auto-rejection only available in sandbox mode',
            ];
        }

        $approval = CompanyKycApproval::where('company_id', $companyId)
            ->where('section', $section)
            ->first();

        if (!$approval) {
            return [
                'success' => false,
                'message' => 'Section not found',
            ];
        }

        $approval->update([
            'status' => 'rejected',
            'reviewed_by' => 1,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        CompanyKycHistory::logAction(
            $companyId,
            $section,
            'rejected',
            1,
            $reason,
            ['sandbox' => true, 'auto_rejected' => true]
        );

        // Update company status
        Company::find($companyId)->update([
            'kyc_status' => 'rejected',
            'kyc_reviewed_by' => 1,
            'kyc_reviewed_at' => now(),
            'kyc_rejection_reason' => $reason,
        ]);

        Log::info('Sandbox KYC Auto-Rejected', [
            'company_id' => $companyId,
            'section' => $section,
        ]);

        return [
            'success' => true,
            'message' => 'Section instantly rejected (sandbox)',
            'approval' => $approval,
        ];
    }

    /**
     * Mock BVN verification (sandbox)
     */
    public function mockBVNVerification(string $bvn): array
    {
        // No delay - instant response

        // Mock success for BVNs starting with '2'
        if (str_starts_with($bvn, '2')) {
            return [
                'success' => true,
                'message' => 'BVN verified (sandbox)',
                'data' => [
                    'bvn' => $bvn,
                    'firstName' => 'Sandbox',
                    'lastName' => 'User',
                    'gender' => 'Male',
                    'birthday' => '1990-01-01',
                    'phone' => '08012345678',
                    'photo' => 'base64_mock_image_data',
                ],
            ];
        }

        // Mock failure for other BVNs
        return [
            'success' => false,
            'message' => 'Invalid BVN (sandbox)',
            'data' => null,
        ];
    }

    /**
     * Mock NIN verification (sandbox)
     */
    public function mockNINVerification(string $nin): array
    {
        // No delay - instant response

        // Mock success for NIPs starting with '3'
        if (str_starts_with($nin, '3')) {
            return [
                'success' => true,
                'message' => 'NIN verified (sandbox)',
                'data' => [
                    'nin' => $nin,
                    'firstName' => 'Test',
                    'lastName' => 'Sandbox',
                    'gender' => 'Female',
                    'birthday' => '1995-05-15',
                    'phone' => '08087654321',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid NIN (sandbox)',
            'data' => null,
        ];
    }

    /**
     * Mock CAC verification (sandbox)
     * Note: Real CAC verification not available in EaseID
     */
    public function mockCACVerification(string $cacNumber): array
    {
        // No delay - instant response

        // Mock success for CAC numbers starting with 'RC'
        if (str_starts_with(strtoupper($cacNumber), 'RC')) {
            return [
                'success' => true,
                'message' => 'CAC verified (sandbox - mock only)',
                'data' => [
                    'rc_number' => $cacNumber,
                    'company_name' => 'Sandbox Test Company Ltd',
                    'registration_date' => '2020-01-15',
                    'company_type' => 'Limited Liability Company',
                    'status' => 'Active',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid CAC number (sandbox)',
            'data' => null,
        ];
    }

    /**
     * Get sandbox testing guide
     */
    public static function getTestingGuide(): array
    {
        return [
            'sandbox_info' => [
                'description' => 'Sandbox environment - fully automated, no admin approval needed',
                'features' => [
                    'Instant KYC approval on submission',
                    'Mock BVN/NIN verification',
                    'No real API calls',
                    'No money transactions',
                    'Complete isolation from production',
                ],
            ],
            'bvn_verification' => [
                'success' => 'Use BVN starting with "2" (e.g., 22222222222)',
                'failure' => 'Use any other BVN',
            ],
            'nin_verification' => [
                'success' => 'Use NIN starting with "3" (e.g., 33333333333)',
                'failure' => 'Use any other NIN',
            ],
            'cac_verification' => [
                'note' => 'CAC verification not available in EaseID - sandbox mock only',
                'success' => 'Use CAC starting with "RC" (e.g., RC123456)',
                'failure' => 'Use any other format',
            ],
            'kyc_workflow' => [
                'submit' => 'POST /api/v1/kyc/submit/{section} - Instantly approved in sandbox',
                'manual_approve' => 'POST /api/sandbox/kyc/auto-approve/{section} - Force approval',
                'manual_reject' => 'POST /api/sandbox/kyc/auto-reject/{section} - Force rejection for testing',
            ],
        ];
    }
}
