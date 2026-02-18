<?php

namespace App\Http\Controllers\API\Gateway;

use App\Http\Controllers\Controller;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Virtual Account Controller
 * 
 * Handles virtual account creation and management for gateway customers
 */
class VirtualAccountController extends Controller
{
    private VirtualAccountService $virtualAccountService;

    public function __construct()
    {
        $this->virtualAccountService = new VirtualAccountService();
    }

    /**
     * Create a virtual account
     * 
     * POST /api/gateway/virtual-accounts
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Enforce Phase 1 Lock
        \App\Services\PhaseGate::enforce(\App\Services\PhaseGate::PHASE_1_VIRTUAL_ACCOUNTS);

        if (\App\Http\Controllers\API\ServiceLockController::isLocked('virtual_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Virtual Account generation is currently disabled.'
            ], 403);
        }

        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'userId' => 'required|string|max:255',
                'customerName' => 'required|string|max:255',
                'customerEmail' => 'nullable|email|max:255',
                'customerPhone' => 'nullable|string|max:20',
                'bvn' => 'nullable|string|size:11',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = $request->get('company_id');
            $userId = $request->input('userId');

            // Check if virtual account already exists
            $existing = $this->virtualAccountService->getByCompanyAndUser($companyId, $userId);

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Virtual account already exists',
                    'data' => [
                        'accountId' => $existing->account_id,
                        'accountNumber' => $existing->palmpay_account_number,
                        'accountName' => $existing->palmpay_account_name,
                        'bankName' => $existing->palmpay_bank_name,
                        'userId' => $existing->user_id,
                        'status' => $existing->status,
                    ]
                ], 200);
            }

            // Create virtual account
            $virtualAccount = $this->virtualAccountService->createVirtualAccount(
                $companyId,
                $userId,
                [
                    'name' => $request->input('customerName'),
                    'email' => $request->input('customerEmail'),
                    'phone' => $request->input('customerPhone'),
                    'bvn' => $request->input('bvn'),
                    'identity_type' => $request->input('identityType', 'personal'),
                    'license_number' => $request->input('licenseNumber', $request->input('bvn')),
                ]
            );

            Log::info('Virtual Account Created via API', [
                'company_id' => $companyId,
                'account_id' => $virtualAccount->account_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Virtual account created successfully',
                'data' => [
                    'accountId' => $virtualAccount->account_id,
                    'accountNumber' => $virtualAccount->palmpay_account_number,
                    'accountName' => $virtualAccount->palmpay_account_name,
                    'bankName' => $virtualAccount->palmpay_bank_name,
                    'userId' => $virtualAccount->user_id,
                    'status' => $virtualAccount->status,
                    'createdAt' => $virtualAccount->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Helpful message for permission errors
            if (strpos($message, 'OPEN_GW_000012') !== false || strpos($message, 'insufficient permissions') !== false) {
                $message = 'PalmPay Permission Error: Please ensure your server IP is whitelisted on the PalmPay Merchant Platform and the Virtual Account product is activated.';
            }

            Log::error('Virtual Account Creation Failed', [
                'company_id' => $request->get('company_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create virtual account',
                'error' => $message
            ], 500);
        }
    }

    /**
     * Get virtual account details
     * 
     * GET /api/gateway/virtual-accounts/{userId}
     * 
     * @param Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $userId)
    {
        try {
            $companyId = $request->get('company_id');

            $virtualAccount = $this->virtualAccountService->getByCompanyAndUser($companyId, $userId);

            if (!$virtualAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Virtual account not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'accountId' => $virtualAccount->account_id,
                    'accountNumber' => $virtualAccount->palmpay_account_number,
                    'accountName' => $virtualAccount->palmpay_account_name,
                    'bankName' => $virtualAccount->palmpay_bank_name,
                    'userId' => $virtualAccount->user_id,
                    'customerName' => $virtualAccount->customer_name,
                    'customerEmail' => $virtualAccount->customer_email,
                    'customerPhone' => $virtualAccount->customer_phone,
                    'status' => $virtualAccount->status,
                    'createdAt' => $virtualAccount->created_at->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Fetch Virtual Account', [
                'company_id' => $request->get('company_id'),
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch virtual account'
            ], 500);
        }
    }

    /**
     * Update virtual account status
     * 
     * PUT /api/gateway/virtual-accounts/{userId}
     * 
     * @param Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $userId)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:Enabled,Disabled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = $request->get('company_id');

            // Get virtual account
            $virtualAccount = $this->virtualAccountService->getByCompanyAndUser($companyId, $userId);

            if (!$virtualAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Virtual account not found'
                ], 404);
            }

            // Update status via PalmPay
            $result = $this->virtualAccountService->updateVirtualAccountStatus(
                $virtualAccount->palmpay_account_number,
                $request->input('status')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            Log::info('Virtual Account Status Updated via API', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'status' => $request->input('status')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Virtual account status updated successfully',
                'data' => [
                    'accountId' => $virtualAccount->account_id,
                    'accountNumber' => $virtualAccount->palmpay_account_number,
                    'status' => $request->input('status'),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Update Virtual Account Status', [
                'company_id' => $request->get('company_id'),
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update virtual account status'
            ], 500);
        }
    }

    /**
     * Delete virtual account
     * 
     * DELETE /api/gateway/virtual-accounts/{userId}
     * 
     * @param Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $userId)
    {
        try {
            $companyId = $request->get('company_id');

            // Get virtual account
            $virtualAccount = $this->virtualAccountService->getByCompanyAndUser($companyId, $userId);

            if (!$virtualAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Virtual account not found'
                ], 404);
            }

            // Delete via PalmPay
            $result = $this->virtualAccountService->deleteVirtualAccount(
                $virtualAccount->palmpay_account_number
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            // Update local record
            $virtualAccount->update([
                'status' => 'deleted',
                'deleted_at' => now()
            ]);

            Log::info('Virtual Account Deleted via API', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'account_number' => $virtualAccount->palmpay_account_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Virtual account deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Delete Virtual Account', [
                'company_id' => $request->get('company_id'),
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete virtual account'
            ], 500);
        }
    }

    /**
     * Query pay-in transactions
     * 
     * GET /api/gateway/virtual-accounts/{userId}/pay-ins
     * 
     * @param Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryPayIn(Request $request, string $userId)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'orderNo' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = $request->get('company_id');

            // Verify virtual account exists
            $virtualAccount = $this->virtualAccountService->getByCompanyAndUser($companyId, $userId);

            if (!$virtualAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Virtual account not found'
                ], 404);
            }

            // Query pay-in order
            $result = $this->virtualAccountService->queryPayInOrder($request->input('orderNo'));

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Query Pay-In Order', [
                'company_id' => $request->get('company_id'),
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to query pay-in order'
            ], 500);
        }
    }

    /**
     * Bulk query pay-in transactions
     * 
     * POST /api/gateway/virtual-accounts/pay-ins/bulk-query
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkQueryPayIn(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'orderNos' => 'required|array|min:1|max:100',
                'orderNos.*' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Bulk query pay-in orders
            $result = $this->virtualAccountService->bulkQueryPayInOrders($request->input('orderNos'));

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to Bulk Query Pay-In Orders', [
                'company_id' => $request->get('company_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk query pay-in orders'
            ], 500);
        }
    }
}