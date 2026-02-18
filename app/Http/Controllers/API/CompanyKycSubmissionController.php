<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyKycApproval;
use App\Models\CompanyKycHistory;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyKycSubmissionController extends Controller
{
    /**
     * Submit KYC for review.
     * This is called when a company completes the business activation flow.
     */
    public function submitKyc(Request $request)
    {
        $user = auth()->user();

        // Get the company associated with this user
        $company = Company::where('user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found'
            ], 404);
        }

        // Update company fields if provided in request
        $companyUpdateData = [];
        $fillableFields = [
            'bank_name',
            'account_number',
            'account_name',
            'bank_code',
            'directors',
            'shareholders',
            'bvn',
            'nin'
        ];

        foreach ($fillableFields as $field) {
            if ($request->has($field)) {
                $companyUpdateData[$field] = $request->input($field);
            }
        }

        if (!empty($companyUpdateData)) {
            $company->update($companyUpdateData);
        }

        DB::beginTransaction();
        try {
            // Create pending approval records for all sections
            $sections = ['business_info', 'account_info', 'bvn_info', 'board_members', 'documents'];

            $easeIdService = new \App\Services\EaseID\EaseIDKycService();

            // CRITICAL: Check blacklist FIRST before any verification
            $blacklistParams = array_filter([
                'phoneNumber' => $company->phone,
                'bvnNo' => $company->bvn,
                'ninNo' => $company->nin,
            ]);

            if (!empty($blacklistParams)) {
                $blacklistResult = $easeIdService->checkBlacklist($blacklistParams);

                if ($blacklistResult['success'] && $blacklistResult['blacklisted']) {
                    // Auto-reject blacklisted company
                    $company->update([
                        'kyc_status' => 'rejected',
                        'verification_data' => array_merge($company->verification_data ?? [], [
                            'blacklist_hit' => true,
                            'blacklist_hit_time' => $blacklistResult['hit_time'],
                            'blacklist_checked_at' => now()->toDateTimeString()
                        ])
                    ]);

                    // Log rejection
                    CompanyKycHistory::logAction(
                        $company->id,
                        'all',
                        'rejected',
                        null,
                        'Company auto-rejected: Blacklisted in EaseID system'
                    );

                    DB::commit();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'KYC submission rejected. Please contact support.'
                    ], 403);
                }

                // Store blacklist check result (not blacklisted)
                $verificationData = $company->verification_data ?? [];
                $verificationData['blacklist_checked'] = true;
                $verificationData['blacklist_hit'] = false;
                $verificationData['blacklist_checked_at'] = now()->toDateTimeString();
                $company->update(['verification_data' => $verificationData]);
            }

            foreach ($sections as $section) {
                $status = 'pending';
                $note = 'KYC submitted for review';

                // Auto-verification for identity sections (BVN)
                if ($section === 'bvn_info' && $company->bvn) {
                    $bvnResult = $easeIdService->verifyBvn($company->bvn);
                    if ($bvnResult['success']) {
                        $status = 'approved';
                        $note = 'BVN auto-verified via EaseID';

                        // Store full production data
                        $verificationData = $company->verification_data ?? [];
                        $verificationData['bvn_full_payload'] = $bvnResult['data'];
                        $verificationData['bvn_verified_at'] = now()->toDateTimeString();

                        $identityDetails = $company->identity_details ?? [];
                        $identityDetails['bvn'] = $bvnResult['data'];

                        $company->update([
                            'verification_data' => $verificationData,
                            'identity_details' => $identityDetails
                        ]);
                    }
                }

                // Auto-verification for Bank Account (Account Info)
                if ($section === 'account_info' && $company->account_number && $company->bank_code && $company->bvn) {
                    $bankResult = $easeIdService->verifyBankAccount(
                        $company->account_number,
                        $company->bank_code,
                        $company->bvn
                    );

                    if ($bankResult['success']) {
                        // We mark as approved if the bank account matches the BVN/Identity
                        $status = 'approved';
                        $note = 'Bank Account auto-verified via EaseID';

                        $verificationData = $company->verification_data ?? [];
                        $verificationData['bank_account_payload'] = $bankResult['data'];
                        $verificationData['bank_verified_at'] = now()->toDateTimeString();
                        $company->update(['verification_data' => $verificationData]);
                    }
                }

                // Auto-verification for Identity (NIN)
                if ($section === 'board_members' && $company->nin) {
                    // Enhanced NIN Enquiry (Full data storage)
                    $ninResult = $easeIdService->verifyNin($company->nin);
                    if ($ninResult['success']) {
                        // Note: If NIN is in mock mode, it still returns success
                        $status = 'approved';
                        $note = 'Identity (NIN) auto-verified via EaseID';

                        $verificationData = $company->verification_data ?? [];
                        $verificationData['nin_full_payload'] = $ninResult['data'];
                        $verificationData['nin_verified_at'] = now()->toDateTimeString();

                        $identityDetails = $company->identity_details ?? [];
                        $identityDetails['nin'] = $ninResult['data'];

                        $company->update([
                            'verification_data' => $verificationData,
                            'identity_details' => $identityDetails
                        ]);
                    }
                }

                CompanyKycApproval::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'section' => $section
                    ],
                    [
                        'status' => $status,
                        'reviewed_by' => null,
                        'rejection_reason' => null,
                        'reviewed_at' => null
                    ]
                );

                // Log submission
                CompanyKycHistory::logAction(
                    $company->id,
                    $section,
                    $status === 'approved' ? 'approved' : 'submitted',
                    null,
                    $note
                );
            }

            // Update company KYC status
            $isResubmission = $company->kyc_status !== 'pending';
            $company->update([
                'kyc_status' => 'under_review'
            ]);

            // Create admin notification
            AdminNotification::createKycSubmission($company, $isResubmission);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'KYC submitted successfully for admin review',
                'data' => [
                    'kyc_status' => $company->kyc_status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit KYC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get KYC submission status for the authenticated company.
     */
    public function getKycStatus()
    {
        $user = auth()->user();
        $company = Company::where('user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found'
            ], 404);
        }

        $kycSummary = CompanyKycApproval::getApprovalSummary($company->id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'kyc_status' => $company->kyc_status,
                'kyc_summary' => $kycSummary,
                'kyc_documents' => $company->kyc_documents, // Include uploaded documents
                'api_credentials_generated' => $company->api_credentials_generated,
                'is_active' => $company->is_active,
                'company_details' => [
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'address' => $company->address,
                    'rc_number' => $company->rc_number ?? $company->business_registration_number,
                    'bvn' => $company->bvn,
                    'nin' => $company->nin,
                    'bank_name' => $company->bank_name,
                    'account_number' => $company->account_number,
                    'account_name' => $company->account_name,
                    'settlement_bank_name' => $company->settlement_bank_name,
                    'settlement_account_number' => $company->settlement_account_number,
                ]
            ]
        ]);
    }

    /**
     * Resubmit a rejected section.
     */
    public function resubmitSection(Request $request, $section)
    {
        $user = auth()->user();
        $company = Company::where('user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found'
            ], 404);
        }

        $validSections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];
        if (!in_array($section, $validSections)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid section'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Handle document uploads for documents section
            if ($section === 'documents') {
                $kycDocs = $company->kyc_documents ?? [];
                $documentFields = [
                    'cac_certificate',
                    'board_resolution',
                    'company_profile',
                    'status_report',
                    'memart',
                    'logo'
                ];

                foreach ($documentFields as $field) {
                    if ($request->hasFile($field)) {
                        $file = $request->file($field);
                        $path = $file->store('kyc_documents', 'public');
                        $kycDocs[$field] = $path;
                    }
                }

                $company->update(['kyc_documents' => $kycDocs]);
            }

            // Handle board member documents
            if ($section === 'board_members') {
                $kycDocs = $company->kyc_documents ?? [];
                $boardMemberFields = [
                    'board_member_utility_bill',
                    'id_card',
                    'utility_bill'
                ];

                foreach ($boardMemberFields as $field) {
                    if ($request->hasFile($field)) {
                        $file = $request->file($field);
                        $path = $file->store('kyc_documents', 'public');
                        $kycDocs[$field] = $path;
                    }
                }

                $company->update(['kyc_documents' => $kycDocs]);
            }

            // Update other fields based on section
            if ($section === 'business_info') {
                $company->update(array_filter([
                    'name' => $request->input('business_name'),
                    'address' => $request->input('address'),
                    'rc_number' => $request->input('rc_number'),
                    'business_registration_number' => $request->input('rc_number'),
                ]));
            }

            if ($section === 'account_info') {
                $company->update(array_filter([
                    'bank_name' => $request->input('bank_name'),
                    'account_number' => $request->input('account_number'),
                    'account_name' => $request->input('account_name'),
                    'bank_code' => $request->input('bank_code'),
                ]));
            }

            if ($section === 'bvn_info') {
                $company->update(array_filter([
                    'bvn' => $request->input('bvn'),
                ]));
            }

            // Update approval status to pending
            CompanyKycApproval::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'section' => $section
                ],
                [
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'rejection_reason' => null,
                    'reviewed_at' => null
                ]
            );

            // Log resubmission
            CompanyKycHistory::logAction(
                $company->id,
                $section,
                'resubmitted',
                null,
                'Section resubmitted after rejection'
            );

            // Update company status if needed
            if ($company->kyc_status === 'rejected') {
                $company->update(['kyc_status' => 'under_review']);
            }

            // Create admin notification
            AdminNotification::createKycSubmission($company, true);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Section resubmitted successfully',
                'data' => [
                    'kyc_status' => $company->fresh()->kyc_status,
                    'kyc_documents' => $company->fresh()->kyc_documents
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to resubmit section: ' . $e->getMessage()
            ], 500);
        }
    }
}
