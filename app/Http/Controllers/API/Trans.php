<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\VirtualAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Trans extends Controller
{

    public function UserTrans(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);
                $database_name = strtolower($request->database_name);
                if ($database_name === 'bank_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where('username', $user->username)->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('account_name', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('account_number', 'LIKE', "%$search%")->orWhere('bank_name', 'LIKE', "%$search%")->orWhere('bank_code', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('account_name', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('account_number', 'LIKE', "%$search%")->orWhere('bank_name', 'LIKE', "%$search%")->orWhere('bank_code', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                } else if ($database_name == 'cable_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where('username', $user->username)->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%")->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%")->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                } elseif ($database_name == 'bill_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where('username', $user->username)->Where(function ($query) use ($search) {
                                    $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%")->orWhere('token', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%")->orWhere('token', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {

                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                } else if ($database_name == 'bulksms_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where('username', $user->username)->Where(function ($query) use ($search) {
                                    $query->orWhere('correct_number', 'LIKE', "%$search%")->orWhere('wrong_number', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('total_correct_number', 'LIKE', "%$search%")->orWhere('total_wrong_number', 'LIKE', "%$search%")->orWhere('message', 'LIKE', "%$search%")->orWhere('sender_name', 'LIKE', "%$search%")->orWhere('numbers', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('correct_number', 'LIKE', "%$search%")->orWhere('wrong_number', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('total_correct_number', 'LIKE', "%$search%")->orWhere('total_wrong_number', 'LIKE', "%$search%")->orWhere('message', 'LIKE', "%$search%")->orWhere('sender_name', 'LIKE', "%$search%")->orWhere('numbers', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                } else if ($database_name == 'cash_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where('username', $user->username)->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('amount_credit', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('payment_type', 'LIKE', "%$search%")->orWhere('network', 'LIKE', "%$search%")->orWhere('sender_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('amount_credit', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('payment_type', 'LIKE', "%$search%")->orWhere('network', 'LIKE', "%$search%")->orWhere('sender_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                } else if ($database_name == 'result_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username])->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('purchase_code', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('purchase_code', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                } else if ($database_name == 'transfers') {
                    $category = ['transfer', 'payout', 'transfer_out'];
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'transfers' => DB::table('transactions')
                                    ->where(['company_id' => $user->id])
                                    ->whereIn('category', $category)
                                    ->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")
                                            ->orWhere('reference', 'LIKE', "%$search%")
                                            ->orWhere('recipient_account_number', 'LIKE', "%$search%")
                                            ->orWhere('recipient_account_name', 'LIKE', "%$search%");
                                    })
                                    ->orderBy('id', 'desc')
                                    ->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'transfers' => DB::table('transactions')
                                    ->where(['company_id' => $user->id, 'status' => $request->status])
                                    ->whereIn('category', $category)
                                    ->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")
                                            ->orWhere('reference', 'LIKE', "%$search%")
                                            ->orWhere('recipient_account_number', 'LIKE', "%$search%")
                                            ->orWhere('recipient_account_name', 'LIKE', "%$search%");
                                    })
                                    ->orderBy('id', 'desc')
                                    ->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'transfers' => DB::table('transactions')
                                    ->where(['company_id' => $user->id])
                                    ->whereIn('category', $category)
                                    ->orderBy('id', 'desc')
                                    ->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'transfers' => DB::table('transactions')
                                    ->where(['company_id' => $user->id, 'status' => $request->status])
                                    ->whereIn('category', $category)
                                    ->orderBy('id', 'desc')
                                    ->paginate($request->limit)
                            ]);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Not invalid'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function TransferDetails(Request $request)
    {
        $transaction = DB::table('transactions')->where(['reference' => $request->id])->first();
        if ($transaction) {
            // Map new schema fields to legacy aliases for frontend compatibility if needed
            return response()->json([
                'trans' => $transaction
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function AllDepositHistory(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);
                $baseQuery = DB::table('transactions')
                    ->leftJoin('virtual_accounts', 'transactions.virtual_account_id', '=', 'virtual_accounts.id')
                    ->where('transactions.company_id', $user->active_company_id)
                    ->where('transactions.type', 'credit');

                $selectFields = [
                    'transactions.*',
                    'transactions.reference as transid',
                    'transactions.transaction_ref',
                    'transactions.session_id',
                    'transactions.transaction_type',
                    'transactions.created_at as date',
                    'transactions.description as details',
                    'transactions.fee as charges',
                    'transactions.net_amount',
                    'transactions.balance_before as oldbal',
                    'transactions.balance_after as newbal',
                    'transactions.settlement_status',
                    'virtual_accounts.account_name as va_account_name',
                    'virtual_accounts.account_number as va_account_number',
                    DB::raw("CASE WHEN transactions.status = 'successful' THEN 'successful' WHEN transactions.status = 'failed' THEN 'failed' WHEN transactions.status = 'pending' THEN 'pending' ELSE 'processing' END as status")
                ];


                if (!empty($search)) {
                    $baseQuery->where(function ($query) use ($search) {
                        $query->orWhere('transactions.amount', 'LIKE', "%$search%")
                            ->orWhere('transactions.created_at', 'LIKE', "%$search%")
                            ->orWhere('transactions.reference', 'LIKE', "%$search%")
                            ->orWhere('transactions.description', 'LIKE', "%$search%");
                    });
                }

                if ($request->status != 'ALL') {
                    $statusMap = ['active' => 'success', 'blocked' => 'failed'];
                    $dbStatus = $statusMap[strtolower($request->status)] ?? $request->status;
                    $baseQuery->where('transactions.status', $dbStatus);
                }

                $deposit_trans = $baseQuery->select($selectFields)
                    ->orderBy('transactions.id', 'desc')
                    ->paginate($request->limit);

                // Extract sender_name from metadata for each transaction
                foreach ($deposit_trans as $transaction) {
                    $metadata = json_decode($transaction->metadata, true);
                    $transaction->customer_name = $metadata['sender_name'] ?? $transaction->va_account_name ?? 'Unknown';
                    $transaction->customer_account = $metadata['sender_account'] ?? '';
                }

                return response()->json([
                    'deposit_trans' => $deposit_trans,
                ]);
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                if (!$request->has('status') || $request->status == 'null' || empty($request->status)) {
                    $request->merge(['status' => 'ALL']);
                }
                $search = strtolower($request->search);

                // Query both legacy message table and new transactions table
                $legacyQuery = DB::table('message')
                    ->where(['username' => $user->username])
                    ->select(
                        'message',
                        'amount',
                        DB::raw("NULL as charges"),
                        DB::raw("NULL as net_amount"),
                        'oldbal',
                        'newbal',
                        'habukhan_date as Habukhan_date',
                        'habukhan_date as adex_date',
                        'transid',
                        DB::raw("NULL as transaction_ref"),
                        DB::raw("NULL as session_id"),
                        DB::raw("NULL as transaction_type"),
                        DB::raw("NULL as settlement_status"),
                        'plan_status',
                        DB::raw("NULL as status"),
                        'role',
                        DB::raw("'legacy' as source")
                    );

                $transactionQuery = DB::table('transactions')
                    ->where('company_id', $user->active_company_id)
                    ->where('type', 'debit')
                    ->select(
                        'description as message',
                        'amount',
                        'fee as charges',
                        'net_amount',
                        'balance_before as oldbal',
                        'balance_after as newbal',
                        'created_at as Habukhan_date',
                        'created_at as adex_date',
                        'reference as transid',
                        'transaction_ref',
                        'session_id',
                        'transaction_type',
                        'settlement_status',
                        DB::raw("CASE WHEN status = 'successful' THEN 1 WHEN status = 'failed' THEN 2 WHEN status = 'pending' THEN 0 ELSE 0 END as plan_status"),
                        DB::raw("status as status"),
                        DB::raw("'user' as role"),
                        DB::raw("'transactions' as source")
                    );

                if (!empty($search)) {
                    $legacyQuery->where(function ($query) use ($search) {
                        $query->orWhere('message', 'LIKE', "%$search%")
                            ->orWhere('habukhan_date', 'LIKE', "%$search%")
                            ->orWhere('transid', 'LIKE', "%$search%");
                    });
                    $transactionQuery->where(function ($query) use ($search) {
                        $query->orWhere('description', 'LIKE', "%$search%")
                            ->orWhere('created_at', 'LIKE', "%$search%")
                            ->orWhere('reference', 'LIKE', "%$search%");
                    });
                }

                if ($request->status != 'ALL') {
                    $legacyQuery->where('plan_status', $request->status);
                    $transactionQuery->where('status', $this->mapStatusToDb($request->status));
                }

                $allSummary = $legacyQuery->unionAll($transactionQuery)
                    ->orderBy('Habukhan_date', 'desc')
                    ->paginate($request->limit);

                return response()->json([
                    'all_summary' => $allSummary
                ]);

            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllRATransactions(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();

                if (!$request->has('status') || $request->status == 'null' || empty($request->status)) {
                    $request->merge(['status' => 'ALL']);
                }

                $search = strtolower($request->search);

                // Use Eloquent with eager loading for relationships
                $query = \App\Models\Transaction::query()
                    ->with(['company', 'customer', 'virtualAccount']);

                // If not admin, filter by company
                if (strtoupper($user->type) !== 'ADMIN') {
                    $query->where('company_id', $user->active_company_id);
                }

                // Filter by transaction type (mapping 'category' from frontend to 'transaction_type' in DB)
                $typeFilter = $request->category ?? $request->transaction_type;
                if (!empty($typeFilter) && $typeFilter !== 'ALL') {
                    $query->where('transaction_type', $typeFilter);
                } else {
                    // Default: show only customer-facing types
                    $query->whereIn('transaction_type', ['va_deposit', 'api_transfer', 'company_withdrawal', 'kyc_charge', 'refund', 'transfer', 'settlement_withdrawal', 'manual_adjustment']);
                }

                // Apply search filters
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('amount', 'LIKE', "%$search%")
                            ->orWhere('created_at', 'LIKE', "%$search%")
                            ->orWhere('reference', 'LIKE', "%$search%")
                            ->orWhere('transaction_ref', 'LIKE', "%$search%")
                            ->orWhere('session_id', 'LIKE', "%$search%")
                            ->orWhere('description', 'LIKE', "%$search%")
                            ->orWhere('recipient_account_number', 'LIKE', "%$search%")
                            ->orWhere('recipient_account_name', 'LIKE', "%$search%");
                    });
                }

                // Advanced Filters (Parity with Admin)
                if ($request->has('start_date') && !empty($request->start_date)) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }
                if ($request->has('end_date') && !empty($request->end_date)) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }
                // Category already handled above via transaction_type mapping
                if ($request->has('trans_order_no') && !empty($request->trans_order_no)) {
                    $query->where('reference', 'LIKE', "%" . $request->trans_order_no . "%");
                }
                if ($request->has('merchant_order_no') && !empty($request->merchant_order_no)) {
                    $query->where('transaction_ref', 'LIKE', "%" . $request->merchant_order_no . "%");
                }
                if ($request->has('session_id') && !empty($request->session_id)) {
                    $query->where('session_id', 'LIKE', "%" . $request->session_id . "%");
                }

                if ($request->status != 'ALL' && !empty($request->status)) {
                    $query->where('status', $request->status);
                }

                // Filtering by legacy status code if provided (for compatibility)
                if ($request->has('plan_status') && $request->plan_status !== 'ALL') {
                    $statusMap = [
                        '1' => 'success',
                        '2' => 'failed',
                        '0' => 'pending'
                    ];
                    $dbStatus = $statusMap[$request->plan_status] ?? null;
                    if ($dbStatus) {
                        $query->where('status', $dbStatus);
                    }
                }

                // Order by created_at DESC
                $query->orderBy('created_at', 'desc');

                // Paginate with default 50 per page
                $perPage = $request->perPage ?? $request->limit ?? 50;
                $transactions = $query->paginate($perPage);

                // Transform data for frontend compatibility
                $transactions->setCollection($transactions->getCollection()->map(function ($transaction) {
                    // Map new schema columns to legacy frontend expectations
                    $transaction->transid = $transaction->reference;
                    $transaction->date = $transaction->created_at;
                    $transaction->details = $transaction->description ?? '';
                    $transaction->charges = $transaction->fee ?? 0;
                    $transaction->oldbal = $transaction->balance_before ?? 0;
                    $transaction->newbal = $transaction->balance_after ?? 0;

                    // Use transactions.settlement_status directly (not from settlement_queue)
                    $transaction->settlement_status = $transaction->settlement_status ?? 'unsettled';
                    // Ensure settlement time exists if status is settled
                    if ($transaction->settlement_status === 'settled') {
                        $transaction->settlement_time = $transaction->settlement_time ?? $transaction->processed_at ?? $transaction->created_at;
                    } else {
                        $transaction->settlement_time = $transaction->settlement_time ?? null;
                    }

                    // 5. Robust Payer & Destination Mapping for High-Fidelity Receipts
                    $metadata = is_array($transaction->metadata) ? $transaction->metadata : json_decode($transaction->metadata, true);
                    $isDeposit = in_array($transaction->transaction_type, ['va_deposit', 'credit']);

                    // Fixed Financials: Ensure Gross - Fee = Net for all types
                    $amount = (float) ($transaction->amount ?? 0);
                    $fee = (float) ($transaction->fee ?? 0);

                    if ($isDeposit) {
                        // DEPOSIT: Gross is the full amount sent, Net is what company gets
                        $transaction->setAttribute('amount', $amount);
                        $transaction->setAttribute('fee', $fee);
                        $transaction->setAttribute('net_amount', $amount - $fee);

                        // PAYER (Section 3): The External Sender
                        $transaction->setAttribute('customer_name', $metadata['sender_name'] ?? 'External Payer');
                        $transaction->setAttribute('customer_account', $metadata['sender_account'] ?? '');
                        $transaction->setAttribute('customer_bank', $metadata['sender_bank'] ?? $metadata['sender_bank_name'] ?? '');

                        // DESTINATION (Section 4): The Company Virtual Account
                        if ($transaction->virtualAccount) {
                            $transaction->setAttribute('recipient_account_name', $transaction->company->name ?? 'System Wallet');
                            $transaction->setAttribute('recipient_account_number', $transaction->virtualAccount->account_number ?? '');
                            $transaction->setAttribute('recipient_bank_name', $transaction->virtualAccount->bank_name ?? 'PalmPay');
                        } else {
                            $transaction->setAttribute('recipient_account_name', 'System Wallet');
                            $transaction->setAttribute('recipient_account_number', '');
                            $transaction->setAttribute('recipient_bank_name', 'PalmPay');
                        }
                    } else {
                        // TRANSFER: Net is what was sent, Gross is total deduction (Amount + Fee)
                        $transaction->setAttribute('amount', $amount + $fee); // Gross
                        $transaction->setAttribute('fee', $fee);
                        $transaction->setAttribute('net_amount', $amount); // Net delivered

                        // PAYER (Section 3): The Company
                        if ($transaction->company) {
                            $transaction->setAttribute('customer_name', $transaction->company->name ?? 'The Company');
                            $transaction->setAttribute('va_account_name', $transaction->company->name ?? '');
                            $transaction->setAttribute('va_account_number', $transaction->company->account_number ?? '');
                            $transaction->setAttribute('va_bank_name', $transaction->company->bank_name ?? 'PalmPay');
                        } else {
                            $transaction->setAttribute('customer_name', 'The Company');
                            $transaction->setAttribute('va_account_name', '');
                            $transaction->setAttribute('va_account_number', '');
                            $transaction->setAttribute('va_bank_name', 'PalmPay');
                        }

                        // DESTINATION (Section 4): The Recipient
                        $recipient_bank = $transaction->recipient_bank_name;
                        if (empty($recipient_bank) && !empty($transaction->recipient_bank_code)) {
                            $bank = \DB::table('banks')->where('code', $transaction->recipient_bank_code)->first();
                            if ($bank)
                                $recipient_bank = $bank->name;
                        }

                        $transaction->setAttribute('recipient_account_name', $transaction->recipient_account_name ?? 'Recipient');
                        $transaction->setAttribute('recipient_account_number', $transaction->recipient_account_number ?? '');
                        $transaction->setAttribute('recipient_bank_name', $recipient_bank ?? '');
                    }

                    // Keep metadata as object for frontend access
                    $transaction->setAttribute('metadata', $metadata);

                    // Always ensure a Batch No exists - generate a Virtual ID if missing
                    $batchNumber = $transaction->settlement_batch_no
                        ?? ('BN-' . ($transaction->created_at ? $transaction->created_at->format('Ymd') : date('Ymd')) . '-' . strtoupper(substr($transaction->reference, -4)));
                    $transaction->setAttribute('settlement_batch_no', $batchNumber);

                    return $transaction;
                }));

                return response()->json([
                    'status' => 'success',
                    'ra_trans' => $transactions,
                    'data' => $transactions
                ]);

            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denied'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denied'
            ])->setStatusCode(403);
        }
    }
    public function AllAirtimeUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);
                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username])->Where(function ($query) use ($search) {
                                $query->orWhere('network', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('discount', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('network', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('discount', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                } else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }
    public function AllStockHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->where('wallet', '!=', 'wallet')->Where(function ($query) use ($search) {
                                $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where(['username' => $user->username, 'plan_status' => $request->status])->where('wallet', '!=', 'wallet')->Where(function ($query) use ($search) {
                                $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                } else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->where('wallet', '!=', 'wallet')->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('wallet', '!=', 'wallet')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllDataHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                } else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllCableHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                } else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllBillHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                } else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllResultHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                } else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function DataTrans(Request $request)
    {
        if (DB::table('data')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('data')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function AirtimeTrans(Request $request)
    {
        if (DB::table('airtime')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('airtime')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function DepositTrans(Request $request)
    {
        $trans = DB::table('transactions')
            ->where('reference', $request->id)
            ->select(
                '*',
                'reference as transid',
                'created_at as date',
                'description as details',
                'fee as charges',
                'balance_before as oldbal',
                'balance_after as newbal',
                DB::raw("CASE WHEN status = 'success' THEN 'successful' WHEN status = 'failed' THEN 'failed' ELSE 'pending' END as status")
            )
            ->first();

        if ($trans) {
            // Extract sender information from metadata
            $metadata = json_decode($trans->metadata, true) ?? [];
            $trans->sender_name = $metadata['sender_name'] ?? $metadata['sender_account_name'] ?? 'N/A';
            $trans->sender_account = $metadata['sender_account'] ?? 'N/A';
            $trans->sender_bank = $metadata['sender_bank'] ?? $metadata['sender_bank_name'] ?? 'N/A';

            return response()->json([
                'trans' => $trans
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function CableTrans(Request $request)
    {
        if (DB::table('cable')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('cable')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function BillTrans(Request $request)
    {
        if (DB::table('bill')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('bill')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function AirtimeCashTrans(Request $request)
    {
        if (DB::table('cash')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('cash')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function BulkSMSTrans(Request $request)
    {
        if (DB::table('bulksms')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('bulksms')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function ResultCheckerTrans(Request $request)
    {
        if (DB::table('exam')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('exam')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function ManualTransfer(Request $request)
    {
        if (DB::table('bank_transfer')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('bank_transfer')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function DataCardInvoice(Request $request)
    {
        if (DB::table('data_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('data_card')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function DataCardSuccess(Request $request)
    {
        if (DB::table('data_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('data_card')->where(['transid' => $request->id])->first(),
                'card_map' => DB::table('dump_data_card_pin')->where(['transid' => $request->id])->get()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function AllCustomers(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                // $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $userId = $this->verifytoken($request->id);
                $company = Company::where('user_id', $userId)->first();

                if (!$company) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Company not found for this user'
                    ])->setStatusCode(404);
                }

                $search = strtolower($request->search);
                $status = $request->status;
                $startDate = $request->start_date;
                $endDate = $request->end_date;

                $query = CompanyUser::where('company_id', $company->id);

                // Search Filter
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->orWhere('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%")
                            ->orWhere('email', 'LIKE', "%$search%")
                            ->orWhere('phone', 'LIKE', "%$search%")
                            ->orWhere('external_customer_id', 'LIKE', "%$search%");
                    });
                }

                // Status Filter
                if ($status && $status !== 'all') {
                    $query->where('status', $status);
                }

                // Date Range Filter
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                } elseif ($startDate) {
                    $query->where('created_at', '>=', $startDate . ' 00:00:00');
                } elseif ($endDate) {
                    $query->where('created_at', '<=', $endDate . ' 23:59:59');
                }

                $customers = $query->orderBy('id', 'desc')->paginate($request->limit);

                // Map to format matching frontend expectations (virtual_accounts wrapper)
                // The frontend expects: account_id, customer_name, email, phone, date, status
                $mappedCustomers = collect($customers->items())->transform(function ($customer) use ($company) {
                    // Generate a display ID if uuid/external_id is missing
                    $displayId = $customer->external_customer_id ?? ($customer->uuid ?? 'CUST-' . str_pad($customer->id, 6, '0', STR_PAD_LEFT));

                    return [
                        'id' => $customer->id,
                        'customer_id' => $displayId, // Display ID (UUID)
                        'account_id' => $displayId, // Consistent ID for frontend
                        'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                        'customer_email' => $customer->email, // FIX: Frontend expects customer_email
                        'customer_phone' => $customer->phone, // FIX: Frontend expects customer_phone
                        'email' => $customer->email, // Keep both for safety
                        'phone' => $customer->phone, // Keep both for safety
                        'merchant_company' => $company->name, // Add company name
                        'date' => $customer->created_at->format('d M Y, h:i A'),
                        'status' => $customer->status ?? 'active',
                        'kyc_status' => 'verified', // Customers under companies are verified
                        // Add other fields if needed for modal details
                        'address' => $customer->address,
                        'city' => $customer->city,
                        'state' => $customer->state,
                        'postal_code' => $customer->postal_code,
                        'date_of_birth' => $customer->date_of_birth,
                    ];
                });

                $paginatedResult = new \Illuminate\Pagination\LengthAwarePaginator(
                    $mappedCustomers,
                    $customers->total(),
                    $customers->perPage(),
                    $customers->currentPage(),
                    ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
                );

                return response()->json([
                    'virtual_accounts' => $paginatedResult
                ]);

            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denied'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denied'
            ])->setStatusCode(403);
        }
    }

    public function AllVirtualAccounts(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $userId = $this->verifytoken($request->id);
            if (DB::table('users')->where(['id' => $userId, 'status' => 'active'])->exists()) {

                $company = Company::where('user_id', $userId)->first();
                if (!$company) {
                    return response()->json(['status' => 404, 'message' => 'Company not found'], 404);
                }

                $search = strtolower($request->search);
                $query = VirtualAccount::where('company_id', $company->id);

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->orWhere('account_name', 'LIKE', "%$search%")
                            ->orWhere('account_number', 'LIKE', "%$search%")
                            ->orWhere('bank_name', 'LIKE', "%$search%")
                            ->orWhere('customer_email', 'LIKE', "%$search%")
                            ->orWhere('customer_name', 'LIKE', "%$search%");
                    });
                }

                $accounts = $query->orderBy('created_at', 'desc')->paginate($request->limit);

                $mappedAccounts = collect($accounts->items())->transform(function ($acc) {
                    return [
                        'id' => $acc->id,
                        'account_id' => $acc->account_id,
                        'customer_email' => $acc->customer_email,
                        'bank_name' => $acc->bank_name,
                        'account_number' => $acc->account_number,
                        'account_name' => $acc->account_name,
                        'status' => $acc->status,
                        'date' => $acc->created_at->format('d M Y, h:i A'),
                        'created_at' => $acc->created_at->format('d M Y, h:i A'),
                    ];
                });

                $paginatedResult = new \Illuminate\Pagination\LengthAwarePaginator(
                    $mappedAccounts,
                    $accounts->total(),
                    $accounts->perPage(),
                    $accounts->currentPage(),
                    ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
                );

                return response()->json([
                    'virtual_accounts' => $paginatedResult
                ]);

            } else {
                return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
            }
        } else {
            return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
        }
    }

    public function CreateCustomer(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $userId = $this->verifytoken($request->id);
            if (DB::table('users')->where(['id' => $userId, 'status' => 'active'])->exists()) {

                $company = Company::where('user_id', $userId)->first();
                if (!$company) {
                    return response()->json(['status' => 404, 'message' => 'Company not found'], 404);
                }

                $validator = Validator::make($request->all(), [
                    'first_name' => 'required|string',
                    'last_name' => 'required|string',
                    'email' => 'required|email',
                    'phone' => 'required|string',
                    'address' => 'required|string',
                    'state' => 'required|string',
                    'city' => 'required|string',
                    'postal_code' => 'required|string',
                    'date_of_birth' => 'nullable|date',
                    'id_type' => 'nullable|string|in:bvn,nin',
                    'id_number' => 'nullable|string',
                    'id_card' => 'nullable|file|mimes:jpg,png,pdf|max:5120',
                    'utility_bill' => 'nullable|file|mimes:jpg,png,pdf|max:5120',
                ]);

                if ($validator->fails()) {
                    return response()->json(['status' => 422, 'message' => $validator->errors()->first()], 422);
                }

                // Handle File Uploads (if implemented in frontend)
                $idCardPath = $request->hasFile('id_card') ? $request->file('id_card')->store('kyc/id_cards', 'public') : null;
                $utilityBillPath = $request->hasFile('utility_bill') ? $request->file('utility_bill')->store('kyc/utility_bills', 'public') : null;

                try {
                    // Identity Guarding: Check for existing customer by email OR phone
                    $customer = CompanyUser::where('company_id', $company->id)
                        ->where(function ($query) use ($request) {
                            $query->where('email', $request->email)
                                ->orWhere('phone', $request->phone);
                        })->first();

                    if ($customer) {
                        // Update existing customer details instead of creating new
                        $customer->update([
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'address' => $request->address,
                            'state' => $request->state,
                            'city' => $request->city,
                            'postal_code' => $request->postal_code,
                            'date_of_birth' => $request->date_of_birth,
                            'id_type' => $request->id_type,
                            'id_number' => $request->id_number,
                            'id_card_path' => $idCardPath ?? $customer->id_card_path,
                            'utility_bill_path' => $utilityBillPath ?? $customer->utility_bill_path,
                            'status' => 'active',
                        ]);
                    } else {
                        // Create new customer if unique
                        $customer = CompanyUser::create([
                            'company_id' => $company->id,
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'email' => $request->email,
                            'phone' => $request->phone,
                            'address' => $request->address,
                            'state' => $request->state,
                            'city' => $request->city,
                            'postal_code' => $request->postal_code,
                            'date_of_birth' => $request->date_of_birth,
                            'id_type' => $request->id_type,
                            'id_number' => $request->id_number,
                            'id_card_path' => $idCardPath,
                            'utility_bill_path' => $utilityBillPath,
                            'status' => 'active',
                            'kyc_status' => 'pending',
                        ]);
                    }
                    // Auto-provision Virtual Account
                    // Mocking logic to simulate external provider (SafeHaven/Palmpay) response
                    /*
                    $accountNumber = '9' . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);

                    VirtualAccount::create([
                        'company_id' => $company->id,
                        'company_user_id' => $customer->id, // Link to the specific customer
                        'user_id' => $userId, // Link to the company admin who owns the relationship
                        'account_type' => 'static', // Correct value based on DB schema
                        'provider' => 'safehaven', // or palmpay
                        'bank_name' => 'SafeHaven Microfinance Bank',
                        'bank_code' => '999', // Example code
                        'account_name' => $request->first_name . ' ' . $request->last_name,
                        'account_number' => $accountNumber,
                        // Fill Palmpay fields to satisfy DB constraints (even if mimicking SafeHaven)
                        'palmpay_account_number' => $accountNumber,
                        'palmpay_account_name' => $request->first_name . ' ' . $request->last_name,
                        'palmpay_bank_name' => 'SafeHaven Microfinance Bank',
                        'customer_email' => $request->email,
                        'customer_name' => $request->first_name . ' ' . $request->last_name,
                        'customer_phone' => $request->phone,
                        'status' => 'active',
                        'amount' => 0.00
                    ]);
                    */

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Customer created successfully',
                        'data' => $customer
                    ], 201);
                } catch (\Exception $e) {
                    return response()->json(['status' => 500, 'message' => 'Failed to create customer: ' . $e->getMessage()], 500);
                }

            } else {
                return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
            }
        } else {
            return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
        }
    }

    public function CreateReservedAccount(Request $request, $key)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));

        if (in_array($key, $explode_url)) {
            $user = Auth::user();

            if ($user->can('manage_customers')) {

                $validator = Validator::make($request->all(), [
                    'customer_id' => 'required|exists:company_users,id',
                    'bank_code' => 'required', // provider or bank code
                ]);

                if ($validator->fails()) {
                    return response()->json(['status' => 422, 'message' => $validator->errors()], 422);
                }

                try {
                    $customer = CompanyUser::find($request->customer_id);

                    // Logic to create Virtual Account
                    // For now, we will use the existing logic or placeholder logic
                    // as per user request "allow to be empty for now" but this is manual creation.
                    // If we must create one:

                    // Use the centralized VirtualAccountService for provisioning and deduplication
                    $virtualAccountService = new \App\Services\PalmPay\VirtualAccountService();

                    $customerData = [
                        'name' => "{$customer->first_name} {$customer->last_name}",
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'identity_type' => $customer->id_type === 'rc' ? 'company' : 'personal',
                        'license_number' => $customer->id_number,
                        'account_type' => 'static',
                        'bvn' => $customer->id_type === 'bvn' ? $customer->id_number : null,
                    ];

                    $va = $virtualAccountService->createVirtualAccount(
                        $user->company_id ?? 0,
                        $customer->uuid,
                        $customerData,
                        $request->bank_code,
                        $customer->id
                    );
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Reserved account created successfully',
                        'data' => $va
                    ], 201);

                } catch (\Exception $e) {
                    return response()->json(['status' => 500, 'message' => 'Failed to create account: ' . $e->getMessage()], 500);
                }

            } else {
                return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
            }
        } else {
            return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
        }
    }
    public function UpdateCustomer(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $userId = $this->verifytoken($request->id);
            if (DB::table('users')->where(['id' => $userId, 'status' => 'active'])->exists()) {

                $company = Company::where('user_id', $userId)->first();
                if (!$company) {
                    return response()->json(['status' => 404, 'message' => 'Company not found'], 404);
                }

                $customer = CompanyUser::where('id', $request->customer_id)->where('company_id', $company->id)->first();

                if (!$customer) {
                    return response()->json(['status' => 404, 'message' => 'Customer not found'], 404);
                }

                $validator = Validator::make($request->all(), [
                    'first_name' => 'required|string',
                    'last_name' => 'required|string',
                    'email' => 'required|email|unique:company_users,email,' . $customer->id,
                    'phone' => 'required|string',
                    'address' => 'nullable|string',
                    'city' => 'nullable|string',
                    'state' => 'nullable|string',
                    'postal_code' => 'nullable|string',
                    'date_of_birth' => 'nullable|date',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 422,
                        'message' => $validator->errors()->first(),
                        'errors' => $validator->errors()
                    ], 422);
                }

                $customer->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'postal_code' => $request->postal_code,
                    'date_of_birth' => $request->date_of_birth,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer updated successfully',
                    'data' => $customer
                ], 200);

            } else {
                return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
            }
        } else {
            return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
        }
    }

    public function DeleteCustomer(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $userId = $this->verifytoken($request->id);
            if (DB::table('users')->where(['id' => $userId, 'status' => 'active'])->exists()) {

                $company = Company::where('user_id', $userId)->first();
                if (!$company) {
                    return response()->json(['status' => 404, 'message' => 'Company not found'], 404);
                }

                $customer = CompanyUser::where('id', $request->customer_id)->where('company_id', $company->id)->first();

                if (!$customer) {
                    return response()->json(['status' => 404, 'message' => 'Customer not found'], 404);
                }

                // Delete associated Virtual Accounts
                VirtualAccount::where('company_user_id', $customer->id)
                    ->orWhere('customer_email', $customer->email)
                    ->delete();

                // Delete the customer
                $customer->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer deleted successfully'
                ], 200);

            } else {
                return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
            }
        } else {
            return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
        }
    }

    public function CustomerDetail(Request $request, $customer_id)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $userId = $this->verifytoken($request->id);
            if (DB::table('users')->where(['id' => $userId, 'status' => 'active'])->exists()) {

                $user = DB::table('users')->where('id', $userId)->first();
                $isAdmin = $user && in_array(strtolower($user->type), ['admin', 'administrator', 'superadmin']);

                $targetVirtualAccountId = null;
                $company = null;

                if ($isAdmin) {
                    // Admin can see any customer
                    $customer = CompanyUser::where(function ($q) use ($customer_id) {
                        $q->where('id', $customer_id)
                            ->orWhere('uuid', $customer_id)
                            ->orWhere('external_customer_id', $customer_id);
                    })->first();

                    if ($customer) {
                        $company = Company::find($customer->company_id);
                    }
                } else {
                    $company = Company::where('user_id', $userId)->first();
                    if (!$company) {
                        return response()->json(['status' => 404, 'message' => 'Company not found'], 404);
                    }

                    $customer = CompanyUser::where(function ($q) use ($customer_id) {
                        $q->where('id', $customer_id)
                            ->orWhere('uuid', $customer_id)
                            ->orWhere('external_customer_id', $customer_id);
                    })->where('company_id', $company->id)->first();
                }

                // If not found, check if it's a VirtualAccount ID or account_id
                if (!$customer) {
                    $vaQuery = VirtualAccount::where(function ($q) use ($customer_id) {
                        $q->where('id', $customer_id)
                            ->orWhere('account_id', $customer_id)
                            ->orWhere('uuid', $customer_id);
                    });

                    // If NOT admin, restrict VA search to user's company
                    if (!$isAdmin && $company) {
                        $vaQuery->where('company_id', $company->id);
                    }

                    $virtualAccount = $vaQuery->first();

                    if ($virtualAccount) {
                        $targetVirtualAccountId = $virtualAccount->id;
                        $company = Company::find($virtualAccount->company_id);

                        $customer = CompanyUser::where('id', $virtualAccount->company_user_id)
                            ->first();

                        // If still not found by direct relation, try by email
                        if (!$customer && $virtualAccount->customer_email) {
                            $customer = CompanyUser::where('email', $virtualAccount->customer_email)
                                ->where('company_id', $company->id)
                                ->first();
                        }
                    }
                }

                if (!$customer) {
                    return response()->json(['status' => 404, 'message' => 'Customer not found'], 404);
                }

                try {
                    // Fetch related data
                    $reserved_accounts = VirtualAccount::where('company_id', $company->id)
                        ->where(function ($q) use ($customer) {
                            $q->where('company_user_id', $customer->id)
                                ->orWhere('customer_email', $customer->email);
                        })
                        ->get()
                        // If we came from a specific virtual account ID, put it first so frontend picks it up
                        ->sortByDesc(function ($acc) use ($targetVirtualAccountId) {
                            return $acc->id == $targetVirtualAccountId ? 1 : 0;
                        })
                        ->values()
                        ->map(function ($acc) use ($customer) {
                            return [
                                'id' => $acc->id,
                                'account_id' => $acc->account_id,
                                'uuid' => $acc->uuid,
                                'customer_email' => $customer->email,
                                'bank_name' => $acc->bank_name,
                                'account_number' => $acc->account_number,
                                'account_name' => $acc->account_name,
                                'status' => $acc->status,
                                'date' => $acc->created_at ? $acc->created_at->format('d M Y, h:i A') : 'N/A',
                                'created_at' => $acc->created_at,
                            ];
                        });

                    // Fetch transactions for this customer's virtual accounts
                    $accountIds = $reserved_accounts->pluck('id')->toArray();
                    $accountNumbers = $reserved_accounts->pluck('account_number')->filter()->toArray();
                    $transactions = [];

                    if (!empty($accountIds) || !empty($accountNumbers)) {
                        $transactions = DB::table('transactions')
                            ->where('company_id', $company->id)
                            ->where(function ($q) use ($accountIds, $accountNumbers, $customer) {
                                $q->whereIn('virtual_account_id', $accountIds);

                                // Fallback: Check account numbers and email in metadata/description
                                foreach ($accountNumbers as $num) {
                                    if (!empty($num)) {
                                        $q->orWhere('description', 'LIKE', "%$num%")
                                            ->orWhere('metadata', 'LIKE', "%$num%")
                                            ->orWhere('palmpay_reference', 'LIKE', "%$num%")
                                            ->orWhere('provider_reference', 'LIKE', "%$num%");
                                    }
                                }

                                if (!empty($customer->email)) {
                                    $q->orWhere('description', 'LIKE', "%{$customer->email}%")
                                        ->orWhere('metadata', 'LIKE', "%{$customer->email}%");
                                }
                                if (!empty($customer->phone)) {
                                    $q->orWhere('description', 'LIKE', "%{$customer->phone}%")
                                        ->orWhere('metadata', 'LIKE', "%{$customer->phone}%");
                                }
                            })
                            ->orderBy('created_at', 'desc')
                            ->limit(100)
                            ->get()
                            ->map(function ($txn) {
                                $va = \App\Models\VirtualAccount::find($txn->virtual_account_id);

                                // Parse payer info from description (e.g. "Transfer from ABOKI TELECOM" or "Ibrahim Goni:08089818908")
                                $description = $txn->description ?? '';
                                $payerName = null;
                                $payerAccount = null;
                                if (preg_match('/Transfer from (.+)/i', $description, $m)) {
                                    $payerName = trim($m[1]);
                                } elseif (preg_match('/^(.+):(\d{7,11})/i', $description, $m)) {
                                    $payerName = trim($m[1]);
                                    $payerAccount = trim($m[2]);
                                }

                                return [
                                    'id' => $txn->id,
                                    'transaction_ref' => $txn->transaction_ref ?? $txn->reference,
                                    'reference' => $txn->reference,
                                    'amount' => $txn->amount,
                                    'fee' => $txn->fee ?? 0,
                                    'net_amount' => $txn->net_amount ?? $txn->amount,
                                    'type' => $txn->type,
                                    'transaction_type' => $txn->transaction_type ?? $txn->type,
                                    'status' => $txn->status,
                                    'description' => $description,
                                    'session_id' => $txn->session_id ?? null,
                                    'palmpay_reference' => $txn->palmpay_reference ?? null,
                                    // Payer (parsed from description)
                                    'customer_name' => $payerName,
                                    'customer_account' => $payerAccount ?? $txn->provider_reference,
                                    // Recipient (for transfers/withdrawals)
                                    'recipient_account_number' => $txn->recipient_account_number ?? null,
                                    'recipient_account_name' => $txn->recipient_account_name ?? null,
                                    'recipient_bank_name' => $txn->recipient_bank_name ?? null,
                                    // VA (for deposits)
                                    'va_account_number' => $va ? $va->account_number : null,
                                    'va_account_name' => $va ? $va->account_name : null,
                                    'va_bank_name' => $va ? $va->bank_name : 'PalmPay',
                                    // Settlement
                                    'settlement_status' => $txn->settlement_status ?? 'unsettled',
                                    'settlement_batch_no' => $txn->settlement_batch_no ?? null,
                                    'settlement_time' => $txn->settlement_time ?? null,
                                    'created_at' => $txn->created_at,
                                ];
                            })
                            ->toArray();
                    }

                    $cards = [];

                    $displayId = $customer->external_customer_id ?? ($customer->uuid ?? 'CUST-' . str_pad($customer->id, 6, '0', STR_PAD_LEFT));

                    $customerData = [
                        'id' => $customer->id,
                        'customer_id' => $displayId,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'first_name' => $customer->first_name,
                        'last_name' => $customer->last_name,
                        'name' => $customer->first_name . ' ' . $customer->last_name,
                        'address' => $customer->address,
                        'city' => $customer->city,
                        'state' => $customer->state,
                        'postal_code' => $customer->postal_code,
                        'date_of_birth' => $customer->date_of_birth,
                        'merchant_name' => $company ? $company->name : 'N/A',
                        'merchant_id' => $customer->company_id,
                    ];

                    return response()->json([
                        'status' => 'success',
                        'customer' => $customerData,
                        'reserved_accounts' => $reserved_accounts,
                        'virtual_accounts' => $reserved_accounts,
                        'transactions' => $transactions,
                        'cards' => $cards,
                        // Compatibility for older/different frontend parts
                        'data' => [
                            'customer' => $customerData,
                            'reserved_accounts' => $reserved_accounts,
                            'virtual_accounts' => $reserved_accounts,
                            'transactions' => $transactions,
                            'cards' => $cards,
                        ]
                    ], 200);

                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Internal Error: ' . $e->getMessage(),
                        'debug' => $e->getTraceAsString()
                    ], 500);
                }

            } else {
                return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
            }
        } else {
            return response()->json(['status' => 403, 'message' => 'Access Denied'], 403);
        }
    }

    public function DataRechardPrint(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() > 0) {
                    $user = $check_user->first();
                    $search = strtolower($request->search);
                    $database_name = strtolower($request->database_name);
                    if ($database_name == 'data_card') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                        $query->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('plan_type', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                        $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('plan_type', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } else if ($database_name == 'recharge_card') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                        $query->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                        $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Not Found'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'User Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Not Authorised'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function RechargeCardProcess(Request $request)
    {
        if (DB::table('recharge_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('recharge_card')->where(['transid' => $request->id])->first()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function RechargeCardPrint(Request $request)
    {
        if (DB::table('recharge_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('recharge_card')->where(['transid' => $request->id])->first(),
                'card_map' => DB::table('dump_recharge_card_pin')->where(['transid' => $request->id])->get()
            ]);
        } else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    private function mapStatusToDb($status)
    {
        $statusMap = [
            'active' => 'success',
            'successful' => 'success',
            'blocked' => 'failed',
            'failed' => 'failed',
            'processing' => 'pending',
            'pending' => 'pending'
        ];

        return $statusMap[strtolower($status)] ?? $status;
    }
}

