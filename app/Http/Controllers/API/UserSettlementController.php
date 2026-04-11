<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\SettlementQueue;
use App\Traits\ApiResponseTrait;

class UserSettlementController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get user's settlements list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $companyId = $user->active_company_id;

        // Get settlements for this company
        $settlements = SettlementQueue::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return $this->success($settlements, 'Settlements retrieved successfully');
    }

    /**
     * Get settlement details
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $companyId = $user->active_company_id;

        $settlement = SettlementQueue::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$settlement) {
            return $this->error('Settlement not found', 404);
        }

        return $this->success($settlement, 'Settlement details retrieved successfully');
    }

    /**
     * Get transactions for a settlement
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactions(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $companyId = $user->active_company_id;

        // Verify settlement belongs to this company
        $settlement = SettlementQueue::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$settlement) {
            return $this->error('Settlement not found', 404);
        }

        // Get transactions for this settlement
        // Try multiple ways to find related transactions
        $transactions = Transaction::where(function ($query) use ($settlement, $id) {
            $query->where('settlement_id', $id);
            
            // If settlement has a batch reference, also search by that
            if ($settlement->batch_reference) {
                $query->orWhere('settlement_batch_id', $settlement->batch_reference);
            }
            
            // If settlement has a transaction_id, include that
            if ($settlement->transaction_id) {
                $query->orWhere('id', $settlement->transaction_id);
            }
        })
        ->where('company_id', $companyId)
        ->orderBy('created_at', 'desc')
        ->get();

        return $this->success($transactions, 'Settlement transactions retrieved successfully');
    }
}
