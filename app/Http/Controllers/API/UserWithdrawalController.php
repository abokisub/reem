<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TransferService;
use App\Traits\ApiResponseTrait;
use App\Models\Beneficiary;
use App\Models\CompanyWallet;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserWithdrawalController extends Controller
{
    use ApiResponseTrait;

    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Initiate withdrawal
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'pin' => 'required|string',
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
            'account_name' => 'nullable|string',
            'narration' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Verify PIN
        if (!$user->pin || !Hash::check($request->pin, $user->pin)) {
            return $this->error('Invalid PIN', 401);
        }

        $companyId = $user->active_company_id;

        // Check balance
        $wallet = CompanyWallet::where('company_id', $companyId)
            ->where('currency', 'NGN')
            ->first();

        if (!$wallet || $wallet->balance < $request->amount) {
            return $this->error('Insufficient balance', 400);
        }

        // Initiate transfer using TransferService
        try {
            $transferData = [
                'amount' => $request->amount,
                'account_number' => $request->account_number,
                'bank_code' => $request->bank_code,
                'account_name' => $request->account_name ?? 'Beneficiary',
                'narration' => $request->narration ?? 'Withdrawal',
                'company_id' => $companyId,
                'user_id' => $user->id,
            ];

            Log::info('Initiating withdrawal', $transferData);

            // Use the transfer service to process the withdrawal
            // Note: The actual implementation depends on your TransferService methods
            // This is a placeholder that you may need to adjust based on your service
            $result = $this->transferService->initiateTransfer($transferData);

            return $this->success($result, 'Withdrawal initiated successfully');
        } catch (\Exception $e) {
            Log::error('Withdrawal failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'amount' => $request->amount,
            ]);
            
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get saved beneficiaries
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function beneficiaries(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $companyId = $user->active_company_id;

        // Get beneficiaries for bank transfers
        $beneficiaries = Beneficiary::where('user_id', $user->id)
            ->where('service_type', 'bank_transfer')
            ->orderBy('is_favorite', 'desc')
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($beneficiaries, 'Beneficiaries retrieved successfully');
    }
}
