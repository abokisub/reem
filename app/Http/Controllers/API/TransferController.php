<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PalmPay\TransferService;
use App\Models\Bank;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    private TransferService $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Get list of supported banks
     */
    public function getBanks()
    {
        $banks = Bank::where('active', true)->orderBy('name', 'asc')->get();
        return response()->json(['status' => 'success', 'data' => $banks]);
    }

    /**
     * Initiate a transfer
     */
    public function initiateTransfer(Request $request)
    {
        $user = auth()->user();
        $company = Company::where('user_id', $user->id)->first();

        if (!$company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'account_number' => 'required|string|size:10',
            'bank_code' => 'required|string',
            'bank_name' => 'nullable|string',
            'account_name' => 'nullable|string',
            'narration' => 'nullable|string|max:100',
            'reference' => 'nullable|string|unique:transactions,external_reference',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        try {
            $transaction = $this->transferService->initiateTransfer($company->id, $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer initiated successfully',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                    'fee' => $transaction->fee,
                    'total_amount' => $transaction->total_amount,
                    'status' => $transaction->status,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get transfer status
     */
    public function getStatus($reference)
    {
        try {
            $status = $this->transferService->queryTransferStatus($reference);
            return response()->json(['status' => 'success', 'data' => $status]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get PalmPay balance (Admin only)
     */
    public function getBalance()
    {
        // Add middleware check if needed, but assuming route is protected
        try {
            $balance = $this->transferService->getBalance();
            return response()->json(['status' => 'success', 'data' => $balance]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
