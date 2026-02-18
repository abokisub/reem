<?php

namespace App\Http\Controllers\API\Sandbox;

use App\Http\Controllers\Controller;
use App\Services\KYC\SandboxKycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Sandbox KYC Controller
 * Provides sandbox-only endpoints for testing KYC flows
 */
class KycController extends Controller
{
    protected $sandboxKycService;

    public function __construct(SandboxKycService $sandboxKycService)
    {
        $this->sandboxKycService = $sandboxKycService;
    }

    /**
     * Auto-approve KYC section (sandbox only)
     * POST /api/sandbox/kyc/auto-approve/{section}
     */
    public function autoApprove(Request $request, $section)
    {
        if (!SandboxKycService::isSandbox()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in sandbox mode',
            ], 403);
        }

        try {
            $companyId = Auth::user()->company_id;

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $result = $this->sandboxKycService->autoApproveSection($companyId, $section);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-reject KYC section (sandbox only)
     * POST /api/sandbox/kyc/auto-reject/{section}
     */
    public function autoReject(Request $request, $section)
    {
        if (!SandboxKycService::isSandbox()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in sandbox mode',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = Auth::user()->company_id;

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $reason = $request->reason ?? 'Sandbox test rejection';
            $result = $this->sandboxKycService->autoRejectSection($companyId, $section, $reason);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sandbox testing guide
     * GET /api/sandbox/kyc/guide
     */
    public function guide()
    {
        return response()->json([
            'success' => true,
            'data' => SandboxKycService::getTestingGuide(),
        ]);
    }

    /**
     * Mock BVN verification (sandbox)
     * POST /api/sandbox/kyc/mock-verify-bvn
     */
    public function mockVerifyBVN(Request $request)
    {
        if (!SandboxKycService::isSandbox()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in sandbox mode',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'bvn' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->sandboxKycService->mockBVNVerification($request->bvn);
        return response()->json($result);
    }

    /**
     * Mock NIN verification (sandbox)
     * POST /api/sandbox/kyc/mock-verify-nin
     */
    public function mockVerifyNIN(Request $request)
    {
        if (!SandboxKycService::isSandbox()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in sandbox mode',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nin' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->sandboxKycService->mockNINVerification($request->nin);
        return response()->json($result);
    }

    /**
     * Mock CAC verification (sandbox)
     * POST /api/sandbox/kyc/mock-verify-cac
     */
    public function mockVerifyCAC(Request $request)
    {
        if (!SandboxKycService::isSandbox()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in sandbox mode',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'cac_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->sandboxKycService->mockCACVerification($request->cac_number);
        return response()->json($result);
    }
}
