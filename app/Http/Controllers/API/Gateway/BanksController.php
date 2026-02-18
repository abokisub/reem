<?php

namespace App\Http\Controllers\API\Gateway;

use App\Http\Controllers\Controller;
use App\Services\PalmPay\AccountVerificationService;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Banks Controller
 * 
 * Handles bank-related operations
 */
class BanksController extends Controller
{
    private AccountVerificationService $accountVerificationService;

    public function __construct()
    {
        $this->accountVerificationService = new AccountVerificationService();
    }

    /**
     * Get list of supported banks
     * 
     * GET /api/gateway/banks
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $banks = Bank::where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'supports_transfers', 'supports_account_verification']);

            return response()->json([
                'success' => true,
                'data' => $banks->map(function ($bank) {
                    return [
                        'bankCode' => $bank->code,
                        'bankName' => $bank->name,
                        'supportsTransfers' => $bank->supports_transfers,
                        'supportsVerification' => $bank->supports_account_verification,
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Fetch Banks', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banks'
            ], 500);
        }
    }

    /**
     * Verify a bank account
     * 
     * POST /api/gateway/banks/verify
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'accountNumber' => 'required|string|size:10',
                'bankCode' => 'required|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify account
            $result = $this->accountVerificationService->verifyAccount(
                $request->input('accountNumber'),
                $request->input('bankCode')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Account verification failed'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'accountNumber' => $result['account_number'],
                    'accountName' => $result['account_name'],
                    'bankCode' => $result['bank_code'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Account Verification Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Account verification failed'
            ], 500);
        }
    }

    /**
     * Verify a PalmPay account
     * 
     * POST /api/gateway/palmpay/verify
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPalmPayAccount(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'accountNumber' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify PalmPay account
            $result = $this->accountVerificationService->verifyPalmPayAccount(
                $request->input('accountNumber')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'PalmPay account verification failed'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'accountNumber' => $result['account_number'],
                    'accountName' => $result['account_name'],
                    'available' => $result['available'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('PalmPay Account Verification Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PalmPay account verification failed'
            ], 500);
        }
    }
}