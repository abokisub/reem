<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Beneficiary;
use App\Http\Controllers\API\ServiceLockController;


class AirtimePurchase extends Controller
{
    use ApiResponseTrait;

    public function BuyAirtime(Request $request)
    {
        // Check if airtime service is locked
        if (ServiceLockController::isLocked('airtime')) {
            return $this->serviceUnavailableResponse('Airtime');
        }

        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'plan_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0',
                'user_id' => 'required'
            ]);
            $system = "APP";
            file_put_contents('debug_trace.txt', "Step 1: Start. ID: " . $request->user_id . " Amount: " . $request->amount . "\n", FILE_APPEND);

            // Professional Refactor: Use client-provided request-id for idempotency if available
            if ($request->has('request-id')) {
                $transid = $request->input('request-id');
            } else {
                $transid = $this->purchase_ref('AIRTIME_');
            }

            $verified_id = $this->verifyapptoken($request->user_id);
            $check = DB::table('users')->where(['id' => $verified_id, 'status' => 'active']);
            if ($check->count() == 1) {
                $d_token = $check->first();
                if (trim($d_token->pin) == trim($request->pin)) {
                    $accessToken = $d_token->api_key;
                    file_put_contents('debug_trace.txt', "Step 2: Auth Success\n", FILE_APPEND);
                } else {
                    return $this->errorResponse('Invalid Transaction Pin', 403);
                }
            } else {
                $accessToken = 'null';
            }
        } else if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'plan_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0'
            ], [
                'network.required' => 'Network Id Required',
                'phone.required' => 'Phone Number Required',
                'phone.digits' => 'Phone Number Digits Must Be 11',
            ]);
            $system = config('app.name');
            $transid = $this->purchase_ref('AIRTIME_');
            if ($this->core()->allow_pin == 1) {
                // transaction pin required
                $check = DB::table('users')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    if (trim($det->pin) == trim($request->pin)) {
                        $accessToken = $det->api_key;
                    } else {
                        return $this->errorResponse('Invalid Transaction Pin', 403);
                    }
                } else {
                    return $this->errorResponse('Invalid Transaction Pin', 403);
                }
            } else {
                // transaction pin not required
                $check = DB::table('users')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    $accessToken = $det->api_key;
                } else {
                    return $this->errorResponse('An Error Occur', 403);
                }
            }
        } else {
            // api verification
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'plan_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0',
                'request-id' => 'required|unique:airtime,transid'
            ]);
            $system = "API";
            $id = "request-id";
            $transid = $request->$id;
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));
        }
        
        // carry out transaction
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->first());
        }
        
        if ($accessToken) {
            $user = DB::table('users')->where(function ($query) use ($accessToken) {
                $query->where('api_key', $accessToken)
                    ->orWhere('app_key', $accessToken)
                    ->orWhere('habukhan_key', $accessToken);
            })->where('status', 1)->sharedLock()->first();
            
            if ($user) {
                $company_blocked = false;
                $user_company = DB::table('companies')->where('user_id', $user->id)->first();
                if ($user_company && DB::table('banned_companies')->where('company_id', $user_company->id)->exists()) {
                    $company_blocked = true;
                }

                if (!$company_blocked) {
                    // declare user type
                    if (in_array(strtoupper($user->type), ['SMART', 'USER', 'CUSTOMER'])) {
                        $user_type = 'smart';
                    } else if (strtoupper($user->type) == 'AGENT') {
                        $user_type = 'agent';
                    } else if (strtoupper($user->type) == 'AWUF') {
                        $user_type = 'awuf';
                    } else if (strtoupper($user->type) == 'API') {
                        $user_type = 'api';
                    } else {
                        $user_type = 'special';
                    }
                    
                    if (DB::table('airtime')->where('transid', $transid)->count() == 0 and DB::table('message')->where('transid', $transid)->count() == 0) {
                        return $this->processAirtimePurchase($request, $user, $transid, $system, $user_type);
                    } else {
                        return $this->errorResponse('Transaction Plan Id Exits', 403);
                    }
                } else {
                    return $this->errorResponse('Transaction Restricted', 403);
                }
            } else {
                return $this->unauthorizedResponse('Invalid Access Token');
            }
        } else {
            return $this->unauthorizedResponse('Authorization Header Token Required');
        }
    }

    private function processAirtimePurchase($request, $user, $transid, $system, $user_type)
    {
        try {
            // declare all variable
            $network = $request->network;
            $phone = $request->phone;
            if ($request->bypass == true || $request->bypass == 'true') {
                $bypass = true;
            } else {
                $bypass = false;
            }
            $plan_type = strtolower($request->plan_type);
            $amount = $request->amount;

            $cid = $user->active_company_id ?? 1;
            // check if network exits before
            $network_d = DB::table('network')->where(['plan_id' => $network, 'company_id' => $cid])->first();
            if (!$network_d) {
                $network_d = DB::table('network')->where(['plan_id' => $network, 'company_id' => 1])->first();
            }

            if (!$network_d) {
                return $this->notFoundResponse('Selected network');
            }

            file_put_contents('debug_trace.txt', "Step 3: Network Found: $network\n", FILE_APPEND);

            if (!in_array($plan_type, ['vtu', 'sns'])) {
                return $this->errorResponse('Invalid Network Plan Type', 403);
            }

            // lock services
            if ($plan_type == 'vtu') {
                $habukhan_lock = "network_vtu";
            } else {
                $habukhan_lock = 'network_share';
            }

            // check number validation
            if (!$this->validatePhoneNumber($phone, $network_d->network, $bypass)) {
                return $this->errorResponse('Invalid phone number for ' . $network_d->network, 403);
            }

            if (substr($phone, 0, 1) != 0) {
                return $this->errorResponse('Invalid Phone Number => ' . $phone, 403);
            }

            // Process transaction
            return $this->executeTransaction($request, $user, $network_d, $transid, $system, $user_type, $plan_type, $amount, $phone, $habukhan_lock);

        } catch (\Exception $e) {
            Log::error('Airtime Purchase Error', [
                'user_id' => $user->id ?? 'unknown',
                'transaction_id' => $transid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->transactionFailedResponse('System error occurred during transaction processing');
        }
    }

    private function validatePhoneNumber($phone, $network, $bypass)
    {
        if ($bypass || $phone == '08011111111') {
            return true;
        }

        $validate = substr($phone, 0, 4);
        
        switch ($network) {
            case "MTN":
                return strpos(" 0702 0703 0713 0704 0706 0707 0716 0802 0803 0806 0810 0813 0814 0816 0903 0913 0906 0916 0804 ", $validate) !== FALSE && strlen($phone) == 11;
            case "GLO":
                return strpos(" 0805 0705 0905 0807 0907 0707 0817 0917 0717 0715 0815 0915 0811 0711 0911 ", $validate) !== FALSE && strlen($phone) == 11;
            case "AIRTEL":
                return strpos(" 0904 0802 0902 0702 0808 0911 0908 0708 0918 0818 0718 0812 0912 0712 0801 0701 0901 0907 0917 ", $validate) !== FALSE && strlen($phone) == 11;
            case "9MOBILE":
                return strpos(" 0809 0909 0709 0819 0919 0719 0817 0917 0717 0718 0918 0818 0808 0708 0908 ", $validate) !== FALSE && strlen($phone) == 11;
            default:
                return false;
        }
    }

    private function executeTransaction($request, $user, $network_d, $transid, $system, $user_type, $plan_type, $amount, $phone, $habukhan_lock)
    {
        DB::beginTransaction();
        
        try {
            $user = DB::table('users')->where(['id' => $user->id])->lockForUpdate()->first();
            
            if ($network_d->$habukhan_lock != 1) {
                DB::rollback();
                return $this->serviceUnavailableResponse($network_d->network . ' ' . strtoupper($plan_type));
            }

            if (!is_numeric($user->balance)) {
                DB::rollback();
                return $this->errorResponse('Unknown Account Balance', 403);
            }

            if ($amount <= 0) {
                DB::rollback();
                return $this->errorResponse('invalid amount', 403);
            }

            // Get discount configuration
            $discount = DB::table('airtime_discount')->where('company_id', $user->active_company_id)->first();
            if (!$discount) {
                $discount = DB::table('airtime_discount')->where('company_id', 1)->first();
            }

            if (!$discount) {
                DB::rollback();
                return $this->errorResponse('Airtime discount configuration not found', 500);
            }

            // Check amount limits
            if ($amount < $discount->min_airtime) {
                DB::rollback();
                return $this->invalidInputResponse('amount', 'Minimum airtime purchase amount is ₦' . number_format($discount->min_airtime, 2) . '. Please enter a higher amount.');
            }

            if ($amount > $discount->max_airtime) {
                DB::rollback();
                return $this->invalidInputResponse('amount', 'Maximum airtime purchase amount is ₦' . number_format($discount->max_airtime, 2) . '. Please enter a lower amount.');
            }

            // Calculate discount
            if ($plan_type == 'sns') {
                $type = 'share';
            } else {
                $type = $plan_type;
            }
            
            if ($network_d->network == '9MOBILE') {
                $real_network = 'mobile';
            } else {
                $real_network = $network_d->network;
            }
            
            $check_for_me = strtolower($real_network) . "_" . strtolower($type) . "_" . strtolower($user_type);
            $discount_amount = ($request->amount / 100) * $discount->$check_for_me;

            // Check balance
            if ($user->balance < $discount_amount) {
                DB::rollback();
                return $this->insufficientBalanceResponse($user->balance, $discount_amount);
            }

            file_put_contents('debug_trace.txt', "Step 5: Balance Sufficient\n", FILE_APPEND);
            
            $debit = $user->balance - $discount_amount;
            $refund = $debit + $discount_amount;
            
            // Prepare transaction records
            $trans_history = [
                'username' => $user->username,
                'amount' => $amount,
                'message' => 'Transaction on process ' . $network_d->network . ' ' . strtoupper($plan_type) . ' to ' . $phone,
                'phone_account' => $phone,
                'oldbal' => $user->balance,
                'newbal' => $debit,
                'habukhan_date' => $this->system_date(),
                'plan_status' => 0,
                'transid' => $transid,
                'role' => 'airtime'
            ];
            
            $airtime_history = [
                'username' => $user->username,
                'network' => $network_d->network,
                'network_type' => strtoupper($plan_type),
                'amount' => $amount,
                'oldbal' => $user->balance,
                'newbal' => $debit,
                'discount' => $discount_amount,
                'transid' => $transid,
                'plan_date' => $this->system_date(),
                'plan_status' => 0,
                'plan_phone' => $phone,
                'system' => $system
            ];

            // Debit user
            file_put_contents('debug_trace.txt', "Step 5.1: Attempting to debit user ID: {$user->id}, current balance: {$user->balance}, new balance: {$debit}\n", FILE_APPEND);
            $updateResult = DB::table('users')->where(['id' => $user->id])->update(['balance' => $debit]);
            file_put_contents('debug_trace.txt', "Step 5.2: Update result: " . ($updateResult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            
            if (!$updateResult) {
                DB::rollback();
                Log::error('Airtime Purchase: Failed to debit user balance', [
                    'user_id' => $user->id,
                    'current_balance' => $user->balance,
                    'debit_amount' => $discount_amount,
                    'new_balance' => $debit
                ]);
                return $this->transactionFailedResponse('Unable to process payment');
            }

            file_put_contents('debug_trace.txt', "Step 6: Debit Success\n", FILE_APPEND);

            // Insert transaction records
            if (!($this->inserting_data('message', $trans_history) && $this->inserting_data('airtime', $airtime_history))) {
                // refund user here
                DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $refund]);
                DB::rollback();
                return $this->errorResponse('kindly re try after some mins', 403);
            }

            DB::commit();

            // Process airtime purchase
            return $this->processAirtimeSend($transid, $user, $network_d, $plan_type, $type, $amount, $phone, $debit, $discount_amount, $system, $refund);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Airtime Purchase Transaction Error', [
                'user_id' => $user->id ?? 'unknown',
                'transaction_id' => $transid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            file_put_contents('debug_trace.txt', "Transaction Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return $this->transactionFailedResponse('System error occurred during transaction processing');
        }
    }

    private function processAirtimeSend($transid, $user, $network_d, $plan_type, $type, $amount, $phone, $debit, $discount_amount, $system, $refund)
    {
        try {
            // purchase data now
            $sending_data = [
                'transid' => $transid,
                'username' => $user->username
            ];
            
            $habukhanm = new AirtimeSend();
            $airtime_sel = DB::table('airtime_sel')->first();
            
            if ($network_d->network == '9MOBILE') {
                $real_network = 'mobile';
            } else {
                $real_network = $network_d->network;
            }
            
            $v_key = strtolower($real_network) . "_" . ($plan_type == 'sns' ? 'share' : 'vtu');
            $check_now = $airtime_sel->$v_key ?? 'Habukhan1';
            $response = $habukhanm->$check_now($sending_data);
            
            if (!empty($response)) {
                if ($response == 'success') {
                    // Save beneficiary
                    try {
                        Beneficiary::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'service_type' => 'airtime',
                                'identifier' => $phone
                            ],
                            [
                                'network_or_provider' => $network_d->network,
                                'last_used_at' => Carbon::now(),
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::error('Airtime Beneficiary Save Failed: ' . $e->getMessage());
                    }

                    // Update transaction status
                    DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => 'successfully purchase ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone . ' , ₦' . $amount]);
                    DB::table('airtime')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1]);
                    
                    return response()->json([
                        'network' => $network_d->network,
                        'request-id' => $transid,
                        'amount' => $amount,
                        'transid' => $transid,
                        'discount' => $discount_amount,
                        'status' => 'success',
                        'message' => 'successfully purchase ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone . ' , ₦' . $amount,
                        'phone_number' => $phone,
                        'oldbal' => $user->balance,
                        'newbal' => $debit,
                        'system' => $system,
                        'plan_type' => strtoupper($plan_type),
                        'wallet_vending' => "wallet"
                    ]);
                } else if ($response == 'process') {
                    return response()->json([
                        'network' => $network_d->network,
                        'request-id' => $transid,
                        'amount' => $amount,
                        'discount' => $discount_amount,
                        'status' => 'process',
                        'message' => 'Transaction on process ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone,
                        'phone_number' => $phone,
                        'oldbal' => $user->balance,
                        'newbal' => $debit,
                        'system' => $system,
                        'wallet_vending' => 'wallet',
                        'plan_type' => strtoupper($plan_type),
                    ]);
                } else if ($response == 'fail') {
                    $check_fail = DB::table('airtime')->where(['username' => $user->username, 'transid' => $transid])->first();
                    if ($check_fail->plan_status != 2) {
                        $admin_refund = DB::table('users')->where(['id' => $user->id])->first();
                        DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $admin_refund->balance + $discount_amount]);
                        DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'message' => 'Transaction fail ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone . ' , ₦' . $amount, 'newbal' => $refund]);
                        DB::table('airtime')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund]);
                    }
                    return response()->json([
                        'network' => $network_d->network,
                        'request-id' => $transid,
                        'amount' => $amount,
                        'discount' => $discount_amount,
                        'status' => 'fail',
                        'message' => 'Transaction fail ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone . ' , ₦' . $amount,
                        'phone_number' => $phone,
                        'oldbal' => $user->balance,
                        'newbal' => $refund,
                        'system' => $system,
                        'wallet_vending' => "wallet"
                    ]);
                } else {
                    return response()->json([
                        'network' => $network_d->network,
                        'request-id' => $transid,
                        'amount' => $amount,
                        'discount' => $discount_amount,
                        'status' => 'process',
                        'message' => 'Transaction on process ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone,
                        'phone_number' => $phone,
                        'oldbal' => $user->balance,
                        'newbal' => $debit,
                        'system' => $system,
                        'wallet_vending' => 'wallet',
                        'plan_type' => strtoupper($plan_type),
                    ]);
                }
            } else {
                return response()->json([
                    'network' => $network_d->network,
                    'request-id' => $transid,
                    'amount' => $amount,
                    'discount' => $discount_amount,
                    'status' => 'process',
                    'message' => 'Transaction on process ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone,
                    'phone_number' => $phone,
                    'oldbal' => $user->balance,
                    'newbal' => $debit,
                    'system' => $system,
                    'wallet_vending' => 'wallet',
                    'plan_type' => strtoupper($plan_type),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Airtime Send Error', [
                'transaction_id' => $transid,
                'error' => $e->getMessage()
            ]);
            return $this->transactionFailedResponse('Error processing airtime purchase');
        }
    }
}