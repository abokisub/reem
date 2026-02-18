<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\KYC\KycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Company KYC Controller
 * Handles KYC submission and status checking for companies
 */
class KycController extends Controller
{
    protected $kycService;

    public function __construct(KycService $kycService)
    {
        $this->kycService = $kycService;
    }

    /**
     * Get KYC status for authenticated company
     * GET /api/v1/kyc/status
     */
    public function getStatus(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $status = $this->kycService->getKycStatus($companyId);

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit KYC section
     * POST /api/v1/kyc/submit/{section}
     */
    public function submitSection(Request $request, string $section)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
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

            $result = $this->kycService->submitKycSection($companyId, $section, $request->data);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['approval'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify BVN
     * POST /api/v1/kyc/verify-bvn
     */
    public function verifyBVN(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bvn' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = Auth::user()->company_id ?? null;
            $result = $this->kycService->verifyBVN($request->bvn, $companyId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = Auth::user()->company_id ?? null;
            $result = $this->kycService->verifyNIN($request->nin, $companyId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify bank account
     * POST /api/v1/kyc/verify-bank-account
     */
    public function verifyBankAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|size:10',
            'bank_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->kycService->verifyBankAccount(
                $request->account_number,
                $request->bank_code
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
