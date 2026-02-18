<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Imports\MonnifyImport;
use Maatwebsite\Excel\Facades\Excel;
// use App\Jobs\ProcessKudaWithdrawal;

use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseService;

class PaymentController extends Controller
{
    public function Xixapay(Request $request)
    {
        // Retrieve the raw payload from the request
        $payload = $request->getContent();

        // Define the secret key from environment
        $secret = env('XIXAPAY_SECRET_KEY');

        // Retrieve the XixaPay signature from the request headers
        $xixapay_signature = $request->header('xixapay');
        // Log the incoming payload for debugging

        // Compute the hash key using the payload and secret key
        $hashkey = hash_hmac('sha256', $payload, $secret);

        // Compare the computed hash key with the received signature
        if ($xixapay_signature !== $hashkey) {
            return response()->json('Unknown source', 403);
        }

        // Decode the payload into an associative array
        $data = json_decode($payload, true);

        // Retrieve key data from the payload
        $status = $data['notification_status'];
        $amount_paid = floatval($data['amount_paid']);
        $reference = $data['transaction_id'];
        $customer_email = $data['customer']['email'];

        // Check if the transaction reference already exists
        if (DB::table('transactions')->where('external_reference', $reference)->exists()) {
            return response()->json('Transaction Ref Exists', 403);
        }

        // Check if the user exists and is active
        if (!DB::table('users')->where(['email' => $customer_email, 'status' => 'active'])->exists()) {
            return response()->json('Unable to find user', 403);
        }

        // Fetch user details
        $user = DB::table('users')->where(['email' => $customer_email, 'status' => 'active'])->first();

        // Compute charges and credit amount from dynamic settings
        $charges = $this->core()->xixapay_charge ?? 60;

        $credit = $amount_paid - $charges;

        // Generate a unique transaction ID
        $transid = $this->purchase_ref('AUTOMATED_');

        // Insert transaction record
        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'company_id' => $user->active_company_id,
            'reference' => $transid,
            'external_reference' => $reference,
            'type' => 'credit',
            'category' => 'funding',
            'amount' => $amount_paid,
            'fee' => $charges,
            'total_amount' => $credit,
            'currency' => 'NGN',
            'status' => 'success',
            'description' => 'Automated Bank Transfer Funding',
            'balance_before' => $user->balance,
            'balance_after' => $user->balance + $credit,
            'metadata' => [
                'wallet_type' => 'User Wallet',
                'credit_by' => 'Palmpay Automated Bank Transfer',
                'provider' => 'Xixapay'
            ],
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update user balance
        DB::table('users')->where(['id' => $user->id])->update(['balance' => $user->balance + $credit]);

        // Insert notifications and messages
        DB::table('notif')->insert([
            'username' => $user->username,
            'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
            'date' => $this->system_date(),
            'habukhan' => 0
        ]);

        DB::table('message')->insert([
            'username' => $user->username,
            'amount' => $credit,
            'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
            'oldbal' => $user->balance,
            'newbal' => $user->balance + $credit,
            'habukhan_date' => $this->system_date(),
            'plan_status' => 1,
            'transid' => $transid,
            'phone_account' => 'Automated Funding',
            'role' => 'credit'
        ]);

        // Send Email Receipt
        $email_data = [
            'email' => $user->email,
            'username' => $user->username,
            'title' => 'Wallet Funding Success',
            'amount' => $amount_paid,
            'charges' => $charges,
            'newbal' => $user->balance + $credit,
            'transid' => $transid,
            'date' => $this->system_date(),
            'mes' => "Your wallet has been credited with ₦" . number_format($credit, 2) . " via PalmPay/Kolomoni (Xixapay)."
        ];
        MailController::send_mail($email_data, 'email.purchase');

        // Send FCM notification if applicable (Modern Admin SDK)

        // Send FCM notification if applicable (Modern Admin SDK)
        if ($user->app_token != null) {
            $firebase = new FirebaseService();
            $firebase->sendNotification(
                $user->app_token,
                config('app.name'),
                "You have received a payment of ₦" . number_format($credit, 2),
                [
                    'type' => 'transaction',
                    'action' => 'deposit',
                    'channel_id' => 'high_importance_channel'
                ]
            );
        }

        // Handle referral
        if ($this->core()->referral == 1 && $user->ref) {
            if (DB::table('transactions')->where(['user_id' => $user->id, 'status' => 'success', 'category' => 'funding'])->count() == 1) {
                if (DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->exists()) {
                    $user_ref = DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->first();
                    $credit_ref = ($credit / 100) * $this->core()->referral_price;
                    DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);

                    DB::table('message')->insert([
                        'username' => $user_ref->username,
                        'amount' => $credit_ref,
                        'message' => 'Referral Earning From ' . ucfirst($user->username),
                        'oldbal' => $user_ref->referral_balance,
                        'newbal' => $user_ref->referral_balance + $credit_ref,
                        'habukhan_date' => $this->system_date(),
                        'plan_status' => 1,
                        'transid' => $this->purchase_ref('EARNING_'),
                        'role' => 'credit'
                    ]);
                }
            }
        }

