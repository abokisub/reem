<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Beneficiary;
use App\Http\Controllers\API\ServiceLockController;


class BillPurchase extends Controller
{
    public function Buy(Request $request)
    {
        // Check if bill service is locked
        if (ServiceLockController::isLocked('bill')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Electricity bill service is currently unavailable. Please try again later.'
            ], 503);
        }

        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $validator = Validator::make($request->all(), [
                'disco' => 'required',
                'meter_number' => 'required',
                'bypass' => 'required',
                'meter_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0',
                'user_id' => 'required'
            ]);
            $system = "APP";
            // Professional Refactor: Use client-provided request-id for idempotency if available
            if ($request->has('request-id')) {
                $transid = $request->input('request-id');
            } else {
                $transid = $this->purchase_ref('BILL_');
            }

            $verified_id = $this->verifyapptoken($request->user_id);
            $check = DB::table('users')->where(['id' => $verified_id, 'status' => 'active']);
            if ($check->count() == 1) {
                $d_token = $check->first();
                if (trim($d_token->pin) == trim($request->pin)) {
                    $accessToken = $d_token->api_key;
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Transaction Pin'
                    ])->setStatusCode(403);
                }
            } else {
                $accessToken = 'null';
            }
        } else if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $validator = Validator::make($request->all(), [
                'disco' => 'required',
                'meter_number' => 'required',
                'bypass' => 'required',
                'meter_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0'
            ]);
            $system = config('app.name');
            $transid = $this->purchase_ref('BILL_');
            if ($this->core()->allow_pin == 1) {
                // transaction pin required
                $check = DB::table('users')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    if (trim($det->pin) == trim($request->pin)) {
                        $accessToken = $det->api_key;
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Invalid Transaction Pin'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Transaction Pin'
                    ])->setStatusCode(403);
                }
            } else {
                // transaction pin not required
                $check = DB::table('users')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    $accessToken = $det->api_key;
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'An Error Occur'
                    ])->setStatusCode(403);
                }
            }
        } else {
            // api verification
            $validator = Validator::make($request->all(), [
                'disco' => 'required',
                'meter_number' => 'required',
                'bypass' => 'required',
                'meter_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0',
                'request-id' => 'required|unique:bill,transid'
            ]);
            $system = "API";
            $id = "request-id";
            $transid = $request->$id;
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));
        }
        if ($accessToken) {
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 'fail'
                ])->setStatusCode(403);
            } else {
                $user_check = DB::table('users')->where(function ($query) use ($accessToken) {
                    $query->where('api_key', $accessToken)
                        ->orWhere('app_key', $accessToken)
                        ->orWhere('habukhan_key', $accessToken);
                })->where('status', 1);
                if ($user_check->count() == 1) {
                    $user = $user_check->first();
                    $company_blocked = false;
                    $user_company = DB::table('companies')->where('user_id', $user->id)->first();
                    if ($user_company && DB::table('banned_companies')->where('company_id', $user_company->id)->exists()) {
                        $company_blocked = true;
                    }

                    if (!$company_blocked) {
                        $cid = $user->active_company_id ?? 1;
                        if (DB::table('bill')->where('transid', $transid)->count() == 0 and DB::table('message')->where('transid', $transid)->count() == 0) {
                            $bill_plan = DB::table('bill_plan')->where(['plan_id' => $request->disco, 'plan_status' => 1, 'company_id' => $cid])->first();
                            if (!$bill_plan) {
                                $bill_plan = DB::table('bill_plan')->where(['plan_id' => $request->disco, 'plan_status' => 1, 'company_id' => 1])->first();
                            }

                            if ($bill_plan) {
                                if ($this->core()->bill == 1) {
                                    $habukhan_new_go = true;

                                    if ($habukhan_new_go == true) {
                                        DB::beginTransaction();
                                        $user = DB::table('users')->where(['id' => $user->id])->lockForUpdate()->first();
                                        if (is_numeric($user->balance)) {
                                            if ($user->balance > 0) {
                                                if ((is_numeric($request->amount)) && $request->amount > 0) {
                                                    $bill_d = DB::table('bill_charge')->where('company_id', $cid)->first();
                                                    if (!$bill_d) {
                                                        $bill_d = DB::table('bill_charge')->where('company_id', 1)->first();
                                                    }
                                                    if ($bill_d->bill_max >= $request->amount) {
                                                        if ($request->amount >= $bill_d->bill_min) {
                                                            if ($bill_d->direct == 1) {
                                                                $charges = $bill_d->bill;
                                                            } else {
                                                                $charges = ($request->amount / 100) * $bill_d->bill;
                                                            }
                                                            $total_amount = $charges + $request->amount;
                                                            if ($user->balance >= $total_amount) {
                                                                $debit = $user->balance - $total_amount;
                                                                $refund = $debit + $total_amount;
                                                                $bill_sel = DB::table('bill_sel')->first();
                                                                $adm = new MeterSend();
                                                                $check_now = $bill_sel->bill;
                                                                $sending_data = [
                                                                    'disco' => $request->disco,
                                                                    'meter_type' => strtolower($request->meter_type),
                                                                    'meter_number' => strtolower($request->meter_number)
                                                                ];
                                                                if (method_exists($adm, $check_now)) {
                                                                    $customer_name = $adm->$check_now($sending_data);
                                                                } else {
                                                                    \Log::error("BillPurchase Error: Method {$check_now} does not exist in MeterSend.");
                                                                    $customer_name = null;
                                                                }
                                                                if ((empty($customer_name)) && ($request->bypass == false || $request->bypass == 'false')) {
                                                                    return response()->json([
                                                                        'status' => 'fail',
                                                                        'message' => 'Invalid Meter Number'
                                                                    ])->setStatusCode(403);
                                                                } else {
                                                                    if (DB::table('users')->where(['id' => $user->id])->update(['balance' => $debit])) {
                                                                        DB::commit();
                                                                        $trans_history = [
                                                                            'username' => $user->username,
                                                                            'amount' => $total_amount,
                                                                            'message' => 'Transaction on process ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                                                                            'phone_account' => $request->meter_number,
                                                                            'oldbal' => $user->balance,
                                                                            'newbal' => $debit,
                                                                            'habukhan_date' => $this->system_date(),
                                                                            'plan_status' => 0,
                                                                            'transid' => $transid,
                                                                            'role' => 'bill'
                                                                        ];
                                                                        $bill_trans = [
                                                                            'username' => $user->username,
                                                                            'amount' => $request->amount,
                                                                            'disco_name' => $bill_plan->disco_name,
                                                                            'meter_number' => $request->meter_number,
                                                                            'meter_type' => strtoupper($request->meter_type),
                                                                            'charges' => $charges,
                                                                            'newbal' => $debit,
                                                                            'oldbal' => $user->balance,
                                                                            'customer_name' => $customer_name,
                                                                            'system' => $system,
                                                                            'plan_status' => 0,
                                                                            'plan_date' => $this->system_date(),
                                                                            'transid' => $transid
                                                                        ];
                                                                        if ($this->inserting_data('message', $trans_history) && $this->inserting_data('bill', $bill_trans)) {
                                                                            $billvend = new BillSend();
                                                                            $bill_data = [
                                                                                'username' => $user->username,
                                                                                'plan_id' => $request->disco,
                                                                                'transid' => $transid
                                                                            ];
                                                                            $response = $billvend->$check_now($bill_data);
                                                                            if (!empty($response)) {
                                                                                if ($response == 'success') {
                                                                                    // --- SMART BENEFICIARY SAVE ---
                                                                                    try {
                                                                                        Beneficiary::updateOrCreate(
                                                                                            [
                                                                                                'user_id' => $user->id,
                                                                                                'service_type' => 'electricity',
                                                                                                'identifier' => $request->meter_number
                                                                                            ],
                                                                                            [
                                                                                                'network_or_provider' => $bill_plan->disco_name,
                                                                                                'last_used_at' => Carbon::now(),
                                                                                            ]
                                                                                        );
                                                                                    } catch (\Exception $e) {
                                                                                        Log::error('Electricity Beneficiary Save Failed: ' . $e->getMessage());
                                                                                    }

                                                                                    DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => 'Transaction successful ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number]);
                                                                                    DB::table('bill')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1]);
                                                                                    $habukhan_forgot = DB::table('bill')->where('transid', $transid)->first();
                                                                                    if (isset($request->is_api) && $request->is_api == true) {
                                                                                        return response()->json([
                                                                                            'status' => 'success',
                                                                                            'message' => 'Transaction Successful',
                                                                                            'transid' => $transid,
                                                                                            'token' => $habukhan_forgot->token,
                                                                                        ]);
                                                                                    }
                                                                                    return response()->json([
                                                                                        'disco_name' => strtoupper($bill_plan->disco_name),
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $request->amount,
                                                                                        'charges' => $charges,
                                                                                        'transid' => $transid,
                                                                                        'status' => 'success',
                                                                                        'message' => 'Transaction  successful ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                                                                                        'meter_number' => $request->meter_number,
                                                                                        'meter_type' => strtoupper($request->meter_type),
                                                                                        'oldbal' => $user->balance,
                                                                                        'newbal' => $debit,
                                                                                        'system' => $system,
                                                                                        'token' => $habukhan_forgot->token,
                                                                                        'wallet_vending' => 'wallet',
                                                                                    ]);
                                                                                } else if ($response == 'proccess') {
                                                                                    return response()->json([
                                                                                        'disco_name' => strtoupper($bill_plan->disco_name),
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $request->amount,
                                                                                        'charges' => $charges,
                                                                                        'status' => 'process',
                                                                                        'message' => 'Transaction on process ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                                                                                        'meter_number' => $request->meter_number,
                                                                                        'meter_type' => strtoupper($request->meter_type),
                                                                                        'oldbal' => $user->balance,
                                                                                        'newbal' => $debit,
                                                                                        'system' => $system,
                                                                                        'wallet_vending' => 'wallet',
                                                                                    ]);
                                                                                } else if ($response == 'fail') {
                                                                                    DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $refund]);
                                                                                    DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund, 'message' => 'Transaction fail ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number]);
                                                                                    DB::table('bill')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund]);
                                                                                    return response()->json([
                                                                                        'disco_name' => strtoupper($bill_plan->disco_name),
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $request->amount,
                                                                                        'charges' => $charges,
                                                                                        'status' => 'fail',
                                                                                        'message' => 'Transaction  fail ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                                                                                        'meter_number' => $request->meter_number,
                                                                                        'meter_type' => strtoupper($request->meter_type),
                                                                                        'oldbal' => $user->balance,
                                                                                        'newbal' => $refund,
                                                                                        'system' => $system,
                                                                                        'wallet_vending' => 'wallet',
                                                                                    ]);
                                                                                } else {
                                                                                    return response()->json([
                                                                                        'disco_name' => strtoupper($bill_plan->disco_name),
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $request->amount,
                                                                                        'charges' => $charges,
                                                                                        'status' => 'process',
                                                                                        'message' => 'Transaction on process ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                                                                                        'meter_number' => $request->meter_number,
                                                                                        'meter_type' => strtoupper($request->meter_type),
                                                                                        'oldbal' => $user->balance,
                                                                                        'newbal' => $debit,
                                                                                        'system' => $system,
                                                                                        'wallet_vending' => 'wallet',
                                                                                    ]);
                                                                                }
                                                                            } else {
                                                                                return response()->json([
                                                                                    'disco_name' => strtoupper($bill_plan->disco_name),
                                                                                    'request-id' => $transid,
                                                                                    'amount' => $request->amount,
                                                                                    'charges' => $charges,
                                                                                    'status' => 'process',
                                                                                    'message' => 'Transaction on process ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                                                                                    'meter_number' => $request->meter_number,
                                                                                    'meter_type' => strtoupper($request->meter_type),
                                                                                    'oldbal' => $user->balance,
                                                                                    'newbal' => $debit,
                                                                                    'system' => $system,
                                                                                    'wallet_vending' => 'wallet',
                                                                                ]);
                                                                            }
                                                                        } else {
                                                                            DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $refund]);
                                                                            DB::table('message')->where('transid', $transid)->delete();
                                                                            DB::table('bill')->where('transid', $transid)->delete();
                                                                            return response()->json([
                                                                                'status' => 'fail',
                                                                                'message' => 'Unable to insert'
                                                                            ])->setStatusCode(403);
                                                                        }
                                                                    } else {
                                                                        return response()->json([
                                                                            'status' => 'fail',
                                                                            'message' => 'unable to debit user'
                                                                        ])->setStatusCode(403);
                                                                    }
                                                                }
                                                            } else {
                                                                return response()->json([
                                                                    'status' => 'fail',
                                                                    'message' => 'Insufficient Account Kindly Fund Your Wallet => ₦' . number_format($user->balance, 2)
                                                                ])->setStatusCode(403);
                                                            }
                                                        } else {
                                                            return response()->json([
                                                                'status' => 'fail',
                                                                'message' => 'Minimum Electricity Purchase for this account is => ₦' . number_format($bill_d->bill_min, 2)
                                                            ])->setStatusCode(403);
                                                        }
                                                    } else {
                                                        return response()->json([
                                                            'status' => 'fail',
                                                            'message' => 'Maximum Electricity Purchase for this account is => ₦' . number_format($bill_d->bill_max, 2)
                                                        ])->setStatusCode(403);
                                                    }
                                                } else {
                                                    return response()->json([
                                                        'status' => 'fail',
                                                        'message' => 'Invalid Amount'
                                                    ])->setStatusCode(403);
                                                }
                                            } else {
                                                return response()->json([
                                                    'status' => 'fail',
                                                    'message' => 'Insufficient Account Kindly Fund Your Wallet => ₦' . number_format($user->balance, 2)
                                                ])->setStatusCode(403);
                                            }
                                        } else {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'Amount Not Detected'
                                            ])->setStatusCode(403);
                                        }
                                    } else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'You have Reach Daily Transaction Limit Kindly Message the Admin To Upgrade Your Account '
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Electricity Bill Not Available Right Now'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Invalid Disco ID'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Referrence ID Used'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Transaction Restricted'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Access Token'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Authorization Header Token Required'
            ])->setStatusCode(403);
        }
    }
}
