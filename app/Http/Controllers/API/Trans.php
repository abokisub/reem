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
                    'transactions.created_at as date',
                    'transactions.description as details',
                    'transactions.fee as charges',
                    'transactions.balance_before as oldbal',
                    'transactions.balance_after as newbal',
                    'virtual_accounts.account_name as va_account_name',
                    'virtual_accounts.account_number as va_account_number',
                    DB::raw("CASE WHEN transactions.status = 'success' THEN 'successful' WHEN transactions.status = 'failed' THEN 'failed' ELSE 'processing' END as status")
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
                        'oldbal',
                        'newbal',
                        'habukhan_date as Habukhan_date',
                        'habukhan_date as adex_date',
                        'transid',
                        'plan_status',
                        'role',
                        DB::raw("'legacy' as source")
                    );

                $transactionQuery = DB::table('transactions')
                    ->where('company_id', $user->active_company_id)
                    ->where('type', 'debit')
                    ->select(
                        'description as message',
                        'amount',
                        'balance_before as oldbal',
                        'balance_after as newbal',
                        'created_at as Habukhan_date',
                        'created_at as adex_date',
                        'reference as transid',
                        DB::raw("CASE WHEN status = 'success' THEN 1 WHEN status = 'failed' THEN 2 ELSE 0 END as plan_status"),
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
                $query = DB::table('transactions')
                    ->leftJoin('virtual_accounts', 'transactions.virtual_account_id', '=', 'virtual_accounts.id')
                    ->where('transactions.company_id', $user->active_company_id);

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->orWhere('amount', 'LIKE', "%$search%")
                            ->orWhere('created_at', 'LIKE', "%$search%")
                            ->orWhere('reference', 'LIKE', "%$search%")
                            ->orWhere('description', 'LIKE', "%$search%")
                            ->orWhere('status', 'LIKE', "%$search%");
                    });
                }

                if ($request->status != 'ALL') {
                    $statusMap = [
                        'active' => 'success',
                        'blocked' => 'failed',
                        'pending' => 'pending'
                    ];
                    $dbStatus = $statusMap[strtolower($request->status)] ?? $request->status;
                    $query->where('status', $dbStatus);
                }

                // Map new schema columns to legacy frontend expectations
                $transactions = $query->select(
                    'transactions.*',
                    'transactions.reference as transid',
                    'transactions.created_at as date',
                    'transactions.description as details',
                    'transactions.fee as charges',
                    'transactions.balance_before as oldbal',
                    'transactions.balance_after as newbal',
                    'virtual_accounts.account_name as va_account_name',
                    'virtual_accounts.account_number as va_account_number',
                    DB::raw("CASE WHEN transactions.status = 'success' THEN 'successful' WHEN transactions.status = 'failed' THEN 'failed' ELSE 'processing' END as status")
                )

                    ->orderBy('transactions.id', 'desc')
                    ->paginate($request->limit);

                // Extract sender_name from metadata for each transaction
                foreach ($transactions as $transaction) {
                    $metadata = json_decode($transaction->metadata, true);
                    $transaction->customer_name = $metadata['sender_name'] ?? $transaction->va_account_name ?? 'Unknown';
                    $transaction->customer_account = $metadata['sender_account'] ?? '';
                    $transaction->customer_bank = $metadata['sender_bank'] ?? $metadata['sender_bank_name'] ?? '';
                    
                    // Keep metadata as object for frontend access
                    $transaction->metadata = $metadata;
                }

                return response()->json([
                    'ra_trans' => $transactions
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
                DB::raw("CASE WHEN status = 'success' THEN 'active' WHEN status = 'failed' THEN 'blocked' ELSE status END as status")
            )
            ->first();

        if ($trans) {
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
                $query = CompanyUser::where('company_id', $company->id);

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->orWhere('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%")
                            ->orWhere('email', 'LIKE', "%$search%")
                            ->orWhere('phone', 'LIKE', "%$search%")
                            ->orWhere('external_customer_id', 'LIKE', "%$search%");
                    });
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

    public function CustomerDetail(Request $request)
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

                // Fetch related data (Mocking mostly as relationships might not be fully defined yet)
                // Fetch related data
                $reserved_accounts = VirtualAccount::where('company_user_id', $customer->id)
                    ->orWhere('customer_email', $customer->email)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($acc) use ($customer) {
                        return [
                            'id' => $acc->id,
                            'account_id' => $acc->account_id,
                            'customer_email' => $customer->email, // Add customer email
                            'bank_name' => $acc->bank_name,
                            'account_number' => $acc->account_number,
                            'account_name' => $acc->account_name,
                            'status' => $acc->status,
                            'date' => $acc->created_at->format('d M Y, h:i A'),
                            'created_at' => $acc->created_at->format('d M Y, h:i A'),
                        ];
                    });
                $transactions = [];
                $cards = [];

                $displayId = $customer->external_customer_id ?? ($customer->uuid ?? 'CUST-' . str_pad($customer->id, 6, '0', STR_PAD_LEFT));

                return response()->json([
                    'status' => 'success',
                    'customer' => [
                        'id' => $customer->id,
                        'customer_id' => $displayId,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'first_name' => $customer->first_name,
                        'last_name' => $customer->last_name,
                        'name' => $customer->first_name . ' ' . $customer->last_name,
                        // Add other fields needed for the detail view
                        'address' => $customer->address,
                        'city' => $customer->city,
                        'state' => $customer->state,
                        'postal_code' => $customer->postal_code,
                        'date_of_birth' => $customer->date_of_birth,
                    ],
                    'reserved_accounts' => $reserved_accounts,
                    'transactions' => $transactions,
                    'cards' => $cards
                ], 200);

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