        return response()->json('ok');
    }

    public function PaymentPointWebhook(Request $request)
    {
        return response()->json('Service disabled', 403);
    }
    public function BankTransfer(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $main_validator = validator::make($request->all(), [
                    'account_number' => 'required',
                    'bank_name' => 'required',
                    'bank_code' => 'required',
                    'amount_sent' => 'required|numeric'
                ]);
                if ($main_validator->fails()) {
                    return response()->json([
                        'message' => $main_validator->errors()->first(),
                        'status' => 403
                    ])->setStatusCode(403);
                } else {
                    $send_request = "https://api.monnify.com/api/v1/disbursements/account/validate?accountNumber=$request->account_number&bankCode=$request->bank_code";
                    $json_response = json_decode(@file_get_contents($send_request), true);
                    if (!empty($json_response)) {
                        if ($json_response['requestSuccessful'] == true) {
                            $transid = $this->purchase_ref('Bank_');
                            $data_bank = [
                                'account_number' => $request->account_number,
                                'bank_name' => $request->bank_name,
                                'bank_code' => $request->bank_code,
                                'account_name' => $json_response['responseBody']['accountName'],
                                'amount' => $request->amount_sent,
                                'date' => $this->system_date(),
                                'plan_status' => 0,
                                'username' => $user->username,
                                'transid' => $transid
                            ];
                            DB::table('bank_transfer')->insert($data_bank);
                            $admins = DB::table('users')->where(['status' => 'active'])->where(function ($query) {
                                $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                            })->get();
                            foreach ($admins as $admin) {
                                $email_data = [
                                    'email' => $admin->email,
                                    'username' => $user->username,
                                    'title' => 'Manual Bank Transfer',
                                    'sender_mail' => $this->general()->app_email,
                                    'app_name' => $this->general()->app_name,
                                    'mes' => $user->username . " Transferred  ₦" . number_format($request->amount_sent, 2) . " to your bank account. Reference is => " . $transid
                                ];
                                MailController::send_mail($email_data, 'email.purchase');
                            }

                            DB::table('request')->insert(['username' => $user->username, 'message' => $user->username . " Transferred  ₦" . number_format($request->amount_sent, 2) . " to your bank account. Reference is => " . $transid, 'date' => $this->system_date(), 'transid' => $transid, 'status' => 'pending', 'title' => 'MANUAL BANK TRANSFER']);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Inavlid Account Details'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Inavlid Account Details'
                        ])->setStatusCode(403);
                    }
                }
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Reload the browser and try again'
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
    public function ATM(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $habukhan_key = DB::table('habukhan_key')->first();
                $main_validator = validator::make($request->all(), [
                    'amount' => "required|numeric|min:$habukhan_key->min|max:$habukhan_key->max",
                ]);
                $transid = $this->purchase_ref('ATM_');
                if (DB::table('message')->where('transid', $transid)->count() == 0 and DB::table('deposit')->where('transid', $transid)->count() == 0) {
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $post_data = array(
                            "amount" => $request->amount,
                            "customerName" => $user->username,
                            "customerEmail" => $user->email,
                            "paymentReference" => $transid,
                            "paymentDescription" => "ATM PAYMENT GATEWAY",
                            "currencyCode" => "NGN",
                            "contractCode" => $habukhan_key->mon_con_num,
                            "redirectUrl" => url('') . "/api/monnify/callback",
                            "paymentMethods" => ["CARD"]
                        );
                        $url = "https://api.monnify.com/api/v1/merchant/transactions/init-transaction";
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));  //send requrest to monnify
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $headers = [
                            'Authorization: Basic ' . base64_encode($habukhan_key->mon_app_key . ':' . $habukhan_key->mon_sk_key),
                            'Content-Type: application/json',
                        ];
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        $get_res = curl_exec($ch);
                        curl_close($ch);
                        $response = json_decode($get_res, true);
                        if ($response) {
                            if ($response['responseMessage'] == 'success') {
                                $monify_ref = $response['responseBody']['transactionReference'];
                                // Record Transaction (PENDING)
                                \App\Models\Transaction::create([
                                    'user_id' => $user->id,
                                    'company_id' => $user->active_company_id,
                                    'reference' => $transid,
                                    'external_reference' => $monify_ref,
                                    'type' => 'credit',
                                    'category' => 'funding',
                                    'amount' => $request->amount,
                                    'fee' => ($request->amount / 100) * $this->core()->monnify_charge,
                                    'total_amount' => $request->amount - (($request->amount / 100) * $this->core()->monnify_charge),
                                    'currency' => 'NGN',
                                    'status' => 'pending',
                                    'description' => 'Monnify ATM Funding',
                                    'balance_before' => $user->balance,
                                    'balance_after' => $user->balance, // No change yet
                                    'metadata' => [
                                        'wallet_type' => 'User Wallet',
                                        'credit_by' => 'Monnify'
                                    ],
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                                return response()->json([
                                    'status' => 'success',
                                    'redirect' => $response['responseBody']['checkoutUrl']
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Try Again Later'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Monnify Server Down'
                            ])->setStatusCode(403);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Reload the browser and try again'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Reload the browser and try again'
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
    public function MonnifyATM(Request $request)
    {
        if ($request->paymentReference) {
            if (DB::table('transactions')->where(['external_reference' => $request->paymentReference, 'status' => 'pending'])->count() == 1) {
                $deposit_trans = DB::table('transactions')->where(['external_reference' => $request->paymentReference, 'status' => 'pending'])->first();

                $sender = "https://api.monnify.com/api/v2/transactions/" . urlencode("$request->paymentReference");
                $habukhan_key = DB::table('habukhan_key')->first();
                $base_monnify = base64_encode($habukhan_key->mon_app_key . ':' . $habukhan_key->mon_sk_key);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.monnify.com/api/v1/auth/login');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    [
                        "Authorization: Basic " . $base_monnify,
                    ]
                );
                $json = curl_exec($ch);
                curl_close($ch);
                $result = json_decode($json, true);
                if (isset($result['responseBody']['accessToken'])) {
                    $accessToken = $result['responseBody']['accessToken'];
                } else {
                    $accessToken = null;
                }
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $sender,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $accessToken,
                        "Content-Type: application/json"
                    ),
                ));
                $response = json_decode(curl_exec($curl), true);
                if (isset($response)) {
                    $trans_status = $response['responseBody']['paymentStatus'];
                    if (strtolower($trans_status) == 'paid') {
                        $credit = $deposit_trans->amount - $deposit_trans->fee;
                        $user = DB::table('users')->where(['id' => $deposit_trans->user_id, 'status' => 'active'])->first();
                        DB::table('transactions')->where(['id' => $deposit_trans->id])->update([
                            'status' => 'success',
                            'balance_before' => $user->balance,
                            'balance_after' => $user->balance + $credit,
                            'processed_at' => now(),
                            'updated_at' => now()
                        ]);
                        DB::table('users')->where(['id' => $user->id])->update(['balance' => $user->balance + $credit]);
                        DB::table('notif')->insert([
                            'username' => $user->username,
                            'message' => 'Account Credited By Monnify ATM ₦' . number_format($credit, 2),
                            'date' => $this->system_date(),
                            'habukhan' => 0
                        ]);
                        DB::table('message')->insert([
                            'username' => $user->username,
                            'amount' => $credit,
                            'message' => 'Account Credited By Monnify ATM ₦' . number_format($credit, 2),
                            'oldbal' => $user->balance,
                            'newbal' => $user->balance + $credit,
                            'habukhan_date' => $this->system_date(),
                            'plan_status' => 1,
                            'transid' => $deposit_trans->reference,
                            'role' => 'credit'
                        ]);
                        // referral
                        if ($this->core()->referral == 1) {
                            if ($user->ref) {
                                if (DB::table('transactions')->where(['user_id' => $user->id, 'status' => 'success', 'category' => 'funding'])->count() == 1) {
                                    if (DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->count() == 1) {
                                        $user_ref = DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->first();
                                        $credit_ref = ($credit / 100) * $this->core()->referral_price;
                                        DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);
                                        DB::table('message')->insert([
                                            'username' => $user_ref->username,
                                            'amount' => $credit_ref,
                                            'message' => 'Referral Earning From ' . ucfirst($user->username),
                                            'oldbal' => $user_ref->referral_balance,
                                            'newbal' => $user_ref->referral_balance + $credit_ref,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $this->purchase_ref('EARNING_'),
                                            'role' => 'credit'
                                        ]);
                                        DB::table('notif')->insert([
                                            'username' => $user_ref->username,
                                            'message' => 'Referral Earning From ' . ucfirst($user->username),
                                            'date' => $this->system_date(),
                                            'habukhan' => 0
                                        ]);
                                    }
                                }
                            }
                        }
                        return redirect(config('app.app_url') . '/dashboard');
                    } else if (strtolower($trans_status) == 'expired') {
                        DB::table('transactions')->where(['id' => $deposit_trans->id])->update(['status' => 'failed']);
                        return redirect(config('app.app_url') . '/dashboard');
                    } else if (strtolower($trans_status) == 'failed') {
                        DB::table('transactions')->where(['id' => $deposit_trans->id])->update(['status' => 'failed']);
                        return redirect(config('app.app_url') . '/dashboard');
                    } else {
                        return redirect(config('app.app_url') . '/dashboard');
                    }
                } else {
                    return redirect(config('app.app_url') . '/dashboard');
                }
            } else {
                return redirect(config('app.error_500'));
            }
        } else {
            return redirect(config('app.error_500'));
        }
    }
    public function MonnifyWebhook(Request $request)
    {
        if ($request->eventData) {
            $amount_paid = $request->eventData['amountPaid'];
            $payment_ref = $request->eventData['transactionReference'];
            $payment_status = $request->eventData['paymentStatus'];
            $paidon = $request->eventData['paidOn'];
            $payment_ref = $request->eventData['paymentReference'];
            $customer_name = $request->eventData['customer'];
            $trans_status = strtolower($payment_status);
            if (DB::table('transactions')->where(['external_reference' => $payment_ref, 'status' => 'success'])->count() == 0) {
                if (strtolower($trans_status) == 'paid') {
                    if (DB::table('transactions')->where(['external_reference' => $payment_ref])->count() == 1) {
                        $deposit_trans = DB::table('transactions')->where(['external_reference' => $payment_ref])->first();
                        $credit = $deposit_trans->amount - $deposit_trans->fee;
                        $user = DB::table('users')->where(['id' => $deposit_trans->user_id, 'status' => 'active'])->first();
                        DB::table('transactions')->where(['id' => $deposit_trans->id, 'status' => 'pending'])->update([
                            'status' => 'success',
                            'balance_before' => $user->balance,
                            'balance_after' => $user->balance + $credit,
                            'processed_at' => now(),
                            'updated_at' => now()
                        ]);
                        DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $user->balance + $credit]);
                        DB::table('notif')->insert([
                            'username' => $user->username,
                            'message' => 'Account Credited By Monnify ATM ₦' . number_format($credit, 2),
                            'date' => $this->system_date(),
                            'habukhan' => 0
                        ]);
                        DB::table('message')->insert([
                            'username' => $user->username,
                            'amount' => $credit,
                            'message' => 'Account Credited By Monnify ATM ₦' . number_format($credit, 2),
                            'oldbal' => $user->balanceance,
                            'newbal' => $user->balanceance + $credit,
                            'habukhan_date' => $this->system_date(),
                            'plan_status' => 1,
                            'transid' => $deposit_trans->transid,
                            'role' => 'credit'
                        ]);
                        // referral
                        if ($this->core()->referral == 1) {
                            if ($user->ref) {
                                if (DB::table('deposit')->where(['username' => $user->username, 'status' => 'active'])->count() == 1) {
                                    if (DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->count() == 1) {
                                        $user_ref = DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->first();
                                        $credit_ref = ($credit / 100) * $this->core()->referral_price;
                                        DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);
                                        DB::table('message')->insert([
                                            'username' => $user_ref->username,
                                            'amount' => $credit_ref,
                                            'message' => 'Referral Earning From ' . ucfirst($user->username),
                                            'oldbal' => $user_ref->referral_balance,
                                            'newbal' => $user_ref->referral_balance + $credit_ref,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $this->purchase_ref('EARNING_'),
                                            'role' => 'credit'
                                        ]);
                                        DB::table('notif')->insert([
                                            'username' => $user_ref->username,
                                            'message' => 'Referral Earning From ' . ucfirst($user->username),
                                            'date' => $this->system_date(),
                                            'habukhan' => 0
                                        ]);
                                    }
                                }
                            }
                        }
                    } else {
                        if (
                            DB::table('users')->where(['status' => 'active'])->where(function ($query) use ($customer_name) {
                                $query->orWhere('username', $customer_name['name'])->orWhere('email', $customer_name['email']);
                            })->count() == 1
                        ) {
                            $user = DB::table('users')->where(['status' => 'active'])->where(function ($query) use ($customer_name) {
                                $query->orWhere('username', $customer_name['name'])->orWhere('email', $customer_name['email']);
                            })->first();
                            // Use dynamic Monnify percentage charge from settings
                            $monnify_percent = $this->core()->monnify_charge ?? 1.5;
                            $charges = ($amount_paid / 100) * $monnify_percent;

                            // Optional: Keep a minimum cap if you prefer, but the user asked for percentage.
                            // To be safe, we'll follow the user's specific "monify should be percentage" request strictly.
                            $transid = $this->purchase_ref('AUTOMATED_');
                            $credit = $amount_paid - $charges;
                            \App\Models\Transaction::create([
                                'user_id' => $user->id,
                                'company_id' => $user->active_company_id,
                                'reference' => $transid,
                                'external_reference' => $payment_ref,
                                'type' => 'credit',
                                'category' => 'funding',
                                'amount' => $amount_paid,
                                'fee' => $charges,
                                'total_amount' => $credit,
                                'currency' => 'NGN',
                                'status' => 'success',
                                'description' => 'Automated Bank Transfer Funding',
                                'balance_before' => $user->balance,
                                'balance_after' => $user->balance + $credit,
                                'metadata' => [
                                    'wallet_type' => 'User Wallet',
                                    'credit_by' => 'Monnify Automated Bank Transfer'
                                ],
                                'processed_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                            DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $user->balance + $credit]);
                            DB::table('notif')->insert([
                                'username' => $user->username,
                                'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                                'date' => $this->system_date(),
                                'habukhan' => 0
                            ]);
                            DB::table('message')->insert([
                                'username' => $user->username,
                                'amount' => $credit,
                                'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                                'oldbal' => $user->balance,
                                'newbal' => $user->balance + $credit,
                                'habukhan_date' => $this->system_date(),
                                'plan_status' => 1,
                                'transid' => $transid,
                                'role' => 'credit'
                            ]);

                            // referral
                            if ($this->core()->referral == 1) {
                                if ($user->ref) {
                                    if (DB::table('deposit')->where(['username' => $user->username, 'status' => 'active'])->count() == 1) {
                                        if (DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->count() == 1) {
                                            $user_ref = DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->first();
                                            $credit_ref = ($credit / 100) * $this->core()->referral_price;
                                            DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);
                                            DB::table('message')->insert([
                                                'username' => $user_ref->username,
                                                'amount' => $credit_ref,
                                                'message' => 'Referral Earning From ' . ucfirst($user->username),
                                                'oldbal' => $user_ref->referral_balance,
                                                'newbal' => $user_ref->referral_balance + $credit_ref,
                                                'habukhan_date' => $this->system_date(),
                                                'plan_status' => 1,
                                                'transid' => $this->purchase_ref('EARNING_'),
                                                'role' => 'credit'
                                            ]);
                                            DB::table('notif')->insert([
                                                'username' => $user_ref->username,
                                                'message' => 'Referral Earning From ' . ucfirst($user->username),
                                                'date' => $this->system_date(),
                                                'habukhan' => 0
                                            ]);
                                        }
                                    }
                                }
                            }

                            // Send Email Receipt
                            $email_data = [
                                'email' => $user->email,
                                'username' => $user->username,
                                'title' => 'Wallet Funding Success',
                                'amount' => $amount_paid,
                                'charges' => $charges,
                                'newbal' => $user->balance + $credit,
                                'transid' => $transid,
                                'date' => $this->system_date(),
                                'mes' => "Your wallet has been credited with ₦" . number_format($credit, 2) . " via Monnify Automated Bank Transfer."
                            ];
                            MailController::send_mail($email_data, 'email.purchase');

                            //referral
                        } else {
                            return view('error.error');
                        }
                    }
                } else {
                    return view('error.error');
                }
            } else {
                return view('error.error');
            }
        } else {
            return view('error.error');
        }
    }
    public function Paystackfunding(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->count() == 1) {
                $user = DB::table('users')->where(['id' => $this->verifytoken($request->id), 'status' => 'active'])->first();
                $habukhan_key = DB::table('habukhan_key')->first();
                $main_validator = validator::make($request->all(), [
                    'amount' => "required|numeric|min:$habukhan_key->min|max:$habukhan_key->max",
                ]);
                $transid = $this->purchase_ref('PAYSTACK_');
                if (DB::table('message')->where('transid', $transid)->count() == 0 and DB::table('deposit')->where('transid', $transid)->count() == 0) {
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        if ($this->core()->paystack == 1) {

                            $postdata = array(
                                "email" => $user->email,
                                "amount" => $request->amount * 100,
                                "currency" => "NGN",
                                "callback_url" => url('') . "/api/callback/paystack",
                                "metadata" => [
                                    "custom_fields" => [
                                        "display_name" => config('app.name') . " Payment Gatway",
                                        "variable_name" => $user->username,
                                        "value" => $user->phone
                                    ]
                                ]
                            );
                            $habukhan_key = DB::table('habukhan_key')->first();
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/initialize");
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));  //send requrest to monnify
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            $headers = [
                                "Authorization: Bearer " . $habukhan_key->psk,
                                'Content-Type: application/json',
                            ];
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            $get_res = curl_exec($ch);
                            curl_close($ch);
                            $response = json_decode($get_res, true);
                            if (isset($response)) {
                                if ($response['status'] == 'true') {
                                    $payment_ref = $response['data']['reference'];

                                    $data = [
                                        'username' => $user->username,
                                        'amount' => $request->amount,
                                        'oldbal' => $user->balanceance,
                                        'newbal' => $user->balanceance,
                                        'wallet_type' => 'User Wallet',
                                        'type' => 'Paystack Funding',
                                        'credit_by' => 'Paystack',
                                        'date' => $this->system_date(),
                                        'status' => 'pending',
                                        'transid' => $transid,
                                        'charges' => $this->core()->paystack_charge ?? 0,
                                        'monify_ref' => $payment_ref
                                    ];
                                    DB::table('deposit')->insert($data);
                                    return response()->json([
                                        'status' => 'success',
                                        'redirect' => $response['data']['authorization_url']
                                    ]);
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Please Try Again Later'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Please Try Again Later'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'paystack Server Down'
                            ])->setStatusCode(403);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Reload the browser and try again'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Reload the browser and try again'
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
    public function PaystackCallBack(Request $request)
    {
        if (isset($request->trxref)) {
            if (DB::table('deposit')->where(['monify_ref' => $request->trxref, 'status' => 'pending'])->count() == 1) {
                $habukhan_key = DB::table('habukhan_key')->first();
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.paystack.co/transaction/verify/' . $request->trxref,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer ' . $habukhan_key->psk
                    ),
                ));
                $resp = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($resp);
                if ($response) {
                    if ('success' == $response->data->status) {
                        $deposit_trans = DB::table('transactions')->where(['external_reference' => $request->trxref])->first();
                        $credit = $deposit_trans->amount - $deposit_trans->fee;
                        $user = DB::table('users')->where(['id' => $deposit_trans->user_id, 'status' => 'active'])->first();
                        DB::table('transactions')->where(['external_reference' => $request->trxref, 'status' => 'pending'])->update([
                            'status' => 'success',
                            'balance_before' => $user->balance,
                            'balance_after' => $user->balance + $credit,
                            'processed_at' => now(),
                            'updated_at' => now()
                        ]);
                        DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $user->balance + $credit]);
                        DB::table('notif')->insert([
                            'username' => $user->username,
                            'message' => 'Account Credited By Paystack ₦' . number_format($credit, 2),
                            'date' => $this->system_date(),
                            'habukhan' => 0
                        ]);
                        DB::table('message')->insert([
                            'username' => $user->username,
                            'amount' => $credit,
                            'message' => 'Account Credited By Paystack ₦' . number_format($credit, 2),
                            'oldbal' => $user->balance,
                            'newbal' => $user->balance + $credit,
                            'habukhan_date' => $this->system_date(),
                            'plan_status' => 1,
                            'transid' => $deposit_trans->reference,
                            'role' => 'credit'
                        ]);
                        // referral
                        if ($this->core()->referral == 1) {
                            if ($user->ref) {
                                if (DB::table('transactions')->where(['user_id' => $user->id, 'status' => 'success', 'category' => 'funding'])->count() == 1) {
                                    if (DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->count() == 1) {
                                        $user_ref = DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->first();
                                        $credit_ref = ($credit / 100) * $this->core()->referral_price;
                                        DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);
                                        DB::table('message')->insert([
                                            'username' => $user_ref->username,
                                            'amount' => $credit_ref,
                                            'message' => 'Referral Earning From ' . ucfirst($user->username),
                                            'oldbal' => $user_ref->referral_balance,
                                            'newbal' => $user_ref->referral_balance + $credit_ref,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $this->purchase_ref('EARNING_'),
                                            'role' => 'credit'
                                        ]);
                                        DB::table('notif')->insert([
                                            'username' => $user_ref->username,
                                            'message' => 'Referral Earning From ' . ucfirst($user->username),
                                            'date' => $this->system_date(),
                                            'habukhan' => 0
                                        ]);
                                    }
                                }
                            }
                        }

                        // Send Email Receipt
                        $email_data = [
                            'email' => $user->email,
                            'username' => $user->username,
                            'title' => 'Wallet Funding Success',
                            'amount' => $deposit_trans->amount,
                            'charges' => $deposit_trans->fee,
                            'newbal' => $user->balance + $credit,
                            'transid' => $deposit_trans->reference,
                            'date' => $this->system_date(),
                            'mes' => "Your wallet has been credited with ₦" . number_format($credit, 2) . " via Paystack."
                        ];
                        MailController::send_mail($email_data, 'email.purchase');

                        return redirect(config('app.app_url') . '/dashboard/app');
                    } else {
                        DB::table('transactions')->where(['external_reference' => $request->trxref, 'status' => 'pending'])->update(['status' => 'failed']);
                        return redirect(config('app.app_url') . '/dashboard/app');
                    }
                } else {
                    return redirect(config('app.app_url') . '/dashboard/app');
                }
            } else {
                return redirect(config('app.error_500'));
            }
        } else {
            return redirect(config('app.error_500'));
        }
    }
    public function VDFWEBHOOK(Request $request)
    {

        if (!empty($request->reference)) {
            if (DB::table('transactions')->where(['external_reference' => $request->reference])->count() == 0) {
                if (DB::table('users')->where(['vdf' => $request->account_number])->count() == 1) {
                    $user = DB::table('users')->where(['vdf' => $request->account_number])->first();
                    $charges = ($request->amount / 100) * 1.3;
                    $transid = $this->purchase_ref('AUTOMATED_VDF_');
                    $credit = $request->amount - $charges;
                    \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'company_id' => $user->active_company_id,
                        'reference' => $transid,
                        'external_reference' => $request->reference,
                        'type' => 'credit',
                        'category' => 'funding',
                        'amount' => $request->amount,
                        'fee' => $charges,
                        'total_amount' => $credit,
                        'currency' => 'NGN',
                        'status' => 'success',
                        'description' => 'Automated Bank Transfer Funding (VDF)',
                        'balance_before' => $user->balance,
                        'balance_after' => $user->balance + $credit,
                        'metadata' => [
                            'wallet_type' => 'User Wallet',
                            'credit_by' => 'VDF'
                        ],
                        'processed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $user->balance + $credit]);
                    DB::table('notif')->insert([
                        'username' => $user->username,
                        'message' => 'Account Credited By VDF Automated Bank Transfer ₦' . number_format($credit, 2),
                        'date' => $this->system_date(),
                        'habukhan' => 0
                    ]);
                    DB::table('message')->insert([
                        'username' => $user->username,
                        'amount' => $credit,
                        'message' => 'Account Credited By VDF Automated Bank Transfer ₦' . number_format($credit, 2),
                        'oldbal' => $user->balance,
                        'newbal' => $user->balance + $credit,
                        'habukhan_date' => $this->system_date(),
                        'plan_status' => 1,
                        'transid' => $transid,
                        'role' => 'credit'
                    ]);



                    // referral
                    if ($this->core()->referral == 1) {
                        if ($user->ref) {

                            if (DB::table('transactions')->where(['user_id' => $user->id, 'status' => 'success', 'category' => 'funding'])->count() == 1) {
                                if (DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->count() == 1) {
                                    $user_ref = DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->first();
                                    $credit_ref = ($credit / 100) * $this->core()->referral_price;
                                    DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);
                                    DB::table('message')->insert([
                                        'username' => $user_ref->username,
                                        'amount' => $credit_ref,
                                        'message' => 'Referral Earning From ' . ucfirst($user->username),
                                        'oldbal' => $user_ref->referral_balance,
                                        'newbal' => $user_ref->referral_balance + $credit_ref,
                                        'habukhan_date' => $this->system_date(),
                                        'plan_status' => 1,
                                        'transid' => $this->purchase_ref('EARNING_'),
                                        'role' => 'credit'
                                    ]);
                                    DB::table('notif')->insert([
                                        'username' => $user_ref->username,
                                        'message' => 'Referral Earning From ' . ucfirst($user->username),
                                        'date' => $this->system_date(),
                                        'habukhan' => 0
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    return view('error.error');
                }
            } else {
                return view('error.error');
            }
        } else {
            return view('error.error');
        }
    }

    public function UpdateKYC(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!in_array($request->headers->get('origin'), $explode_url)) {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
        $validator = Validator::make($request->all(), [
            'data.date_of_birth' => ['required', 'date_format:d-M-Y', 'before:today'],
            'data.bvn_number' => 'required|digits:11',
            'token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 'fail'
            ])->setStatusCode(403);
        }
        $check = DB::table('users')->where(['id' => $this->verifytoken($request->token)])->first();
        if (!$check) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Login Expired'
            ])->setStatusCode(403);
        }
        if ($check->is_bvn_fail == 1) {
            $charges = 10;
            if ($check->balanceance >= $charges) {
                DB::table('users')->where('id', $check->id)->update(['balance' => $check->balanceance - $charges]);
                DB::table('message')->insert([
                    'username' => $check->username,
                    'amount' => $charges,
                    'message' => "BVN Validation Request",
                    'oldbal' => $check->balanceance,
                    'newbal' => $check->balanceance - $charges,
                    'habukhan_date' => $this->system_date(),
                    'plan_status' => 1,
                    'transid' => $this->purchase_ref('validate_bvn_'),
                    'role' => 'debit'
                ]);
            } else {
                return response()->json(['status' => 'fail', 'message' => 'Insufficient Account Balance, Please Kindly Fund Your Wallet And Try Again'], 403);
            }
        }
        $habukhan_key = $this->habukhan_key();
        $base_monnify = base64_encode($habukhan_key->mon_app_key . ':' . $habukhan_key->mon_sk_key);
        $response_acess = Http::withHeaders([
            'Authorization' => 'Basic ' . $base_monnify,
        ])->post('https://api.monnify.com/api/v1/auth/login');
        $response_habukhan_access_json = $response_acess->json();
        if ($response_acess->successful()) {
            if (!empty($response_habukhan_access_json['responseBody']['accessToken'])) {
                $access_token = $response_habukhan_access_json['responseBody']['accessToken'];
                $request_kyc_bvn_match = Http::withHeaders(['Authorization' => "Bearer " . $access_token])->post('https://api.monnify.com/api/v1/vas/bvn-details-match', [
                    "bvn" => $request->data['bvn_number'],
                    "name" => $check->name,
                    "dateOfBirth" => $request->data['date_of_birth'],
                    "mobileNo" => $check->phone
                ]);
                $request_kyc_bvn_match_update = $request_kyc_bvn_match->json();
                file_put_contents('monnify_bvn_u.json', json_encode($request_kyc_bvn_match_update));
                if ($request_kyc_bvn_match->successful()) {
                    if (!empty($request_kyc_bvn_match_update['responseBody']['dateOfBirth'])) {
                        $dateOfBirthStatus = $request_kyc_bvn_match_update['responseBody']['dateOfBirth'];
                        if ($dateOfBirthStatus === 'FULL_MATCH') {
                            DB::table('users')->where('id', $check->id)->update(['bvn' => $request->data['bvn_number']]);
                            // send the the payment gateway the bvn
                            //monify update
                            if ($check->monify_ref != null) {
                                $response = Http::withHeaders(['Authorization' => "Bearer " . $access_token])->put('https://api.monnify.com//pi/v1/bank-transfer/reserved-accounts/' . $check->monify_ref . '/kyc-info', [
                                    "bvn" => $request->data['bvn_number'],
                                ]);
                                file_put_contents('monnify_bvn_check.txt', json_encode($response->json()));
                            }
                            if ($check->monify_ref == null & $check->kolomoni_mfb != null) {
                                DB::table('users')->where('id', $check->id)->update(['kolomoni_mfb' => null, 'paystack_account' => null, 'autofund' => null]);
                                // user_bank table is obsolete. Skipping deletion.
                                // DB::table('user_bank')->where('username', $check->username)->whereIn('bank', ['MONIEPOINT'])->delete();
                            }

                            return response()->json(['status' => 'success', 'message' => 'BVN matches with date of birth. KYC Updated'], 200);
                        } else {
                            DB::table('users')->where('id', $check->id)->update(['is_bvn_fail' => 1]);
                            return response()->json(['status' => 'fail', 'message' => 'BVN does not match with date of birth.'], 403);
                        }
                    }
                }
            }
        }
        return response()->json(['status' => 'fail', 'message' => 'Please Kindly Try Again Later'], 403);
    }
    /**
     * Create a Dedicated Virtual Account (Palmpay)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createVirtualAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!in_array($request->headers->get('origin'), $explode_url)) {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
        $validator = Validator::make($request->all(), [
            'data.amount' => 'nullable|numeric', // Amount is less critical for persistent accounts, but kept for compatibility
            'token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 'fail'
            ])->setStatusCode(403);
        }

        $userId = $this->verifytoken($request->token);
        $check = DB::table('users')->where(['id' => $userId])->first();

        if (!$check) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Login Expired'
            ])->setStatusCode(403);
        }

        try {
            // Initialize VirtualAccountService
            $virtualAccountService = new \App\Services\PalmPay\VirtualAccountService();

            // Use a default company ID (e.g., 0) as this is a platform-level user request
            $companyId = 0;

            $defaultBankCode = config('services.palmpay.bank_code', '100033');
            $bankCodes = [$defaultBankCode];
            $createdAccounts = [];
            $errors = [];

            foreach ($bankCodes as $bankCode) {
                try {
                    // Check if account already exists using the service
                    $existingAccount = DB::table('virtual_accounts')
                        ->where('user_id', $check->id)
                        ->where('bank_code', $bankCode)
                        ->where('status', 'active')
                        ->first();

                    if ($existingAccount) {
                        $createdAccounts[] = [
                            'account_number' => $existingAccount->palmpay_account_number,
                            'account_name' => $existingAccount->palmpay_account_name,
                            'bank_name' => $existingAccount->palmpay_bank_name,
                            'expire_at' => null,
                            'charges' => '50 Naira',
                            'amount' => $request->data['amount'] ?? 0
                        ];
                        continue;
                    }

                    // Create new Virtual Account
                    $customerData = [
                        'name' => $check->name,
                        'email' => $check->email,
                        'phone' => $check->phone,
                        'bvn' => $check->bvn,
                        'identity_type' => 'personal',
                        'license_number' => $check->bvn
                    ];

                    $newAccount = $virtualAccountService->createVirtualAccount($companyId, $check->id, $customerData, $bankCode);

                    $createdAccounts[] = [
                        'account_number' => $newAccount->palmpay_account_number,
                        'account_name' => $newAccount->palmpay_account_name,
                        'bank_name' => $newAccount->palmpay_bank_name,
                        'expire_at' => null,
                        'charges' => '50 Naira',
                        'amount' => $request->data['amount'] ?? 0
                    ];

                } catch (\Exception $e) {
                    \Log::error("Bank $bankCode Generation Error: " . $e->getMessage());
                    $errors[] = "$bankCode: " . $e->getMessage();
                }
            }

            if (empty($createdAccounts)) {
                throw new \Exception("Unable to generate accounts: " . implode(', ', $errors));
            }

            // Sync primary (PalmPay) details with user table for backward compatibility
            $primary = collect($createdAccounts)->firstWhere('bank_name', 'PalmPay') ?? $createdAccounts[0];
            DB::table('users')->where('id', $check->id)->update([
                'palmpay_account_number' => $primary['account_number'],
                'palmpay_bank_name' => $primary['bank_name'],
                'palmpay_account_name' => $primary['account_name']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => count($createdAccounts) > 1 ? 'Virtual accounts created' : 'Account created',
                'data' => $primary // Return primary for frontend legacy support, dashboard will fetch all via GetBanksArray
            ], 200);

        } catch (\Exception $e) {
            \Log::error("DynamicAccount Generation Error: " . $e->getMessage());
            return response()->json(['status' => 'fail', 'message' => 'Unable to generate dedicated account. ' . $e->getMessage()], 403);
        }
    }
    public function importExcel()
    {
        try {
            // Specify the path to your Excel file
            $filePath = 'habukhan.xlsx';

            // Import Excel data
            Excel::import(new MonnifyImport, $filePath);

            return response()->json(['message' => 'Import successful']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function KudaByHabukhan(Request $request)
    {
        if ($request->transactionReference) {
            $amount_paid = $request->amount / 100;
            $payment_ref = $request->transactionReference;
            $account_number_here = $request->accountNumber;
            if (DB::table('deposit')->where(['monify_ref' => $payment_ref, 'status' => 'active'])->count() == 0) {
                if (DB::table('users')->where(['kuda' => $account_number_here, 'status' => 'active'])->count() == 1) {
                    $user = DB::table('users')->where(['kuda' => $account_number_here, 'status' => 'active'])->first();
                    if ($amount_paid < 10000) {
                        $charges = 30;
                    } else {
                        $charges = 50;
                    }
                    $transid = $this->purchase_ref('Kuda_AUTOMATED_');
                    $credit = $amount_paid - $charges;
                    DB::table('deposit')->insert([
                        'username' => $user->username,
                        'amount' => $amount_paid,
                        'oldbal' => $user->balanceance,
                        'newbal' => $user->balanceance + $credit,
                        'wallet_type' => 'User Wallet',
                        'type' => 'AutoMated Bank Transfer',
                        'credit_by' => 'Kuda Automated Bank Transfer',
                        'date' => $this->system_date(),
                        'status' => 'active',
                        'transid' => $transid,
                        'charges' => $charges,
                        'monify_ref' => $payment_ref,
                        'payment_type' => 'kuda',
                        'withdraw' => 0
                    ]);
                    DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $user->balanceance + $credit]);
                    DB::table('notif')->insert([
                        'username' => $user->username,
                        'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                        'date' => $this->system_date(),
                        'habukhan' => 0
                    ]);
                    DB::table('message')->insert([
                        'username' => $user->username,
                        'amount' => $credit,
                        'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                        'oldbal' => $user->balanceance,
                        'newbal' => $user->balanceance + $credit,
                        'habukhan_date' => $this->system_date(),
                        'plan_status' => 1,
                        'transid' => $transid,
                        'role' => 'credit'
                    ]);

                    if ($user->app_token != null) {
                        $firebase = new FirebaseService();
                        $firebase->sendNotification(
                            $user->app_token,
                            config('app.name'),
                            "You have received a payment of ₦" . number_format($credit, 2),
                            [
                                'type' => 'transaction',
                                'action' => 'deposit',
                                'channel_id' => 'high_importance_channel'
                            ]
                        );
                    }
                    // referral
                    if ($this->core()->referral == 1) {
                        if ($user->ref) {
                            if (DB::table('deposit')->where(['username' => $user->username, 'status' => 'active'])->count() == 1) {
                                if (DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->count() == 1) {
                                    $user_ref = DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->first();
                                    $credit_ref = ($credit / 100) * $this->core()->referral_price;
                                    DB::table('users')->where(['username' => $user->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);
                                    DB::table('message')->insert([
                                        'username' => $user_ref->username,
                                        'amount' => $credit_ref,
                                        'message' => 'Referral Earning From ' . ucfirst($user->username),
                                        'oldbal' => $user_ref->referral_balance,
                                        'newbal' => $user_ref->referral_balance + $credit_ref,
                                        'habukhan_date' => $this->system_date(),
                                        'plan_status' => 1,
                                        'transid' => $this->purchase_ref('EARNING_'),
                                        'role' => 'credit'
                                    ]);
                                    DB::table('notif')->insert([
                                        'username' => $user_ref->username,
                                        'message' => 'Referral Earning From ' . ucfirst($user->username),
                                        'date' => $this->system_date(),
                                        'habukhan' => 0
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    // public function WithdrawKuda()
    // {
    //     $records = DB::table('deposit')
    //         ->where('payment_type', 'kuda')
    //         ->where('withdraw', 0)
    //         ->orderBy('id', 'desc')
    //         ->take(100)
    //         ->get();

    //     foreach ($records as $habukhan_record) {
    //         dispatch(new ProcessKudaWithdrawal($habukhan_record));
    //     }
    // }

    public function SafeWebhook(Request $request)
    {
        $jsonData = json_decode($request->getContent());
        //  file_put_contents('safe_ddd.txt', $request->getContent());
        if (!empty($jsonData->transaction_reference)) {
            $deposit_ref = $jsonData->transaction_reference;
            $payment_status = $jsonData->event_data->status;
            $amount_paid = $jsonData->event_data->data->paid;
            $payment_type = $jsonData->package_id;
            $account_ref = $jsonData->destination->account_reference;
            $account_email = $jsonData->destination->account_email;
            $account_number = $jsonData->destination->account_number;
            $check_deposit = DB::table('deposit')->where(['monify_ref' => $deposit_ref])->first();
            if (!$check_deposit) {
                $user = DB::table('users')->where(['email' => $account_email, 'status' => 'active'])->first();
                if ($user) {
                    $user = DB::table('users')->where('id', $user->id)->first();
                    if ($payment_type == 7) {
                        $charges = 40;
                        $type = "Safehaven";
                    } else if ($payment_type == 101) {
                        $charges = 50;
                        $type = "SafeHaven (Dynamic Funding)";
                    } else {
                        $charges = ($amount_paid / 100) * 1.1;
                        $type = "Access";
                    }
                    $credit = $amount_paid - $charges;

                    DB::table('deposit')->insert([
                        'username' => $user->username,
                        'amount' => $credit,
                        'oldbal' => $user->balanceance,
                        'newbal' => $user->balanceance + $credit,
                        'wallet_type' => 'User Wallet',
                        'type' => $type,
                        'credit_by' => $type,
                        'date' => $this->system_date(),
                        'status' => 'active',
                        'transid' => 'collected_' . $deposit_ref,
                        'charges' => $charges,
                        'monify_ref' => $deposit_ref
                    ]);

                    DB::table('users')->where(['username' => $user->username, 'id' => $user->id])->update(['balance' => $user->balanceance + $credit]);

                    DB::table('notif')->insert([
                        'username' => $user->username,
                        'message' => "A deposit of ₦" . number_format($credit, 2) . " has been added to your account.",
                        'date' => $this->system_date(),
                        'habukhan' => 0
                    ]);

                    DB::table('message')->insert([
                        'username' => $user->username,
                        'amount' => $credit,
                        'message' => "A deposit of ₦" . number_format($credit, 2) . " has been added to your account.",
                        'oldbal' => $user->balanceance,
                        'newbal' => $user->balanceance + $credit,
                        'habukhan_date' => $this->system_date(),
                        'plan_status' => 1,
                        'transid' => 'collected_' . $deposit_ref,
                        'role' => 'credit'
                    ]);
                    return response()->json(['message' => 'funded'], 200);
                }
                return response()->json(['message' => 'User Not Found / Not An Active User'], 405);
            }
            return response()->json(['message' => 'Deposit reference already exists'], 405);
        }
        return response()->json(['message' => 'Unable to get transaction reference'], 405);
    }
}