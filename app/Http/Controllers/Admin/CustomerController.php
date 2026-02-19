<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = CompanyUser::with('company'); // Eager load the company (merchant)

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by KYC Status
        if ($request->has('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        $customers = $query->latest()->paginate(20);

        // Transform the paginated data
        $customers->getCollection()->transform(function ($customer) {
            return [
                'id' => $customer->id,
                'customer_id' => $customer->uuid,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'merchant_company' => $customer->company ? $customer->company->name : 'N/A',
                'company_id' => $customer->company_id,
                'kyc_status' => 'verified', // Customers under companies are auto-verified
                'status' => $customer->status,
                'joined_date' => $customer->created_at->format('Y-m-d'),
                'created_at' => $customer->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $customer = CompanyUser::with(['company', 'virtualAccounts'])->findOrFail($id);

        // Calculate statistics
        $stats = [
            'total_reserved_accounts' => $customer->virtualAccounts->count(),
            'total_transactions' => 0, // Will be calculated from transactions
            'total_amount_received' => 0, // Will be calculated from transactions
        ];

        // Get transactions for this customer's virtual accounts
        $virtualAccountIds = $customer->virtualAccounts->pluck('id');
        
        $transactions = [];
        if ($virtualAccountIds->isNotEmpty()) {
            $transactionStats = DB::table('transactions')
                ->whereIn('virtual_account_id', $virtualAccountIds)
                ->where('status', 'success')
                ->select(
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(amount) as total_amount')
                )
                ->first();
            
            $stats['total_transactions'] = $transactionStats->total_count ?? 0;
            $stats['total_amount_received'] = $transactionStats->total_amount ?? 0;
            
            // Get recent transactions for display
            $transactions = DB::table('transactions')
                ->whereIn('virtual_account_id', $virtualAccountIds)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'reference' => $transaction->reference,
                        'amount' => $transaction->amount,
                        'fee' => $transaction->fee ?? 0,
                        'net_amount' => $transaction->net_amount ?? $transaction->amount,
                        'status' => $transaction->status,
                        'type' => $transaction->type,
                        'category' => $transaction->category,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at,
                        'date' => \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d H:i:s'),
                    ];
                });
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'customer' => $customer,
                'stats' => $stats,
                'virtual_accounts' => $customer->virtualAccounts->map(function ($va) {
                    return [
                        'id' => $va->id,
                        'uuid' => $va->uuid,
                        'account_number' => $va->account_number,
                        'account_name' => $va->account_name,
                        'bank_name' => $va->bank_name,
                        'bank_code' => $va->bank_code,
                        'account_type' => $va->account_type,
                        'status' => $va->status,
                        'created_at' => $va->created_at,
                    ];
                }),
                'transactions' => $transactions,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $customer = CompanyUser::findOrFail($id);

        try {
            DB::beginTransaction();

            // Delete related virtual accounts first?
            // Assuming cascade delete or handle manually if needed.
            // For now, just delete the user.

            $customer->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer transactions
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactions($id)
    {
        $customer = CompanyUser::findOrFail($id);
        
        // Get all virtual account IDs for this customer
        $virtualAccountIds = $customer->virtualAccounts()->pluck('id');
        
        if ($virtualAccountIds->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'transactions' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 0,
                        'total_records' => 0
                    ]
                ]
            ]);
        }

        // Get transactions for these virtual accounts
        $transactions = DB::table('transactions')
            ->whereIn('virtual_account_id', $virtualAccountIds)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'total_records' => $transactions->total(),
                    'per_page' => $transactions->perPage()
                ]
            ]
        ]);
    }
}
