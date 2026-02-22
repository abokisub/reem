<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyKycApproval;
use App\Models\CompanyKycHistory;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class CompanyKycController extends Controller
{
    /**
     * Get all companies with KYC status.
     */
    public function index(Request $request)
    {
        $query = Company::with(['user', 'wallet']);

        // Exclude admin company (email: admin@pointwave.com)
        $query->where('email', '!=', 'admin@pointwave.com');

        // Filter by KYC status
        if ($request->has('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        // Filter by search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('business_registration_number', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('created_at', 'desc')->paginate(20);

        // Add KYC approval summary for each company
        /** @var \Illuminate\Pagination\LengthAwarePaginator $companies */
        $companies->through(function ($company) {
            $company->kyc_summary = CompanyKycApproval::getApprovalSummary($company->id);
            return $company;
        });

        return response()->json([
            'status' => 'success',
            'data' => $companies
        ]);
    }

    /**
     * Get companies pending KYC review.
     */
    public function pendingKyc()
    {
        $companies = Company::whereIn('kyc_status', ['pending', 'under_review'])
            ->where('email', '!=', 'admin@pointwave.com') // Exclude admin company
            ->with(['user', 'wallet'])
            ->orderBy('created_at', 'asc')
            ->get();

        $companies->transform(function ($company) {
            $company->kyc_summary = CompanyKycApproval::getApprovalSummary($company->id);
            $company->pending_sections = collect($company->kyc_summary)
                ->filter(fn($s) => $s['status'] === 'pending')
                ->keys()
                ->toArray();
            return $company;
        });

        return response()->json([
            'status' => 'success',
            'data' => $companies
        ]);
    }

    /**
     * Get company details with full KYC information.
     */
    public function show($id)
    {
        $company = Company::with(['user', 'wallet', 'virtualAccounts'])
            ->findOrFail($id);

        $company->kyc_summary = CompanyKycApproval::getApprovalSummary($id);
        $company->kyc_history = CompanyKycHistory::getCompanyHistory($id);

        // Get transaction statistics
        $company->transaction_stats = [
            'total_transactions' => 0, // TODO: Implement when transaction table is ready
            'total_revenue' => 0,
            'successful_transactions' => 0,
            'failed_transactions' => 0,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $company
        ]);
    }

    /**
     * Review a specific KYC section (approve/reject).
     */
    public function reviewSection(Request $request, $companyId, $section)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:1000'
        ]);

        $company = Company::findOrFail($companyId);

        // Validate section
        $validSections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];
        if (!in_array($section, $validSections)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid section'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Update or create approval record
            $approval = CompanyKycApproval::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'section' => $section
                ],
                [
                    'status' => $request->status,
                    'reviewed_by' => auth()->id(),
                    'rejection_reason' => $request->rejection_reason,
                    'reviewed_at' => now()
                ]
            );

            // Log history
            CompanyKycHistory::logAction(
                $companyId,
                $section,
                $request->status,
                auth()->id(),
                $request->rejection_reason
            );

            // Update company KYC status
            if ($request->status === 'rejected') {
                $company->update(['kyc_status' => 'under_review']);

                // TODO: Send rejection email to company
                // Mail::to($company->email)->send(new KycSectionRejected($company, $section, $request->rejection_reason));
            } else {
                // Check if all sections are now approved
                if (CompanyKycApproval::allSectionsApproved($companyId)) {
                    $this->approveCompany($company);
                } else {
                    $company->update(['kyc_status' => 'under_review']);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => ucfirst($section) . ' ' . $request->status . ' successfully',
                'data' => [
                    'approval' => $approval,
                    'company_kyc_status' => $company->fresh()->kyc_status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to review section: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve company and generate API credentials.
     */
    private function approveCompany(Company $company)
    {
        // Generate and save API credentials
        $credentials = Company::generateApiKeys();
        $company->update($credentials);

        $company->update([
            'kyc_status' => 'approved',
            'kyc_reviewed_at' => now(),
            'kyc_reviewed_by' => auth()->id(),
            'api_credentials_generated' => true,
            'is_active' => true
        ]);

        // Log history
        CompanyKycHistory::logAction(
            $company->id,
            'all',
            'approved',
            auth()->id(),
            'All sections approved - API credentials generated'
        );

        // TODO: Send approval email with API credentials
        // Mail::to($company->email)->send(new CompanyKycApproved($company, $apiKey, $secretKey));

        return $company;
    }

    /**
     * Suspend/Activate company.
     */
    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
            'reason' => 'required_if:is_active,false|nullable|string|max:500'
        ]);

        $company = Company::findOrFail($id);

        // Auto-generate PalmPay virtual account when company is activated
        if ($request->is_active && !$company->palmpay_account_number) {
            try {
                $virtualAccountService = new \App\Services\PalmPay\VirtualAccountService();
                
                \Log::info('Creating master wallet for company activation', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'has_director_bvn' => !empty($company->director_bvn),
                    'has_director_nin' => !empty($company->director_nin),
                    'has_rc_number' => !empty($company->business_registration_number),
                ]);
                
                // Create master wallet for the company
                $virtualAccount = $virtualAccountService->createVirtualAccount(
                    $company->id,
                    'company_master_' . $company->id, // Unique user_id for company master wallet
                    [
                        'name' => $company->name,
                        'email' => $company->email,
                        'phone' => $company->phone,
                        // Uses director BVN automatically
                    ],
                    '100033', // PalmPay bank code
                    null // No company_user_id for master wallet
                );

                // Update company with PalmPay master wallet details
                $company->palmpay_account_number = $virtualAccount->account_number;
                $company->palmpay_account_name = $virtualAccount->account_name;
                $company->palmpay_bank_name = 'PalmPay';
                $company->palmpay_bank_code = '100033';

                \Log::info('Company master wallet created successfully', [
                    'company_id' => $company->id,
                    'account_number' => $virtualAccount->account_number,
                    'account_name' => $virtualAccount->account_name,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create company master wallet', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't fail activation if virtual account creation fails
            }
        }

        $company->update([
            'is_active' => $request->is_active,
            'kyc_status' => $request->is_active ? 'approved' : 'suspended'
        ]);

        // Log history
        CompanyKycHistory::logAction(
            $company->id,
            'all',
            $request->is_active ? 'activated' : 'suspended',
            auth()->id(),
            $request->reason
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Company ' . ($request->is_active ? 'activated' : 'suspended') . ' successfully',
            'data' => $company
        ]);
    }

    /**
     * Regenerate API credentials.
     */
    public function regenerateCredentials($id)
    {
        $company = Company::findOrFail($id);

        if ($company->kyc_status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Company KYC must be approved first'
            ], 400);
        }

        $credentials = Company::generateApiKeys();

        $company->update([
            'business_id' => $credentials['business_id'],
            'api_key' => $credentials['api_key'],
            'secret_key' => $credentials['secret_key'],
        ]);

        // Log history
        CompanyKycHistory::logAction(
            $company->id,
            'all',
            'credentials_regenerated',
            auth()->id(),
            'API credentials regenerated by admin'
        );

        // TODO: Send new credentials via email
        // Mail::to($company->email)->send(new ApiCredentialsRegenerated($company, $apiKey, $secretKey));

        return response()->json([
            'status' => 'success',
            'message' => 'API credentials regenerated successfully',
            'data' => [
                'business_id' => $credentials['business_id'],
                'api_key' => $credentials['api_key'],
                'secret_key' => $credentials['secret_key'] // Only return once
            ]
        ]);
    }

    /**
     * Get KYC statistics.
     */
    public function statistics()
    {
        // Exclude admin company from all counts
        $stats = [
            'total_companies' => Company::where('email', '!=', 'admin@pointwave.com')->count(),
            'pending_kyc' => Company::where('email', '!=', 'admin@pointwave.com')->where('kyc_status', 'pending')->count(),
            'under_review' => Company::where('email', '!=', 'admin@pointwave.com')->where('kyc_status', 'under_review')->count(),
            'approved' => Company::where('email', '!=', 'admin@pointwave.com')->where('kyc_status', 'verified')->count(), // Changed from 'approved' to 'verified'
            'rejected' => Company::where('email', '!=', 'admin@pointwave.com')->where('kyc_status', 'rejected')->count(),
            'suspended' => Company::where('email', '!=', 'admin@pointwave.com')->where('status', 'suspended')->count(), // Changed to check 'status' instead of 'kyc_status'
            'active_companies' => Company::where('email', '!=', 'admin@pointwave.com')->where('is_active', true)->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
    /**
     * Delete a company and all related records.
     */
    public function destroy($id)
    {
        if ($id == 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'System Master company cannot be deleted'
            ], 403);
        }

        $company = Company::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete related records
            $company->wallets()->delete();
            $company->virtualAccounts()->delete();
            CompanyKycApproval::where('company_id', $id)->delete();
            CompanyKycHistory::where('company_id', $id)->delete();

            // Delete the company (Soft delete if using SoftDeletes trait, or forceDelete if cleaning up test data)
            // For production readiness audit, we use delete() which respects SoftDeletes if active.
            $company->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Company deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete company: ' . $e->getMessage()
            ], 500);
        }
    }
}
