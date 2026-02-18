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


class CablePurchase extends Controller
{

    public function BuyCable(Request $request)
    {
        // Check if cable service is locked
        if (ServiceLockController::isLocked('cable')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cable TV service is currently unavailable. Please try again later.'
            ], 503);
        }

        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $validator = Validator::make($request->all(), [
                'cable' => 'required',
                'iuc' => 'required',
                'bypass' => 'required',
                'cable_plan' => 'required',
                'user_id' => 'required'
            ]);
            $system = "APP";
            // Professional Refactor: Use client-provided request-id for idempotency if available
            if ($request->has('request-id')) {
                $transid = $request->input('request-id');
            } else {
                $transid = $this->purchase_ref('CABLE_');
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
                'cable' => 'required',
                'iuc' => 'required',
                'bypass' => 'boolean|required',
                'cable_plan' => 'required',
            ]);
            $system = config('app.name');
            $transid = $this->purchase_ref('CABLE_');
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
                'cable' => 'required',
                'iuc' => 'required',
                'bypass' => 'required',
                'cable_plan' => 'required',
                'request-id' => 'required|unique:cable,transid'
            ]);
            $system = "API";
            $id = "request-id";
            $transid = $request->$id;
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));
        }
        // Generate a unique reference/transaction ID at the start
        $reference = 'CABLE_' . substr(md5(uniqid(mt_rand(), true)), 0, 13);

        // carry out transaction
        if ($accessToken) {
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 'fail',
                    'reference' => $reference,
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
                        if (DB::table('cable_id')->where('id', $request->cable)->count() == 1) {
                            if (DB::table('cable')->where('transid', $transid)->count() == 0 and DB::table('message')->where('transid', $transid)->count() == 0) {
                                $cable = DB::table('cable_id')->where(['plan_id' => $request->cable, 'company_id' => $cid])->first();
                                if (!$cable) {
                                    $cable = DB::table('cable_id')->where(['plan_id' => $request->cable, 'company_id' => 1])->first();
                                }

                                $cable_name = strtolower($cable->cable_name);
                                $cable_plan = DB::table('cable_plan')->where(['plan_id' => $request->cable_plan, 'cable_name' => $cable->cable_name, 'plan_status' => 1, 'company_id' => $cid])->first();
                                if (!$cable_plan) {
                                    $cable_plan = DB::table('cable_plan')->where(['plan_id' => $request->cable_plan, 'cable_name' => $cable->cable_name, 'plan_status' => 1, 'company_id' => 1])->first();
                                }

                                if ($cable_plan) {
                                    // check if lock
                                    $cable_lock = DB::table('cable_result_lock')->first();
                                    if ($cable_lock->$cable_name == 1) {
                                        if (is_numeric($user->balance)) {
                                            if ($user->balance > 0) {
                                                $habukhan_new_go = true;

                                                if ($habukhan_new_go == true) {
                                                    if (!empty($cable_plan->plan_price)) {
                                                        $cable_setting = DB::table('cable_charge')->where('company_id', $cid)->first();
                                                        if (!$cable_setting) {
                                                            $cable_setting = DB::table('cable_charge')->where('company_id', 1)->first();
                                                        }
                                                        if ($cable_setting->direct == 1) {
                                                            $charges = $cable_setting->$cable_name;
                                                        } else {
                                                            $charges = ($cable_plan->plan_price / 100) * $cable_setting->$cable_name;
                                                        }
                                                        $total_amount = $charges + $cable_plan->plan_price;
                                                        DB::beginTransaction();
                                                        $user = DB::table('users')->where(['id' => $user->id])->lockForUpdate()->first();
                                                        if ($user->balance >= $total_amount) {
                                                            // check cutomer name
                                                            $cable_sel = DB::table('cable_sel')->first();
                                                            $adm = new IUCsend();
                                                            $check_now = $cable_sel->$cable_name;
                                                            $sending_data = [
                                                                'iuc' => $request->iuc,
                                                                'cable' => $request->cable
                                                            ];
                                                            if (method_exists($adm, $check_now)) {
                                                                $customer_name = $adm->$check_now($sending_data);
                                                            } else {
                                                                \Log::error("CablePurchase IUC Error: Method {$check_now} does not exist in IUCsend.");
                                                                $customer_name = null;
                                                            }
                                                            \Log::info("Cable Purchase Validation - Cable: {$cable_name}, Method: {$check_now}, Customer Name: {$customer_name}, Bypass: " . ($request->bypass ? 'true' : 'false'));
                                                            if ((empty($customer_name)) && ($request->bypass == false || $request->bypass == 'false')) {
                                                                $errorMessage = (strpos($cable_name, 'showmax') !== false)
                                                                    ? 'Invalid Phone Number or Service Unavailable'
                                                                    : 'Invalid IUC Number or Service Unavailable';
                                                                return response()->json([
                                                                    'status' => 'fail',
                                                                    'message' => $errorMessage
                                                                ])->setStatusCode(403);
                                                            } else {
                                                                // debit user
                                                                $debit = $user->balance - $total_amount;
                                                                $refund = $debit + $total_amount;
                                                                if (DB::table('users')->where(['id' => $user->id])->update(['balance' => $debit])) {
                                                                    DB::commit();
                                                                    $trans_history = [
                                                                        'username' => $user->username,
                                                                        'amount' => $total_amount,
                                                                        'message' => 'Transaction on process ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                                                                        'phone_account' => $request->iuc,
                                                                        'oldbal' => $user->balance,
                                                                        'newbal' => $debit,
                                                                        'habukhan_date' => $this->system_date(),
                                                                        'plan_status' => 0,
                                                                        'transid' => $transid,
                                                                        'role' => 'cable'
                                                                    ];
                                                                    $cable_trans = [
                                                                        'username' => $user->username,
                                                                        'amount' => $cable_plan->plan_price,
                                                                        'charges' => $charges,
                                                                        'cable_name' => strtoupper($cable_name),
                                                                        'cable_plan' => $cable_plan->plan_name,
                                                                        'plan_status' => 0,
                                                                        'iuc' => $request->iuc,
                                                                        'plan_date' => $this->system_date(),
                                                                        'transid' => $transid,
                                                                        'customer_name' => $customer_name,
                                                                        'system' => $system,
                                                                        'oldbal' => $user->balance,
                                                                        'newbal' => $debit,
                                                                    ];
                                                                    if ($this->inserting_data('message', $trans_history) && $this->inserting_data('cable', $cable_trans)) {
                                                                        $sender = new CableSend();
                                                                        $user_info = [
                                                                            'username' => $user->username,
                                                                            'transid' => $transid,
                                                                            'plan_id' => $request->cable_plan
                                                                        ];
                                                                        if (method_exists($sender, $check_now)) {
                                                                            $response = $sender->$check_now($user_info);
                                                                        } else {
                                                                            \Log::error("CablePurchase Error: Method {$check_now} does not exist in CableSend.");
                                                                            $response = 'fail';
                                                                        }
                                                                        if (!empty($response)) {
                                                                            if ($response == 'success') {
                                                                                // --- SMART BENEFICIARY SAVE ---
                                                                                try {
                                                                                    Beneficiary::updateOrCreate(
                                                                                        [
                                                                                            'user_id' => $user->id,
                                                                                            'service_type' => 'tv',
                                                                                            'identifier' => $request->iuc
                                                                                        ],
                                                                                        [
                                                                                            'network_or_provider' => $cable->cable_name,
                                                                                            'last_used_at' => Carbon::now(),
                                                                                        ]
                                                                                    );
                                                                                } catch (\Exception $e) {
                                                                                    Log::error('Cable Beneficiary Save Failed: ' . $e->getMessage());
                                                                                }

                                                                                DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => 'successfully purchase ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc]);
                                                                                DB::table('cable')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1]);
                                                                                return response()->json([
                                                                                    'cable_name' => strtoupper($cable_name),
                                                                                    'request-id' => $transid,
                                                                                    'amount' => $cable_plan->plan_price,
                                                                                    'charges' => $charges,
                                                                                    'status' => 'success',
                                                                                    'transid' => $transid,
                                                                                    'message' => 'successfully purchase ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                                                                                    'iuc' => $request->iuc,
                                                                                    'oldbal' => $user->balance,
                                                                                    'newbal' => $debit,
                                                                                    'system' => $system,
                                                                                    'wallet_vending' => 'wallet',
                                                                                    'plan_name' => $cable_plan->plan_name,
                                                                                    'reference' => $reference,
                                                                                ]);
                                                                            } else if ($response == 'process') {
                                                                                return response()->json([
                                                                                    'cabl_name' => strtoupper($cable_name),
                                                                                    'request-id' => $transid,
                                                                                    'amount' => $cable_plan->plan_price,
                                                                                    'charges' => $charges,
                                                                                    'status' => 'process',
                                                                                    'message' => 'Transaction on process ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                                                                                    'iuc' => $request->iuc,
                                                                                    'oldbal' => $user->balance,
                                                                                    'newbal' => $debit,
                                                                                    'system' => $system,
                                                                                    'wallet_vending' => 'wallet',
                                                                                    'plan_name' => $cable_plan->plan_name,
                                                                                    'reference' => $reference,
                                                                                ]);
                                                                            } else if ($response == 'fail') {
                                                                                DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $refund]);
                                                                                DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund, 'message' => 'Transaction fail ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc]);
                                                                                DB::table('cable')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund]);
                                                                                return response()->json([
                                                                                    'cabl_name' => strtoupper($cable_name),
                                                                                    'request-id' => $transid,
                                                                                    'amount' => $cable_plan->plan_price,
                                                                                    'charges' => $charges,
                                                                                    'status' => 'fail',
                                                                                    'message' => 'Transaction fail ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                                                                                    'iuc' => $request->iuc,
                                                                                    'oldbal' => $user->balance,
                                                                                    'newbal' => $refund,
                                                                                    'system' => $system,
                                                                                    'wallet_vending' => 'wallet',
                                                                                    'plan_name' => $cable_plan->plan_name,
                                                                                    'reference' => $reference,
                                                                                ]);
                                                                            } else {
                                                                                return response()->json([
                                                                                    'cabl_name' => strtoupper($cable_name),
                                                                                    'request-id' => $transid,
                                                                                    'amount' => $cable_plan->plan_price,
                                                                                    'charges' => $charges,
                                                                                    'status' => 'process',
                                                                                    'message' => 'Transaction on process ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                                                                                    'iuc' => $request->iuc,
                                                                                    'oldbal' => $user->balance,
                                                                                    'newbal' => $debit,
                                                                                    'system' => $system,
                                                                                    'wallet_vending' => 'wallet',
                                                                                    'plan_name' => $cable_plan->plan_name,
                                                                                    'reference' => $reference,
                                                                                ]);
                                                                            }
                                                                        } else {
                                                                            return response()->json([
                                                                                'cabl_name' => strtoupper($cable_name),
                                                                                'request-id' => $transid,
                                                                                'amount' => $cable_plan->plan_price,
                                                                                'charges' => $charges,
                                                                                'status' => 'process',
                                                                                'message' => 'Transaction on process ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                                                                                'iuc' => $request->iuc,
                                                                                'oldbal' => $user->balance,
                                                                                'newbal' => $debit,
                                                                                'system' => $system,
                                                                                'wallet_vending' => 'wallet',
                                                                                'plan_name' => $cable_plan->plan_name,
                                                                                'reference' => $reference,
                                                                            ]);
                                                                        }
                                                                    } else {
                                                                        DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $refund]);
                                                                        DB::table('message')->where('transid', $transid)->delete();
                                                                        DB::table('cable')->where('transid', $transid)->delete();
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
                                                    'message' => 'Insufficient Account Kindly Fund Your Wallet => ₦' . number_format($user->balance, 2)
                                                ])->setStatusCode(403);
                                            }
                                        } else {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'Invalid account number'
                                            ])->setStatusCode(403);
                                        }
                                    } else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => strtoupper($cable_name) . " is not available right now"
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'invalid cable plan id'
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
                                'message' => 'Invalid Cable Plan ID'
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
