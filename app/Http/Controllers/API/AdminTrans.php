<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminTrans extends Controller
{
    public function AllTrans(Request $request)
    {

        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);

        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $database_name = strtolower($request->database_name);
                    if ($database_name === 'bank_trans') {
                        $query = DB::table('transactions')
                            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
                            ->where('transactions.type', 'credit')
                            ->select(
                                'transactions.*',
                                'users.username',
                                'transactions.reference as transid',
                                'transactions.description as details',
                                'transactions.recipient_account_number as account_number',
                                'transactions.recipient_account_name as account_name',
                                'transactions.recipient_bank_name as bank_name',
                                'transactions.recipient_bank_code as bank_code',
                                'transactions.balance_before as oldbal',
                                'transactions.balance_after as newbal',
                                DB::raw("CASE WHEN transactions.status = 'success' THEN 1 WHEN transactions.status = 'failed' THEN 2 ELSE 0 END as plan_status")
                            );

                        if (!empty($search)) {
                            $query->where(function ($q) use ($search) {
                                $q->orWhere('transactions.amount', 'LIKE', "%$search%")
                                    ->orWhere('users.username', 'LIKE', "%$search%")
                                    ->orWhere('transactions.reference', 'LIKE', "%$search%")
                                    ->orWhere('transactions.description', 'LIKE', "%$search%");
                            });
                        }

                        if ($request->status != 'ALL') {
                            $statusMap = ['1' => 'success', '2' => 'failed', '0' => 'pending'];
                            $dbStatus = $statusMap[$request->status] ?? $request->status;
                            $query->where('transactions.status', $dbStatus);
                        }

                        return response()->json([
                            'bank_trans' => $query->orderBy('transactions.id', 'desc')->paginate($request->limit)
                        ]);
                    } else if ($database_name == 'cable_trans') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'cable_trans' => DB::table('cable')->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%")->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'cable_trans' => DB::table('cable')->where(['plan_status' => $request->status])->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%")->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'cable_trans' => DB::table('cable')->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'cable_trans' => DB::table('cable')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } elseif ($database_name == 'bill_trans') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'bill_trans' => DB::table('bill')->Where(function ($query) use ($search) {
                                        $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%")->orWhere('token', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'bill_trans' => DB::table('bill')->where(['plan_status' => $request->status])->Where(function ($query) use ($search) {
                                        $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%")->orWhere('token', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {

                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'bill_trans' => DB::table('bill')->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'bill_trans' => DB::table('bill')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } else if ($database_name == 'bulksms_trans') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'bulksms_trans' => DB::table('bulksms')->Where(function ($query) use ($search) {
                                        $query->orWhere('correct_number', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('wrong_number', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('total_correct_number', 'LIKE', "%$search%")->orWhere('total_wrong_number', 'LIKE', "%$search%")->orWhere('message', 'LIKE', "%$search%")->orWhere('sender_name', 'LIKE', "%$search%")->orWhere('numbers', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'bulksms_trans' => DB::table('bulksms')->where(['plan_status' => $request->status])->Where(function ($query) use ($search) {
                                        $query->orWhere('correct_number', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('wrong_number', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('total_correct_number', 'LIKE', "%$search%")->orWhere('total_wrong_number', 'LIKE', "%$search%")->orWhere('message', 'LIKE', "%$search%")->orWhere('sender_name', 'LIKE', "%$search%")->orWhere('numbers', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'bulksms_trans' => DB::table('bulksms')->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'bulksms_trans' => DB::table('bulksms')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } else if ($database_name == 'cash_trans') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'cash_trans' => DB::table('cash')->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('amount_credit', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('payment_type', 'LIKE', "%$search%")->orWhere('network', 'LIKE', "%$search%")->orWhere('sender_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'cash_trans' => DB::table('cash')->where(['plan_status' => $request->status])->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('amount_credit', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('payment_type', 'LIKE', "%$search%")->orWhere('network', 'LIKE', "%$search%")->orWhere('sender_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'cash_trans' => DB::table('cash')->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'cash_trans' => DB::table('cash')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } else if ($database_name == 'result_trans') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'result_trans' => DB::table('exam')->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('purchase_code', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'result_trans' => DB::table('exam')->where(['plan_status' => $request->status])->Where(function ($query) use ($search) {
                                        $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('purchase_code', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'result_trans' => DB::table('exam')->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'result_trans' => DB::table('exam')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } else if ($database_name == 'card_trans') {
                        // Phase 7: Card Transactions
                        $query = DB::table('card_transactions')
                            ->join('virtual_cards', 'card_transactions.card_id', '=', 'virtual_cards.card_id')
                            ->join('users', 'virtual_cards.user_id', '=', 'user.id')
                            ->select('card_transactions.*', 'user.username', 'virtual_cards.card_type', 'virtual_cards.user_id');

                        if (!empty($search)) {
                            $query->where(function ($q) use ($search) {
                                $q->orWhere('card_transactions.card_id', 'LIKE', "%$search%")
                                    ->orWhere('card_transactions.xixapay_transaction_id', 'LIKE', "%$search%")
                                    ->orWhere('card_transactions.merchant_name', 'LIKE', "%$search%")
                                    ->orWhere('user.username', 'LIKE', "%$search%");
                            });
                        }

                        if ($request->status != 'ALL') {
                            $query->where('card_transactions.status', $request->status);
                        }

                        return response()->json([
                            'card_trans' => $query->orderBy('card_transactions.id', 'desc')->paginate($request->limit)
                        ]);
                    } else {
                        return response()->json([


                            'message' => 'Not invalid'

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
    public function DepositTransSum(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $query = DB::table('transactions')
                        ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
                        ->where('transactions.type', 'credit')
                        ->select(
                            'transactions.*',
                            'users.username',
                            'transactions.reference as transid',
                            'transactions.description as details',
                            DB::raw("CASE WHEN transactions.status = 'success' THEN 'active' WHEN transactions.status = 'failed' THEN 'blocked' ELSE transactions.status END as status")
                        );

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('transactions.amount', 'LIKE', "%$search%")
                                ->orWhere('users.username', 'LIKE', "%$search%")
                                ->orWhere('transactions.created_at', 'LIKE', "%$search%")
                                ->orWhere('transactions.reference', 'LIKE', "%$search%")
                                ->orWhere('transactions.description', 'LIKE', "%$search%");
                        });
                    }

                    if ($request->status != 'ALL') {
                        $statusMap = ['active' => 'success', 'blocked' => 'failed'];
                        $dbStatus = $statusMap[strtolower($request->status)] ?? $request->status;
                        $query->where('transactions.status', $dbStatus);
                    }

                    return response()->json([
                        'deposit_trans' => $query->orderBy('transactions.id', 'desc')->paginate($request->limit),
                    ]);
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function StockTransSum(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);

                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'data_trans' => DB::table('data')->where('wallet', '!=', 'wallet')->Where(function ($query) use ($search) {
                                    $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'data_trans' => DB::table('data')->where(['plan_status' => $request->status])->where('wallet', '!=', 'wallet')->Where(function ($query) use ($search) {
                                    $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'data_trans' => DB::table('data')->where('wallet', '!=', 'wallet')->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'data_trans' => DB::table('data')->where('wallet', '!=', 'wallet')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AirtimeTransSum(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'airtime_trans' => DB::table('airtime')->Where(function ($query) use ($search) {
                                    $query->orWhere('network', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('discount', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'airtime_trans' => DB::table('airtime')->where(['plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('network', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('discount', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'airtime_trans' => DB::table('airtime')->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'airtime_trans' => DB::table('airtime')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DataTransSum(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {

                    $search = strtolower($request->search);

                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'data_trans' => DB::table('data')->Where(function ($query) use ($search) {
                                    $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'data_trans' => DB::table('data')->where(['plan_status' => $request->status])->Where(function ($query) use ($search) {
                                    $query->orWhere('network', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    } else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'data_trans' => DB::table('data')->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        } else {
                            return response()->json([
                                'data_trans' => DB::table('data')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AllSummaryTrans(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'admin')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);

                    // Query from new transactions table
                    $query = DB::table('transactions')
                        ->leftJoin('companies', 'transactions.company_id', '=', 'companies.id')
                        ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
                        ->select(
                            'transactions.id',
                            'transactions.reference as transid',
                            'transactions.reference',
                            'transactions.amount',
                            'transactions.fee',
                            'transactions.type',
                            'transactions.category',
                            'transactions.status',
                            'transactions.description as message',
                            'transactions.metadata',
                            'transactions.created_at',
                            'transactions.balance_before as oldbal',
                            'transactions.balance_after as newbal',
                            'transactions.recipient_account_number',
                            'transactions.recipient_account_name',
                            'transactions.recipient_bank_name',
                            'users.username',
                            'companies.name as company_name',
                            DB::raw("CASE 
                                WHEN transactions.status = 'success' THEN 'success'
                                WHEN transactions.status = 'successful' THEN 'success'
                                WHEN transactions.status = 'pending' THEN 'pending'
                                WHEN transactions.status = 'processing' THEN 'pending'
                                WHEN transactions.status = 'failed' THEN 'failed'
                                ELSE 'pending'
                            END as plan_status")
                        );

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('transactions.reference', 'LIKE', "%$search%")
                                ->orWhere('transactions.description', 'LIKE', "%$search%")
                                ->orWhere('transactions.amount', 'LIKE', "%$search%")
                                ->orWhere('users.username', 'LIKE', "%$search%")
                                ->orWhere('companies.name', 'LIKE', "%$search%");
                        });
                    }

                    if ($request->status != 'ALL') {
                        $query->where('transactions.status', $request->status);
                    }

                    $transactions = $query->orderBy('transactions.id', 'desc')->paginate($request->limit ?? 20);

                    $transactions->through(function ($item) {
                        // Decode metadata
                        $metadata = is_string($item->metadata) ? json_decode($item->metadata, true) : $item->metadata;
                        
                        // For debit transactions (transfers/withdrawals), use recipient info from transaction columns
                        // For credit transactions (deposits), use sender info from metadata
                        if ($item->type === 'debit') {
                            $item->phone = $item->recipient_account_number ?? null;
                            $item->phone_account = $item->recipient_account_name ?? null;
                        } else {
                            // Credit transaction - extract sender info from metadata
                            // Check multiple possible keys for sender account and name
                            $item->phone = $metadata['sender_account'] ?? $metadata['account_number'] ?? $metadata['sender_account_number'] ?? null;
                            $item->phone_account = $metadata['sender_name'] ?? $metadata['sender_account_name'] ?? $metadata['account_name'] ?? null;
                        }
                        
                        // Set display values
                        $item->display_category = Str::headline($item->category);
                        $item->display_status = strtoupper($item->status ?? 'pending');
                        
                        // Set merchant/user display name
                        $item->merchant_display = $item->company_name ?? $item->username ?? 'N/A';
                        
                        // Format date
                        $item->plan_date = $item->created_at;
                        
                        return $item;
                    });


                    return response()->json([
                        'all_summary' => $transactions
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DataRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('data')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('data')->where('transid', $request->transid)->first();
                        $user = DB::table('users')->where(['username' => $trans->username])->first();
                        if ($request->plan_status == 1) {
                            $api_response = "You have successfully purchased " . $trans->network . ' ' . $trans->plan_name . ' to ' . $trans->plan_phone;
                            $status = 'success';
                            // make success
                            if ($trans->plan_status == 0) {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->network . ' ' . $trans->plan_name . ' to ' . $trans->plan_phone]);
                                DB::table('data')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                            } else if ($trans->plan_status == 2) {
                                if (strtolower($trans->wallet) == 'wallet') {
                                    $b = DB::table('users')->where('username', $trans->username)->first();
                                    $user_balance = $b->balance;
                                    DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance - $trans->amount]);
                                } else {
                                    $wallet_bal = strtolower($trans->wallet) . "_bal";
                                    $b = DB::table('wallet_funding')->where('username', $trans->username)->first();
                                    $user_balance = $b->$wallet_bal;
                                    DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance - $trans->amount]);
                                }
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->network . ' ' . $trans->plan_name . ' to ' . $trans->plan_phone, 'oldbal' => $user_balance, 'newbal' => $user_balance - $trans->amount]);
                                DB::table('data')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'oldbal' => $user_balance, 'newbal' => $user_balance - $trans->amount]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Not Stated'
                                ])->setStatusCode(403);
                            }
                        } else if ($request->plan_status == 2) {
                            // refund user
                            if (strtolower($trans->wallet) == 'wallet') {
                                $b = DB::table('users')->where('username', $trans->username)->first();
                                $user_balance = $b->balance;
                                DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance + $trans->amount]);
                            } else {
                                $wallet_bal = strtolower($trans->wallet) . "_bal";
                                $b = DB::table('wallet_funding')->where('username', $trans->username)->first();
                                $user_balance = $b->$wallet_bal;
                                DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance + $trans->amount]);
                            }
                            DB::table('data')->where(['username' => $trans->username, 'transid' => $trans->transid])->delete();
                            DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->delete();
                            $data_new = [
                                'plan_status' => 2,
                                'oldbal' => $user_balance,
                                'newbal' => $user_balance + $trans->amount,
                                'network' => $trans->network,
                                'network_type' => $trans->network_type,
                                'plan_name' => $trans->plan_name,
                                'amount' => $trans->amount,
                                'transid' => $trans->transid,
                                'plan_phone' => $trans->plan_phone,
                                'plan_date' => $this->system_date(),
                                'system' => $trans->system,
                                'wallet' => $trans->wallet,
                                'api_response' => null,
                                'username' => $trans->username
                            ];
                            $message_new = [
                                'plan_status' => 2,
                                'message' => "Transaction Fail (Refund)" . $trans->network . ' ' . $trans->plan_name . ' to ' . $trans->plan_phone,
                                'oldbal' => $user_balance,
                                'newbal' => $user_balance + $trans->amount,
                                'username' => $trans->username,
                                'created_at' => $this->system_date(),
                                'transid' => $trans->transid,
                                'role' => 'data',
                                'amount' => $trans->amount
                            ];

                            DB::table('message')->insert($message_new);
                            DB::table('data')->insert($data_new);
                            $api_response = "Transaction Fail (Refund)" . $trans->network . ' ' . $trans->plan_name . ' to ' . $trans->plan_phone;
                            $status = 'fail';
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Not Stated'
                            ])->setStatusCode(403);
                        }
                        if ($status) {
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $user->webhook);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $status, 'request-id' => $trans->transid, 'response' => $api_response]));  //Post Fields
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_exec($ch);
                            curl_close($ch);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',
                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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
    public function AirtimeRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('airtime')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('airtime')->where('transid', $request->transid)->first();
                        if ($request->plan_status == 1) {
                            // make success
                            if ($trans->plan_status == 0) {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->network . ' ' . $trans->network_type . ' to ' . $trans->plan_phone]);
                                DB::table('airtime')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                            } else if ($trans->plan_status == 2) {

                                $b = DB::table('users')->where('username', $trans->username)->first();
                                $user_balance = $b->balance;
                                DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance - $trans->discount]);

                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->network . ' ' . $trans->network_type . ' to ' . $trans->plan_phone, 'oldbal' => $user_balance, 'newbal' => $user_balance - $trans->discount]);
                                DB::table('airtime')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'oldbal' => $user_balance, 'newbal' => $user_balance - $trans->discount]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Not Stated'
                                ])->setStatusCode(403);
                            }
                        } else if ($request->plan_status == 2) {
                            // refund user

                            $b = DB::table('users')->where('username', $trans->username)->first();
                            $user_balance = $b->balance;
                            DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance + $trans->discount]);

                            DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Transaction Fail (Refund)" . $trans->network . ' ' . $trans->network_type . ' to ' . $trans->plan_phone, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->discount]);
                            DB::table('airtime')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->discount]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Not Stated'
                            ])->setStatusCode(403);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',

                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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
    public function CableRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('cable')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('cable')->where('transid', $request->transid)->first();
                        $transaction_amount = $trans->amount + $trans->charges;
                        if ($request->plan_status == 1) {
                            // make success
                            if ($trans->plan_status == 0) {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->cable_name . ' ' . $trans->cable_plan . ' to ' . $trans->iuc]);
                                DB::table('cable')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                            } else if ($trans->plan_status == 2) {

                                $b = DB::table('users')->where('username', $trans->username)->first();
                                $user_balance = $b->balance;
                                DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance - $transaction_amount]);

                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->cable_name . ' ' . $trans->cable_plan . ' to ' . $trans->iuc, 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                                DB::table('cable')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Not Stated'
                                ])->setStatusCode(403);
                            }
                        } else if ($request->plan_status == 2) {
                            // refund user

                            $b = DB::table('users')->where('username', $trans->username)->first();
                            $user_balance = $b->balance;
                            DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance + $transaction_amount]);

                            DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Transaction Fail (Refund)" . $trans->cable_name . ' ' . $trans->cable_plan . ' to ' . $trans->iuc, 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                            DB::table('cable')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Not Stated'
                            ])->setStatusCode(403);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',

                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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
    public function BillRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('bill')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('bill')->where('transid', $request->transid)->first();
                        $transaction_amount = $trans->amount + $trans->charges;
                        if ($request->plan_status == 1) {
                            // make success
                            if ($trans->plan_status == 0) {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->disco_name . ' ' . $trans->meter_type . ' to ' . $trans->meter_number]);
                                DB::table('bill')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                            } else if ($trans->plan_status == 2) {

                                $b = DB::table('users')->where('username', $trans->username)->first();
                                $user_balance = $b->balance;
                                DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance - $transaction_amount]);

                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->disco_name . ' ' . $trans->meter_type . ' to ' . $trans->meter_number, 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                                DB::table('bill')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Not Stated'
                                ])->setStatusCode(403);
                            }
                        } else if ($request->plan_status == 2) {
                            // refund user

                            $b = DB::table('users')->where('username', $trans->username)->first();
                            $user_balance = $b->balance;
                            DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance + $transaction_amount]);

                            DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Transaction Fail (Refund) " . $trans->disco_name . ' ' . $trans->meter_type . ' to ' . $trans->meter_number, 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                            DB::table('bill')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Not Stated'
                            ])->setStatusCode(403);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',

                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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
    public function ResultRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('exam')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('exam')->where('transid', $request->transid)->first();
                        $transaction_amount = $trans->amount;
                        if ($request->plan_status == 1) {
                            // make success
                            if ($trans->plan_status == 0) {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->exam_name . ' E-pin']);
                                DB::table('exam')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                            } else if ($trans->plan_status == 2) {

                                $b = DB::table('users')->where('username', $trans->username)->first();
                                $user_balance = $b->balance;
                                DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance - $transaction_amount]);

                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "You have successfully purchased " . $trans->exam_name . ' E-pin', 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                                DB::table('exam')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Not Stated'
                                ])->setStatusCode(403);
                            }
                        } else if ($request->plan_status == 2) {
                            // refund user

                            $b = DB::table('users')->where('username', $trans->username)->first();
                            $user_balance = $b->balance;
                            DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance + $transaction_amount]);

                            DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Transaction Fail (Refund)" . $trans->exam_name . 'E-pin ', 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                            DB::table('exam')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Not Stated'
                            ])->setStatusCode(403);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',

                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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
    public function BulkSmsRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('bulksms')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('bulksms')->where('transid', $request->transid)->first();
                        $transaction_amount = $trans->amount;
                        if ($request->plan_status == 1) {
                            // make success
                            if ($trans->plan_status == 0) {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "Bulk SMS Sent successfully"]);
                                DB::table('bulksms')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                            } else if ($trans->plan_status == 2) {

                                $b = DB::table('users')->where('username', $trans->username)->first();
                                $user_balance = $b->balance;
                                DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance - $transaction_amount]);

                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "Bulk SMS sent successfuly", 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                                DB::table('bulksms')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Not Stated'
                                ])->setStatusCode(403);
                            }
                        } else if ($request->plan_status == 2) {
                            // refund user

                            $b = DB::table('users')->where('username', $trans->username)->first();
                            $user_balance = $b->balance;
                            DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance + $transaction_amount]);

                            DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Bulksms Fail (Refund)", 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                            DB::table('bulksms')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Not Stated'
                            ])->setStatusCode(403);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',

                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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
    public function AirtimeCashRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('cash')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('cash')->where('transid', $request->transid)->first();
                        $transaction_amount = $trans->amount_credit;
                        if ($request->plan_status == 1) {
                            // make success
                            $message = [
                                'username' => $trans->username,
                                'message' => 'airtime 2 cash approved',
                                'date' => $this->system_date(),
                                'habukhan' => 0
                            ];
                            DB::table('notif')->insert($message);

                            // Send Push Notification
                            $user = DB::table('users')->where('username', $trans->username)->first();
                            if ($user && $user->app_token) {
                                try {
                                    (new FirebaseService())->sendNotification(
                                        $user->app_token,
                                        "Airtime to Cash Approved",
                                        "Your airtime conversion has been approved. ₦" . number_format($trans->amount_credit, 2) . " credited to your wallet.",
                                        ['type' => 'transaction', 'action' => 'airtime_cash']
                                    );
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::warning('AirtimeCash Push failed: ' . $e->getMessage());
                                }
                            }

                            if (strtolower($trans->payment_type) != 'wallet') {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "Airtime 2 Cash Success"]);
                                DB::table('cash')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                            } else {
                                $b = DB::table('users')->where('username', $trans->username)->first();
                                $user_balance = $b->balance;
                                DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance + $transaction_amount]);

                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'message' => "Airtime 2 Cash Successs", 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                                DB::table('cash')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1, 'oldbal' => $user_balance, 'newbal' => $user_balance + $transaction_amount]);
                            }
                        } else if ($request->plan_status == 2) {
                            $message = [
                                'username' => $trans->username,
                                'message' => 'airtime 2 cash declined',
                                'date' => $this->system_date(),
                                'habukhan' => 0
                            ];
                            DB::table('notif')->insert($message);

                            // Send Push Notification
                            $user = DB::table('users')->where('username', $trans->username)->first();
                            if ($user && $user->app_token) {
                                try {
                                    (new FirebaseService())->sendNotification(
                                        $user->app_token,
                                        "Airtime to Cash Declined",
                                        "Your airtime conversion request has been declined.",
                                        ['type' => 'transaction', 'action' => 'airtime_cash']
                                    );
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::warning('AirtimeCash Push failed: ' . $e->getMessage());
                                }
                            }

                            // refund user
                            if (strtolower($trans->payment_type) != 'wallet') {
                                //
                                DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Airtime 2 Cash fail"]);
                                DB::table('cash')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2]);
                            } else {
                                if ($trans->plan_status == 1) {
                                    $b = DB::table('users')->where('username', $trans->username)->first();
                                    $user_balance = $b->balance;
                                    DB::table('users')->where('username', $trans->username)->update(['balance' => $user_balance - $transaction_amount]);

                                    DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Airtime 2 Cash fail", 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                                    DB::table('cash')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance - $transaction_amount]);
                                } else {
                                    //
                                    DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2, 'message' => "Airtime 2 Cash fail"]);
                                    DB::table('cash')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2]);
                                }
                            }
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Not Stated'
                            ])->setStatusCode(403);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',

                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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
    public function ManualSuccess(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('bank_transfer')->where('transid', $request->transid)->count() == 1) {
                        $trans = DB::table('bank_transfer')->where('transid', $request->transid)->first();
                        if ($request->plan_status == 1) {
                            // make success
                            $message = [
                                'username' => $trans->username,
                                'message' => 'manual funding approved',
                                'date' => $this->system_date(),
                                'habukhan' => 0
                            ];
                            DB::table('notif')->insert($message);

                            //
                            DB::table('bank_transfer')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);
                        } else {
                            // make fail
                            $message = [
                                'username' => $trans->username,
                                'message' => 'manual funding decliend',
                                'date' => $this->system_date(),
                                'habukhan' => 0
                            ];
                            DB::table('notif')->insert($message);
                            DB::table('bank_transfer')->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2]);
                        }
                        // send message here
                        return response()->json([
                            'status' => 'success',

                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Transaction id'
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

    public function DataRechargeCard(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $database_name = strtolower($request->database_name);
                    if ($database_name == 'data_card') {
                        if (!empty($searh)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->Where(function ($query) use ($search) {
                                        $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('plan_type', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->Where(function ($query) use ($search) {
                                        $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('plan_type', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    } else if ($database_name == 'recharge_card') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->Where(function ($query) use ($search) {
                                        $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->Where(function ($query) use ($search) {
                                        $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                    })->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            } else {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
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

    public function DataCardRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $data_card_d = DB::table('data_card')->where(['transid' => $request->transid])->first();
                    if ($data_card_d->plan_status == 0) {
                        $b = DB::table('users')->where('username', $data_card_d->username)->first();
                        $user_balance = $b->balance;
                        DB::table('users')->where('username', $data_card_d->username)->update(['balance' => $user_balance + $data_card_d->amount]);
                        DB::table('message')->where(['username' => $data_card_d->username, 'transid' => $data_card_d->transid])->update(['plan_status' => 2, 'message' => "Data Card Printing Fail", 'oldbal' => $user_balance, 'newbal' => $user_balance - $data_card_d->amount]);
                        DB::table('data_card')->where(['username' => $data_card_d->username, 'transid' => $data_card_d->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance + $data_card_d->amount]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Nothing Can Be Done To This Transaction'
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

    public function RechargeCardRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $recharge_card_d = DB::table('recharge_card')->where(['transid' => $request->transid])->first();
                    if ($recharge_card_d->plan_status == 0) {
                        $b = DB::table('users')->where('username', $recharge_card_d->username)->first();
                        $user_balance = $b->balance;
                        DB::table('users')->where('username', $recharge_card_d->username)->update(['balance' => $user_balance + $recharge_card_d->amount]);
                        DB::table('message')->where(['username' => $recharge_card_d->username, 'transid' => $recharge_card_d->transid])->update(['plan_status' => 2, 'message' => "Recharge Card Printing Fail", 'oldbal' => $user_balance, 'newbal' => $user_balance - $recharge_card_d->amount]);
                        DB::table('recharge_card')->where(['username' => $recharge_card_d->username, 'transid' => $recharge_card_d->transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $user_balance + $recharge_card_d->amount]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Nothing Can Be Done To This Transaction'
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

    public function AutoRefundBySystem(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            $tables = ['data', 'airtime', 'cable', 'bill'];
            $processed = false;

            foreach ($tables as $table) {
                if (DB::table($table)->where(['plan_status' => 0])->count() > 0) {
                    $pending = DB::table($table)->where(['plan_status' => 0])->limit(100)->get();
                    foreach ($pending as $trans) {
                        $user = DB::table('users')->where(['username' => $trans->username])->first();

                        // Calculate refund amount based on service type
                        $refund_amount = $trans->amount ?? 0;
                        if ($table == 'airtime') {
                            $refund_amount = $trans->discount;
                        } elseif ($table == 'cable' || $table == 'bill') {
                            $refund_amount = $trans->amount + ($trans->charges ?? 0);
                        }

                        // Refund balance
                        if (strtolower($trans->wallet ?? 'wallet') == 'wallet') {
                            DB::table('users')->where('username', $trans->username)->increment('balance', $refund_amount);
                        } else {
                            $wallet_bal = strtolower($trans->wallet) . "_bal";
                            DB::table('wallet_funding')->where('username', $trans->username)->increment($wallet_bal, $refund_amount);
                        }

                        // Update Status
                        DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update([
                            'plan_status' => 2,
                            'message' => "Transaction Fail (Refund) " . ($trans->network ?? $trans->cable_name ?? $trans->disco_name ?? 'System') . " Refunded ₦" . $refund_amount
                        ]);
                        DB::table($table)->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 2]);

                        // Webhook
                        if (!empty($user->webhook)) {
                            @$ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $user->webhook);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => 'fail', 'request-id' => $trans->transid, 'response' => "Refunded"]));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_exec($ch);
                            curl_close($ch);
                        }
                    }
                    $processed = true;
                }
            }

            return $processed ? 'success' : 'all done';
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AutoSuccessBySystem(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            $tables = ['data', 'airtime', 'cable', 'bill'];
            $processed = false;

            foreach ($tables as $table) {
                if (DB::table($table)->where(['plan_status' => 0])->count() > 0) {
                    $pending = DB::table($table)->where(['plan_status' => 0])->get();
                    foreach ($pending as $trans) {
                        $user = DB::table('users')->where(['username' => $trans->username])->first();

                        DB::table('message')->where(['username' => $trans->username, 'transid' => $trans->transid])->update([
                            'plan_status' => 1,
                            'message' => "Transaction Successful: " . ($trans->network ?? $trans->cable_name ?? $trans->disco_name ?? 'System') . " purchase to " . ($trans->plan_phone ?? $trans->iuc ?? $trans->meter_number)
                        ]);
                        DB::table($table)->where(['username' => $trans->username, 'transid' => $trans->transid])->update(['plan_status' => 1]);

                        if (!empty($user->webhook)) {
                            @$ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $user->webhook);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => 'success', 'request-id' => $trans->transid, 'response' => "Successful"]));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_exec($ch);
                            curl_close($ch);
                        }
                    }
                    $processed = true;
                }
            }
            return $processed ? 'success' : 'all done';
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function TransferTransSum(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $query = DB::table('transactions')
                        ->join('users as user', 'transactions.user_id', '=', 'user.id')
                        ->where('transactions.category', 'transfer_out')
                        ->select(
                            'transactions.*',
                            'user.username',
                            'transactions.reference as reference',
                            'transactions.recipient_account_number as account_number',
                            'transactions.recipient_account_name as account_name',
                            'transactions.recipient_bank_code as bank_code',
                            'transactions.recipient_bank_name as bank_name',
                            'transactions.fee as charge',
                            DB::raw("CASE WHEN transactions.status = 'success' THEN 'SUCCESS' WHEN transactions.status = 'failed' THEN 'FAILED' ELSE transactions.status END as status")
                        );

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('user.username', 'LIKE', "%$search%")
                                ->orWhere('transactions.reference', 'LIKE', "%$search%")
                                ->orWhere('transactions.recipient_account_number', 'LIKE', "%$search%")
                                ->orWhere('transactions.recipient_account_name', 'LIKE', "%$search%");
                        });
                    }

                    if ($request->status != 'ALL') {
                        $query->where('transactions.status', strtolower($request->status));
                    }

                    $results = $query->orderBy('transactions.id', 'desc')->paginate($request->limit);

                    return response()->json([
                        'transfer_trans' => $results
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
    public function TransferUpdate(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });

                if ($check_user->count() > 0) {
                    $trans = DB::table('transactions')->where('reference', $request->transid)->first();

                    if (!$trans) {
                        return response()->json(['status' => 404, 'message' => 'Transaction not found'])->setStatusCode(404);
                    }

                    if ($request->plan_status == 1) { // Mark Successful
                        DB::table('transactions')->where('reference', $request->transid)->update(['status' => 'success']);
                        DB::table('message')->where('transid', $request->transid)->update(['plan_status' => 1]);

                        return response()->json(['status' => 'success', 'message' => 'Transfer marked as Successful']);

                    } else if ($request->plan_status == 2) { // Refund / Fail
                        // Prevent double refund
                        if (strtolower($trans->status) == 'failed') {
                            return response()->json(['status' => 'fail', 'message' => 'Transaction already failed/refunded'])->setStatusCode(400);
                        }

                        DB::transaction(function () use ($trans) {
                            $user = DB::table('users')->where('id', $trans->user_id)->lockForUpdate()->first();
                            $fee = $trans->fee ?? 0;
                            $refundAmount = $trans->amount + $fee;
                            $new_bal = $user->balance + $refundAmount;

                            DB::table('users')->where('id', $user->id)->update(['balance' => $new_bal]);

                            DB::table('transactions')->where('id', $trans->id)->update([
                                'status' => 'failed',
                                'balance_after' => $new_bal // Correctly reflect refund balance in record if desired
                            ]);

                            DB::table('message')->where('transid', $trans->reference)->update([
                                'plan_status' => 0, // Failed
                                'newbal' => $new_bal
                            ]);
                        });

                        return response()->json(['status' => 'success', 'message' => 'Transfer Refunded Successfully']);
                    }
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function CardTransSum(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $query = DB::table('message')
                        ->whereIn('role', ['card_creation', 'card_funding', 'card_withdrawal', 'card_status_change']);

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('username', 'LIKE', "%$search%")
                                ->orWhere('message', 'LIKE', "%$search%")
                                ->orWhere('transid', 'LIKE', "%$search%");
                        });
                    }

                    if ($request->status != 'ALL') {
                        $query->where('plan_status', $request->status);
                    }

                    $results = $query->orderBy('id', 'desc')->paginate($request->limit);

                    return response()->json([
                        'card_trans' => $results
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function CardRefund(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $trans = DB::table('message')->where('transid', $request->transid)->first();
                    if (!$trans) {
                        return response()->json(['status' => 404, 'message' => 'Transaction not found'])->setStatusCode(404);
                    }

                    $user = DB::table('users')->where('username', $trans->username)->first();
                    if (!$user) {
                        return response()->json(['status' => 404, 'message' => 'User not found'])->setStatusCode(404);
                    }

                    if ($request->plan_status == 1) { // Mark Success
                        if ($trans->plan_status == 1) {
                            return response()->json(['status' => 400, 'message' => 'Already Successful'])->setStatusCode(400);
                        }

                        // If it was failed (2), we need to reverse the refund
                        if ($trans->plan_status == 2) {
                            if ($trans->role === 'card_withdrawal') {
                                // Withdrawal success = User gets money. Previous refund (failed) debited them or did nothing? 
                                // Wait, the Refund logic below increments for withdrawal too? Let me re-check.
                                DB::table('users')->where('username', $trans->username)->increment('balance', $trans->amount);
                            } else {
                                // Creation/Funding success = User loses money
                                DB::table('users')->where('username', $trans->username)->decrement('balance', $trans->amount);
                            }
                        }

                        DB::table('message')->where('id', $trans->id)->update([
                            'plan_status' => 1,
                            'message' => str_replace('Transaction Fail (Refund)', '', $trans->message) . " (Marked Successful)"
                        ]);

                        return response()->json(['status' => 'success', 'message' => 'Transaction marked as Successful']);

                    } else if ($request->plan_status == 2) { // Refund / Mark Fail
                        if ($trans->plan_status == 2) {
                            return response()->json(['status' => 400, 'message' => 'Already Refunded/Failed'])->setStatusCode(400);
                        }

                        // Reversal logic
                        if ($trans->role === 'card_withdrawal') {
                            // Withdrawal fail = User loses the money they "got"
                            DB::table('users')->where('username', $trans->username)->decrement('balance', $trans->amount);
                        } else {
                            // Creation/Funding fail = User gets their money back
                            DB::table('users')->where('username', $trans->username)->increment('balance', $trans->amount);
                        }

                        DB::table('message')->where('id', $trans->id)->update([
                            'plan_status' => 2,
                            'message' => "Transaction Fail (Refund) " . $trans->message
                        ]);

                        return response()->json(['status' => 'success', 'message' => 'Transaction status updated successfully']);
                    }
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    /**
     * Get Transaction Statement
     * Financial statement with date range filtering
     */
    public function getStatement(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');

                if ($check_user->count() > 0) {
                    $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
                    $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
                    $search = $request->search ?? '';

                    // Query transactions table
                    $query = DB::table('transactions')
                        ->leftJoin('companies', 'transactions.company_id', '=', 'companies.id')
                        ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
                        ->whereBetween('transactions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->select(
                            'transactions.id',
                            'transactions.reference',
                            'transactions.amount',
                            'transactions.fee as charges',
                            'transactions.type',
                            'transactions.status',
                            'transactions.description',
                            'transactions.created_at',
                            'transactions.recipient_account_number as customer_account_number',
                            'transactions.recipient_account_name as customer_name',
                            'users.username',
                            'companies.name as company_name'
                        )
                        ->orderBy('transactions.created_at', 'desc');

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->where('transactions.reference', 'LIKE', "%$search%")
                                ->orWhere('transactions.description', 'LIKE', "%$search%")
                                ->orWhere('users.username', 'LIKE', "%$search%")
                                ->orWhere('companies.name', 'LIKE', "%$search%")
                                ->orWhere('transactions.recipient_account_number', 'LIKE', "%$search%")
                                ->orWhere('transactions.recipient_account_name', 'LIKE', "%$search%");
                        });
                    }

                    $statement = $query->paginate($request->limit ?? 50);

                    // Summary statistics
                    $summary = DB::table('transactions')
                        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->selectRaw('
                            COUNT(*) as total_count,
                            SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_credit,
                            SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_debit,
                            SUM(fee) as total_charges
                        ')
                        ->first();

                    return response()->json([
                        'status' => 'success',
                        'statement' => $statement,
                        'summary' => $summary,
                        'date_range' => [
                            'start' => $startDate,
                            'end' => $endDate
                        ]
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    /**
     * Get Transaction Reports
     * Analytics and performance metrics
     */
    public function getReport(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');

                if ($check_user->count() > 0) {
                    $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
                    $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');

                    // Overall metrics
                    $metrics = DB::table('transactions')
                        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->selectRaw('
                            COUNT(*) as total_transactions,
                            SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_transactions,
                            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_transactions,
                            SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as pending_transactions,
                            SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_inflow,
                            SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_outflow,
                            SUM(fee) as total_fees,
                            AVG(amount) as average_transaction_amount
                        ')
                        ->first();

                    // Daily breakdown
                    $daily = DB::table('transactions')
                        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->selectRaw('
                            DATE(created_at) as date,
                            COUNT(*) as count,
                            SUM(CASE WHEN status = "success" THEN amount ELSE 0 END) as volume,
                            SUM(fee) as fees
                        ')
                        ->groupBy('date')
                        ->orderBy('date', 'desc')
                        ->get();

                    // Top companies by volume
                    $topCompanies = DB::table('transactions')
                        ->join('companies', 'transactions.company_id', '=', 'companies.id')
                        ->whereBetween('transactions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->selectRaw('
                            companies.name as company_name,
                            COUNT(*) as transaction_count,
                            SUM(transactions.amount) as total_volume
                        ')
                        ->groupBy('companies.id', 'companies.name')
                        ->orderBy('total_volume', 'desc')
                        ->limit(10)
                        ->get();

                    // Success rate by hour
                    $hourlyStats = DB::table('transactions')
                        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->selectRaw('
                            HOUR(created_at) as hour,
                            COUNT(*) as total,
                            SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful
                        ')
                        ->groupBy('hour')
                        ->orderBy('hour')
                        ->get();

                    return response()->json([
                        'status' => 'success',
                        'metrics' => $metrics,
                        'daily_breakdown' => $daily,
                        'top_companies' => $topCompanies,
                        'hourly_stats' => $hourlyStats,
                        'date_range' => [
                            'start' => $startDate,
                            'end' => $endDate
                        ]
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    /**
     * Process Unified Admin Action
     * Handles Automated Refunds and Forced Credit Notifications
     */
    public function ProcessAdminAction(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');

                if ($check_user->count() > 0) {
                    $trans = DB::table('transactions')->where('reference', $request->reference)->first();
                    if (!$trans) {
                        return response()->json(['status' => 404, 'message' => 'Transaction not found'])->setStatusCode(404);
                    }

                    $action = $request->action; // 'refund' or 'notify_credit'

                    if ($action === 'refund') {
                        if ($trans->status === 'failed') {
                            return response()->json(['status' => 400, 'message' => 'Transaction already failed/refunded'])->setStatusCode(400);
                        }

                        return DB::transaction(function () use ($trans) {
                            $user = DB::table('users')->where('id', $trans->user_id)->lockForUpdate()->first();
                            if (!$user)
                                return response()->json(['status' => 404, 'message' => 'User not found'])->setStatusCode(404);

                            // Refund Amount + Fee if it's a debit/transfer
                            $refundAmount = ($trans->type === 'credit') ? 0 : ($trans->amount + ($trans->fee ?? 0));

                            if ($refundAmount > 0) {
                                DB::table('users')->where('id', $user->id)->increment('balance', $refundAmount);
                            }

                            DB::table('transactions')->where('id', $trans->id)->update([
                                'status' => 'failed',
                                'description' => $trans->description . ' (Admin Refunded)',
                                'balance_after' => ($user->balance + $refundAmount)
                            ]);

                            return response()->json(['status' => 'success', 'message' => 'Transaction Refunded Successfully']);
                        });

                    } else if ($action === 'notify_credit') {
                        // Mark as Success and ensure user is credited if it was a credit type
                        if ($trans->status === 'success') {
                            return response()->json(['status' => 'success', 'message' => 'Notification Sent (Already Success)']);
                        }

                        return DB::transaction(function () use ($trans) {
                            $user = DB::table('users')->where('id', $trans->user_id)->lockForUpdate()->first();
                            if (!$user)
                                return response()->json(['status' => 404, 'message' => 'User not found'])->setStatusCode(404);

                            if ($trans->type === 'credit') {
                                DB::table('users')->where('id', $user->id)->increment('balance', $trans->amount);
                            }

                            DB::table('transactions')->where('id', $trans->id)->update([
                                'status' => 'success',
                                'processed_at' => now(),
                                'balance_after' => ($user->balance + ($trans->type === 'credit' ? $trans->amount : 0))
                            ]);

                            return response()->json(['status' => 'success', 'message' => 'User Credited and Notified']);
                        });
                    }

                    return response()->json(['status' => 400, 'message' => 'Invalid action'])->setStatusCode(400);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function exportStatement(Request $request)
    {
        $allowed_origins = explode(',', config('app.habukhan_app_key'));
        $server_ip = $request->server('SERVER_ADDR');
        $is_internal = in_array($request->ip(), ['127.0.0.1', '::1', $server_ip]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $allowed_origins) || $is_internal) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');

                if ($check_user->count() > 0) {
                    $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
                    $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
                    $search = $request->search ?? '';

                    $transactions = DB::table('transactions')
                        ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
                        ->whereBetween('transactions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->select(
                            'transactions.reference',
                            'users.username as customer_name',
                            'transactions.type',
                            'transactions.amount',
                            'transactions.fee',
                            'transactions.status',
                            'transactions.created_at',
                            'transactions.description'
                        )
                        ->where(function ($q) use ($search) {
                            $q->where('transactions.reference', 'LIKE', "%$search%")
                                ->orWhere('users.username', 'LIKE', "%$search%")
                                ->orWhere('transactions.description', 'LIKE', "%$search%");
                        })
                        ->orderBy('transactions.created_at', 'desc')
                        ->get();

                    $filename = "statement_" . $startDate . "_to_" . $endDate . ".csv";
                    $handle = fopen('php://temp', 'r+');
                    fputcsv($handle, ['Reference', 'Customer', 'Type', 'Amount', 'Charges', 'Status', 'Date', 'Description']);

                    foreach ($transactions as $row) {
                        fputcsv($handle, [
                            $row->reference,
                            $row->customer_name,
                            $row->type,
                            $row->amount,
                            $row->fee,
                            $row->status,
                            $row->created_at,
                            $row->description
                        ]);
                    }

                    rewind($handle);
                    $csv = stream_get_contents($handle);
                    fclose($handle);

                    return response($csv)
                        ->header('Content-Type', 'text/csv')
                        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
}

    /**
     * Generate receipt for any transaction (admin only)
     */
    public function generateReceipt(Request $request, $id)
    {
        try {
            $transaction = \App\Models\Transaction::with('company')->findOrFail($id);
            
            $receiptService = new \App\Services\ReceiptService();
            return $receiptService->generateReceipt($transaction);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Admin receipt generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate receipt. Please try again.',
                'error_code' => 'PDF_GENERATION_FAILED'
            ], 500);
        }
    }
