<?php

namespace App\Http\Controllers\API\Gateway;

use App\Http\Controllers\Controller;
use App\Services\EaseID\EaseIDKycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * KYC Controller for Gateway API
 * 
 * Allows merchants to verify identity documents (BVN, NIN, etc.)
 */
class KycController extends Controller
{
    private EaseIDKycService $kycService;

    public function __construct()
    {
        $this->kycService = new EaseIDKycService();
    }

    /**
     * Verify BVN
     * 
     * POST /api/gateway/kyc/verify/bvn
     */
    public function verifyBvn(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_enhanced_bvn')) {
            return response()->json(['success' => false, 'message' => 'Enhanced BVN verification is currently locked.'], 403);
        }

        $result = $this->kycService->verifyBvn($request->input('bvn'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * Verify NIN
     * 
     * POST /api/gateway/kyc/verify/nin
     */
    public function verifyNin(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_enhanced_nin')) {
            return response()->json(['success' => false, 'message' => 'Enhanced NIN verification is currently locked.'], 403);
        }

        $result = $this->kycService->verifyNin($request->input('nin'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * Verify Bank Account
     * 
     * POST /api/gateway/kyc/verify/bank-account
     */
    public function verifyBankAccount(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_bank_verify')) {
            return response()->json(['success' => false, 'message' => 'Bank verification service is currently locked.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'accountNumber' => 'required|string|size:10',
            'bankCode' => 'required|string|size:3',
            'bvn' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->kycService->verifyBankAccount(
            $request->input('accountNumber'),
            $request->input('bankCode'),
            $request->input('bvn')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * Check Blacklist
     * 
     * POST /api/gateway/kyc/blacklist/check
     */
    public function checkBlacklist(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_blacklist')) {
            return response()->json(['success' => false, 'message' => 'Blacklist verification service is currently locked.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'nullable|string|max:15',
            'bvnNo' => 'nullable|string|size:11',
            'ninNo' => 'nullable|string|size:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // At least one parameter required
        if (!$request->has('phoneNumber') && !$request->has('bvnNo') && !$request->has('ninNo')) {
            return response()->json([
                'success' => false,
                'message' => 'At least one parameter (phoneNumber, bvnNo, ninNo) is required'
            ], 422);
        }

        $params = array_filter([
            'phoneNumber' => $request->input('phoneNumber'),
            'bvnNo' => $request->input('bvnNo'),
            'ninNo' => $request->input('ninNo'),
        ]);

        $result = $this->kycService->checkBlacklist($params);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'blacklisted' => $result['blacklisted'],
                'hitTime' => $result['hit_time']
            ]
        ]);
    }

    /**
     * Compare Faces
     * 
     * POST /api/gateway/kyc/face/compare
     */
    public function compareFaces(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_face_compare')) {
            return response()->json(['success' => false, 'message' => 'Face comparison service is currently locked.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'sourceImage' => 'required|string',
            'targetImage' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->kycService->compareFaces(
            $request->input('sourceImage'),
            $request->input('targetImage')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'similarity' => $result['similarity'],
                'isMatch' => $result['is_match']
            ]
        ]);
    }

    /**
     * Get Credit Score
     * 
     * POST /api/gateway/kyc/credit-score
     */
    public function getCreditScore(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_credit_score')) {
            return response()->json(['success' => false, 'message' => 'Credit score service is currently locked.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'mobileNo' => 'required|string|max:15',
            'idNumber' => 'required|string|size:11',
            'deviceInfo' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->kycService->getCreditScore(
            $request->input('mobileNo'),
            $request->input('idNumber'),
            $request->input('deviceInfo', [])
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'creditScore' => $result['credit_score'],
                'creditScoreV3' => $result['credit_score_v3']
            ]
        ]);
    }

    /**
     * Verify BVN Basic (Matching)
     * 
     * POST /api/gateway/kyc/verify/bvn-basic
     */
    public function verifyBvnBasic(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_basic_bvn')) {
            return response()->json(['success' => false, 'message' => 'Basic BVN verification is currently locked.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'bvn' => 'required|string|size:11',
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'gender' => 'nullable|in:Male,Female',
            'birthday' => 'nullable|date',
            'phoneNumber' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $optionalParams = array_filter([
            'middleName' => $request->input('middleName'),
            'gender' => $request->input('gender'),
            'birthday' => $request->input('birthday'),
            'phoneNumber' => $request->input('phoneNumber'),
        ]);

        $result = $this->kycService->verifyBvnBasic(
            $request->input('bvn'),
            $request->input('firstName'),
            $request->input('lastName'),
            $optionalParams
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * Verify NIN Basic (Matching)
     * 
     * POST /api/gateway/kyc/verify/nin-basic
     */
    public function verifyNinBasic(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_basic_nin')) {
            return response()->json(['success' => false, 'message' => 'Basic NIN verification is currently locked.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nin' => 'required|string|size:11',
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'gender' => 'nullable|in:Male,Female',
            'birthday' => 'nullable|date',
            'phoneNumber' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $optionalParams = array_filter([
            'middleName' => $request->input('middleName'),
            'gender' => $request->input('gender'),
            'birthday' => $request->input('birthday'),
            'phoneNumber' => $request->input('phoneNumber'),
        ]);

        $result = $this->kycService->verifyNinBasic(
            $request->input('nin'),
            $request->input('firstName'),
            $request->input('lastName'),
            $optionalParams
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * Initiate Liveness Detection
     * 
     * POST /api/gateway/kyc/liveness/initiate
     */
    public function initiateLiveness(Request $request)
    {
        if (\App\Http\Controllers\API\ServiceLockController::isLocked('kyc_liveness')) {
            return response()->json(['success' => false, 'message' => 'Liveness detection service is currently locked.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'userId' => 'required|string|max:100',
            'notifyUrl' => 'required|url',
            'callbackUrl' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->kycService->initiateLiveness(
            $request->input('userId'),
            $request->input('notifyUrl'),
            $request->input('callbackUrl')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'verifyUrl' => $result['url'],
                'transactionId' => $result['transactionId']
            ]
        ]);
    }
}
