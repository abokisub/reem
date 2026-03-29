<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DataManagementController extends Controller
{
    /**
     * Verify token and check if user is admin
     */
    private function verifyAdmin($id)
    {
        $adminId = $this->verifytoken($id);
        if (!$adminId)
            return false;

        $user = DB::table('users')->where(['id' => $adminId, 'status' => 'active'])->first();
        return ($user && in_array(strtolower($user->type), ['admin', 'ADMIN']));
    }

    /**
     * Get inactive virtual accounts based on months of inactivity
     */
    public function getInactiveVirtualAccounts(Request $request, $id)
    {
        if (!$this->verifyAdmin($id)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $months = (int) $request->input('months', 6);
        $dateThreshold = Carbon::now()->subMonths($months);

        $query = DB::table('virtual_accounts')
            ->leftJoin('companies', 'virtual_accounts.company_id', '=', 'companies.id')
            ->where('virtual_accounts.is_master', 0)
            ->where(function ($q) use ($dateThreshold) {
                $q->whereNotExists(function ($sub) use ($dateThreshold) {
                    $sub->select(DB::raw(1))
                        ->from('end_user_transactions')
                        ->whereRaw('end_user_transactions.virtual_account_id = virtual_accounts.id')
                        ->where('end_user_transactions.paid_at', '>', $dateThreshold);
                })
                    ->where('virtual_accounts.created_at', '<', $dateThreshold);
            });

        $accounts = $query->select(
            'virtual_accounts.id',
            'virtual_accounts.account_id',
            'virtual_accounts.palmpay_account_number',
            'virtual_accounts.customer_name',
            'virtual_accounts.created_at',
            'companies.name as business_name'
        )
            ->orderBy('virtual_accounts.created_at', 'asc')
            ->paginate($request->input('perPage', 20));

        return response()->json([
            'status' => 'success',
            'data' => $accounts
        ]);
    }

    /**
     * Batch delete virtual accounts
     */
    public function deleteVirtualAccounts(Request $request, $id)
    {
        if (!$this->verifyAdmin($id)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'No accounts selected'], 400);
        }

        // Additional safeguard: don't delete master accounts even if ID is passed
        DB::table('virtual_accounts')
            ->whereIn('id', $ids)
            ->where('is_master', 0)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Virtual accounts deleted successfully'
        ]);
    }

    /**
     * Get old transactions (permanently stored)
     */
    public function getOldTransactions(Request $request, $id)
    {
        if (!$this->verifyAdmin($id)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $months = (int) $request->input('months', 6);
        $dateThreshold = Carbon::now()->subMonths($months);

        $transactions = DB::table('end_user_transactions')
            ->leftJoin('companies', 'end_user_transactions.company_id', '=', 'companies.id')
            ->where('end_user_transactions.paid_at', '<', $dateThreshold)
            ->select(
                'end_user_transactions.id',
                'end_user_transactions.transaction_reference',
                'end_user_transactions.amount',
                'end_user_transactions.fee',
                'end_user_transactions.net_amount',
                'end_user_transactions.paid_at',
                'companies.name as business_name'
            )
            ->orderBy('end_user_transactions.paid_at', 'asc')
            ->paginate($request->input('perPage', 20));

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    /**
     * Batch delete transactions permanently
     */
    public function deleteTransactions(Request $request, $id)
    {
        if (!$this->verifyAdmin($id)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'No transactions selected'], 400);
        }

        DB::table('end_user_transactions')
            ->whereIn('id', $ids)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Transactions permanently deleted'
        ]);
    }
}
