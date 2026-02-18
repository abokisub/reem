<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyKycApproval;
use App\Services\KYC\KycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin KYC Controller
 * Handles KYC approval/rejection by administrators
 */
class KycController extends Controller
{
    protected $kycService;

    public function __construct(KycService $kycService)
    {
        $this->kycService = $kycService;
    }

    /**
     * List pending KYC submissions
     * GET /api/admin/kyc/pending
     */
    public function pending(Request $request)
    {
        $query = CompanyKycApproval::with(['company', 'reviewer'])
            ->where('status', 'pending');

        if ($request->has('section')) {
            $query->where('section', $request->section);
        }

        $pendingApprovals = $query->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $pendingApprovals,
        ]);
    }

    /**
     * List all KYC submissions with filters
     * GET /api/admin/kyc/submissions
     */
    public function index(Request $request)
    {
        $query = CompanyKycApproval::with(['company', 'reviewer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('section')) {
            $query->where('section', $request->section);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $approvals = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    /**
     * Get KYC details for a specific company
     * GET /api/admin/kyc/company/{companyId}
     */
    public function getCompanyKyc($companyId)
    {
        try {
            $status = $this->kycService->getKycStatus($companyId);

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Approve KYC section
     * POST /api/admin/kyc/approve/{companyId}/{section}
     */
    public function approve(Request $request, $companyId, $section)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->kycService->approveSection(
                $companyId,
                $section,
                Auth::id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['approval'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject KYC section
     * POST /api/admin/kyc/reject/{companyId}/{section}
     */
    public function reject(Request $request, $companyId, $section)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->kycService->rejectSection(
                $companyId,
                $section,
                Auth::id(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['approval'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get KYC statistics
     * GET /api/admin/kyc/stats
     */
    public function stats()
    {
        $stats = [
            'total_companies' => Company::count(),
            'kyc_pending' => Company::where('kyc_status', 'pending')->count(),
            'kyc_under_review' => Company::where('kyc_status', 'under_review')->count(),
            'kyc_approved' => Company::where('kyc_status', 'approved')->count(),
            'kyc_rejected' => Company::where('kyc_status', 'rejected')->count(),
            'pending_sections' => CompanyKycApproval::where('status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
