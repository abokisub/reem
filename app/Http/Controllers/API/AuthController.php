<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        set_time_limit(300); // Increased time limit
        ignore_user_abort(true); // Continue processing even if user disconnects
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $validator = validator::make($request->all(), [
                'first_name' => 'required|max:199|min:2',
                'last_name' => 'required|max:199|min:2',
                'business_name' => 'required|max:199|min:2',
                'email' => 'required|unique:users,email|max:255|email',
                'phone' => 'required|numeric|unique:users,phone|digits:11',
                'password' => 'required|min:8',
            ], [
                'first_name.required' => 'First Name is Required',
                'last_name.required' => 'Last Name is Required',
                'business_name.required' => 'Business Name is Required',
                'email.required' => 'E-mail is Required',
                'phone.required' => 'Phone Number Required',
                'password.required' => 'Password Required',
                'email.unique' => 'Email Already Taken',
                'phone.unique' => 'Phone Number already Taken',
                'password.min' => 'Password must be at least 8 characters',
                'phone.numeric' => 'Phone Number Must be Numeric',
            ]);

            // checking referal user details
            if ($request->ref != null) {
                $check_ref = DB::table('users')
                    ->where('username', '=', $request->ref)
                    ->count();
            }

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 403
                ])->setStatusCode(403);
            } else if (substr($request->phone, 0, 1) != '0') {
                return response()->json([
                    'message' => 'Invalid Phone Number',
                    'status' => 403
                ])->setStatusCode(403);
            } else if ($request->ref != null && $check_ref == 0) {
                return response()->json([
                    'message' => 'Invalid Referral Username You can Leave the Referral Username Box Empty',
                    'status' => '403'
                ])->setStatusCode(403);
            } else {
                // Generate a unique username from email
                $username = strstr($request->email, '@', true);
                if (DB::table('users')->where('username', $username)->exists()) {
                    $username = $username . random_int(100, 999);
                }

                $user = new User();
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->business_name = $request->business_name;
                $user->name = $request->first_name . ' ' . $request->last_name;
                $user->username = $username;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $user->password = password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16));
                $user->api_key = bin2hex(openssl_random_pseudo_bytes(30));
                $user->app_key = $user->api_key;
                $user->balance = '0.00';
                $user->referral_balance = '0.00';
                $user->ref = $request->ref;
                $user->type = 'user';
                $user->date = Carbon::now("Africa/Lagos");
                $user->kyc = '0';
                $user->status = 'pending';
                $user->user_limit = $this->habukhan_key()->default_limit;
                $user->pin = null; // Removed from registration
                $user->save();
                if ($user != null) {
                    $user = DB::table('users')->where(['id' => $user->id])->first();

                    // Auto-create company for new user
                    try {
                        $keys = \App\Models\Company::generateApiKeys();
                        $testKeys = \App\Models\Company::generateApiKeys('test_');
                        
                        $companyData = [
                            'user_id' => $user->id,
                            'name' => $request->business_name,
                            'email' => $request->email,
                            'phone' => $request->phone,
                            'business_type' => 'individual', // Default
                            'business_category' => 'general', // Default
                            'status' => 'pending',
                            'kyc_status' => 'unverified',
                            'api_public_key' => $keys['api_public_key'],
                            'api_secret_key' => $keys['api_secret_key'],
                            'test_public_key' => $testKeys['api_public_key'],
                            'test_secret_key' => $testKeys['api_secret_key'],
                            'public_key' => $keys['api_public_key'],
                            'secret_key' => $keys['api_secret_key'],
                            'api_key' => $keys['api_public_key'],
                            'test_api_key' => $testKeys['api_public_key'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $companyId = DB::table('companies')->insertGetId($companyData);
                        
                        // Create company wallet
                        DB::table('company_wallets')->insert([
                            'company_id' => $companyId,
                            'currency' => 'NGN',
                            'balance' => 0,
                            'ledger_balance' => 0,
                            'pending_balance' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Set as active company
                        DB::table('users')->where('id', $user->id)->update(['active_company_id' => $companyId]);
                        
                        \Log::info("Auto-created company for user {$user->id}: Company ID {$companyId}");
                    } catch (\Exception $e) {
                        \Log::error("Failed to auto-create company during registration: " . $e->getMessage());
                        // Continue registration even if company creation fails
                    }

                    // Fetch settings to check enabled providers
                    try {
                        $settings = DB::table('settings')->select(
                            'palmpay_enabled',
                            'monnify_enabled',
                            'wema_enabled',
                            'xixapay_enabled',
                            'default_virtual_account'
                        )->first();

                        // Determine which accounts to show based on settings
                        $monnify_enabled = $settings->monnify_enabled ?? true;
                        $wema_enabled = $settings->wema_enabled ?? true;
                        $xixapay_enabled = $settings->xixapay_enabled ?? true;
                        $palmpay_enabled = $settings->palmpay_enabled ?? true;
                        $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                        $default_virtual_account = ($default_virtual_account == 'palmpay') ? 'xixapay' : $default_virtual_account; // Migration for name change if needed
                    } catch (\Exception $e) {
                        $monnify_enabled = false;
                        $wema_enabled = false;
                        $xixapay_enabled = false;
                        $palmpay_enabled = true;
                        $default_virtual_account = 'palmpay';
                    }

                    // Smart Fallback
                    $active_default = $default_virtual_account;
                    if ($active_default == 'wema' && !$wema_enabled)
                        $active_default = null;
                    if ($active_default == 'monnify' && !$monnify_enabled)
                        $active_default = null;
                    if ($active_default == 'xixapay' && !$xixapay_enabled)
                        $active_default = null;
                    if ($active_default == 'palmpay' && !$palmpay_enabled)
                        $active_default = null;



                    /*
                    try {
                        if ($xixapay_enabled)
                            $this->xixapay_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Register Xixapay: " . $e->getMessage());
                    }

                    try {
                        if ($monnify_enabled || $wema_enabled)
                            $this->monnify_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Register Monnify: " . $e->getMessage());
                    }
                    */

                    // if ($palmpay_enabled || $monnify_enabled)
                    //    $this->paymentpoint_account($user->username);
                    /*
                    try {
                        $this->paystack_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Register Paystack: " . $e->getMessage());
                    }
                    */
                    // Always try paystack or link to setting
                    $this->insert_stock($user->username);

                    $user = DB::table('users')->where(['id' => $user->id])->first();
                    $company = \App\Models\Company::where('user_id', $user->id)->first();

                    $user_details = $this->getUserDetails($user);

                    $token = $this->generatetoken($user->id);
                    $use = $this->core();
                    $general = $this->general();


                    // DISABLED: Auto-verify for web - now enforcing OTP for all users
                    // if ($origin) {
                    //     DB::table('users')->where(['id' => $user->id])->update(['status' => 'active']);
                    //     return response()->json([
                    //         'status' => 'success',
                    //         'message' => 'Registration Successful (Web)',
                    //         'token' => $token,
                    //         'user' => $user_details
                    //     ]);
                    // }

                    // All users - follow OTP flow
                    if (true) { // Force OTP as requested by user
                        if ($use->is_verify_email || true) { // redundant true but clear intent
                            $otp = random_int(100000, 999999);
                            $data = [
                                'otp' => $otp,
                                'otp_expiry' => Carbon::now()->addMinute()
                            ];
                            $tableid = [
                                'username' => $user->username
                            ];
                            $this->updateData($data, 'users', $tableid);
                            $email_data = [
                                'name' => $user->name,
                                'email' => $user->email,
                                'username' => $user->username,
                                'title' => 'Account Verification',
                                'pin' => $user->pin,
                                'app_name' => config('app.name'),
                                'otp' => $otp
                            ];
                            try {
                                MailController::send_mail($email_data, 'email.verify');
                            } catch (\Throwable $e) {
                                // Continue even if email fails
                            }
                            return response()->json([
                                'status' => 'verify',
                                'requires_otp' => true,
                                'username' => $user->username,
                                'token' => $token,
                                'user' => $user_details
                            ]);
                        } else {
                            // Default fallback (Mobile)
                            DB::table('users')->where(['id' => $user->id])->update(['status' => 'active']);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Registration Successful',
                                'token' => $token,
                                'user' => $user_details
                            ]);
                        }
                    } else {
                        $data = [
                            'status' => 'active',
                        ];
                        $tableid = [
                            'username' => $user->username
                        ];
                        $this->updateData($data, 'users', $tableid);
                        $email_data = [
                            'name' => $user->name,
                            'email' => $user->email,
                            'username' => $user->username,
                            'title' => 'WELCOME EMAIL',
                            'sender_mail' => $general->app_email,
                            'system_email' => $general->app_email,
                            'app_name' => $general->app_name,
                            'pin' => $user->pin,
                        ];
                        try {
                            MailController::send_mail($email_data, 'email.welcome');
                        } catch (\Throwable $e) {
                            // Continue
                        }
                        return response()->json([
                            'status' => 'success',
                            'username' => $user->username,
                            'token' => $token,
                            'user' => $user_details
                        ]);
                    }

                } else {
                    return response()->json(
                        [
                            'status' => 403,
                            'message' => 'Unable to Register User Please Try Again Later',
                        ]
                    )->setStatusCode(403);
                }
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function account(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $user_token = $request->id;
            $real_token = $this->verifytoken($user_token);
            if (!is_null($real_token)) {
                $habukhan_check = DB::table('users')->where('id', $real_token);
                if ($habukhan_check->count() == 1) {
                    $user = $habukhan_check->get()[0];
                    // Fetch settings to check enabled providers
                    try {
                        $settings = DB::table('settings')->select(
                            'palmpay_enabled',
                            'monnify_enabled',
                            'wema_enabled',
                            'xixapay_enabled',
                            'default_virtual_account'
                        )->first();

                        // Determine which accounts to show based on settings
                        // Monnify provides Sterling/Wema
                        $monnify_enabled = $settings->monnify_enabled ?? true;
                        // Wema is separate direct wema
                        $wema_enabled = $settings->wema_enabled ?? true;
                        // Xixapay provides OPay (kolomoni_mfb/opay columns)
                        $xixapay_enabled = $settings->xixapay_enabled ?? true;
                        // Palmpay is separate
                        $palmpay_enabled = $settings->palmpay_enabled ?? true;
                        $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                        $default_virtual_account = ($default_virtual_account == 'palmpay') ? 'xixapay' : $default_virtual_account; // Migration for name change if needed
                    } catch (\Exception $e) {
                        $monnify_enabled = true;
                        $wema_enabled = true;
                        $xixapay_enabled = true;
                        $palmpay_enabled = true;
                        $default_virtual_account = 'palmpay';
                    }

                    // Fetch Virtual Account if available
                    $virtualAccount = DB::table('virtual_accounts')->where('user_id', $user->id)->where('status', 'active')->first();

                    // Priority: Virtual Account -> User Table -> Company Table
                    // We prioritize Virtual Account because it's the most up-to-date source for dedicated accounts
                    $palmpay_acc = $user->palmpay_account_number;
                    $palmpay_bank = $user->palmpay_bank_name;
                    $palmpay_name = $user->palmpay_account_name;

                    if ($virtualAccount) {
                        $palmpay_acc = $virtualAccount->palmpay_account_number;
                        $palmpay_bank = $virtualAccount->palmpay_bank_name;
                        $palmpay_name = $virtualAccount->palmpay_account_name;
                    }

                    if (empty($palmpay_acc)) {
                        $company = DB::table('companies')->where('user_id', $user->id)->first();
                        if ($company && !empty($company->account_number)) {
                            $palmpay_acc = $company->account_number;
                            $palmpay_bank = $company->bank_name;
                            $palmpay_name = $company->account_name;
                        }
                    }

                    $user_details = $this->getUserDetails($user);

                    // Smart Fallback
                    $active_default = $default_virtual_account;
                    if ($active_default == 'wema' && !$wema_enabled)
                        $active_default = null;
                    if ($active_default == 'monnify' && !$monnify_enabled)
                        $active_default = null;
                    if ($active_default == 'xixapay' && !$xixapay_enabled)
                        $active_default = null;
                    if ($active_default == 'palmpay' && !$palmpay_enabled)
                        $active_default = null;

                    if ($active_default == null) {
                        if ($palmpay_enabled)
                            $active_default = 'palmpay';
                        elseif ($wema_enabled)
                            $active_default = 'wema';
                        elseif ($monnify_enabled)
                            $active_default = 'monnify';
                        elseif ($xixapay_enabled)
                            $active_default = 'xixapay';
                    }

                    /*
                    try {
                        if ($xixapay_enabled && $user->palmpay == null)
                            $this->xixapay_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account Xixapay: " . $e->getMessage());
                    }

                    try {
                        if (($monnify_enabled || $wema_enabled) && ($user->palmpay_account_number == null))
                            $this->monnify_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account Monnify: " . $e->getMessage());
                    }

                    try {
                        if ($palmpay_enabled && ($user->palmpay == null || $user->opay == null))
                            $this->paymentpoint_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account PaymentPoint: " . $e->getMessage());
                    }

                    try {
                        if ($user->paystack_account == null)
                            $this->paystack_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account Paystack: " . $e->getMessage());
                    }
                    */
                    // $this->insert_stock($user->username); // Optimize stock check if needed
                    $moniepoint_acc = $user->palmpay_account_number;

                    $user_details = $this->getUserDetails($user);

                    if ($user->status == 'pending') {
                        // FIX: Allow access if business activation is submitted (under_review)
                        // This prevents the loop where they activate business but get sent back to OTP
                        $company = DB::table('companies')->where('user_id', $user->id)->first();
                        $kyc_status = $company ? $company->kyc_status : ($user->kyc_status ?? null);

                        if ($kyc_status === 'under_review' || $kyc_status === 'submitted') {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'account verified',
                                'user' => $user_details
                            ]);
                        }

                        return response()->json([
                            'status' => 'verify',
                            'requires_otp' => true,
                            'message' => 'Account Not Yet Verified',
                            'user' => $user_details
                        ]);
                    } else if ($user->status == 'active') {
                        //set up the user over here


                        return response()->json([
                            'status' => 'success',
                            'message' => 'account verified',
                            'user' => $user_details
                        ]);
                    } else if ($user->status == '2') {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Account Banned'
                        ])->setStatusCode(403);
                    } elseif ($user->status == '3') {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Account Deactivated'
                        ])->setStatusCode(403);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Unable to Get User'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Allowed',
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'AccessToken Expired'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System',
            ])->setStatusCode(403);
        }
    }
    public function verify(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $habukhan_check = DB::table('users')->where('email', $request->email);
            if ($habukhan_check->count() == 1) {
                $user = $habukhan_check->get()[0];
                /*
                // FIX: Commenting out heavy external API calls
                // $this->xixapay_account($user->username);
                // $this->monnify_account($user->username);
                // $this->paymentpoint_account($user->username);
                // $this->paystack_account($user->username);
                // $this->insert_stock($user->username);
                */
                $user = DB::table('users')->where(['id' => $user->id])->first();

                // Fetch settings to check enabled providers
                try {
                    $settings = DB::table('settings')->select(
                        'palmpay_enabled',
                        'monnify_enabled',
                        'wema_enabled',
                        'xixapay_enabled',
                        'default_virtual_account'
                    )->first();

                    // Determine which accounts to show based on settings
                    $monnify_enabled = $settings->monnify_enabled ?? true;
                    $wema_enabled = $settings->wema_enabled ?? true;
                    $xixapay_enabled = $settings->xixapay_enabled ?? true;
                    $palmpay_enabled = $settings->palmpay_enabled ?? true;
                    $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                } catch (\Exception $e) {
                    $monnify_enabled = true;
                    $wema_enabled = true;
                    $xixapay_enabled = true;
                    $palmpay_enabled = true;
                    $default_virtual_account = 'palmpay';
                }

                // Smart Fallback
                $active_default = $default_virtual_account;
                if ($active_default == 'wema' && !$wema_enabled)
                    $active_default = null;
                if ($active_default == 'monnify' && !$monnify_enabled)
                    $active_default = null;
                if ($active_default == 'xixapay' && !$xixapay_enabled)
                    $active_default = null;
                if ($active_default == 'palmpay' && !$palmpay_enabled)
                    $active_default = null;

                if ($active_default == null) {
                    if ($palmpay_enabled)
                        $active_default = 'palmpay';
                    elseif ($wema_enabled)
                        $active_default = 'wema';
                    elseif ($monnify_enabled)
                        $active_default = 'monnify';
                    elseif ($xixapay_enabled)
                        $active_default = 'xixapay';
                }



                // Fetch Virtual Account if available
                $virtualAccount = DB::table('virtual_accounts')->where('user_id', $user->id)->where('status', 'active')->first();

                // Priority: User Table -> Virtual Account -> Company Table
                $palmpay_acc = $user->palmpay_account_number;
                $palmpay_bank = $user->palmpay_bank_name;
                $palmpay_name = $user->palmpay_account_name;

                if (empty($palmpay_acc) && $virtualAccount) {
                    $palmpay_acc = $virtualAccount->palmpay_account_number;
                    $palmpay_bank = $virtualAccount->palmpay_bank_name;
                    $palmpay_name = $virtualAccount->palmpay_account_name;
                }

                if (empty($palmpay_acc)) {
                    $company = DB::table('companies')->where('user_id', $user->id)->first();
                    if ($company && !empty($company->account_number)) {
                        $palmpay_acc = $company->account_number;
                        $palmpay_bank = $company->bank_name;
                        $palmpay_name = $company->account_name;
                    }
                }

                $user_details = $this->getUserDetails($user);
                if ($user->otp == $request->code) {
                    if (isset($user->otp_expiry) && Carbon::now()->greaterThan(Carbon::parse($user->otp_expiry))) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'OTP has expired. Please request a new one.'
                        ], 403);
                    }
                    //if success
                    $data = [
                        'status' => 'active',
                        'otp' => null
                    ];
                    $tableid = [
                        'id' => $user->id
                    ];
                    $general = $this->general();
                    $this->updateData($data, 'users', $tableid);
                    $email_data = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'title' => 'WELCOME EMAIL',
                        'sender_mail' => $general->app_email,
                        'system_email' => $general->app_email,
                        'app_name' => $general->app_name,
                        'pin' => $user->pin,
                    ];
                    // FIX: Re-enabled Mail
                    try {
                        MailController::send_mail($email_data, 'email.welcome');
                    } catch (\Throwable $e) {
                        \Log::error("Welcome Mail Error: " . $e->getMessage());
                    }
                    return response()->json([
                        'status' => 'success',
                        'message' => 'account verified',
                        'user' => $user_details,
                        'token' => $this->generatetoken($user->id)
                    ]);
                } else {
                    // Fix for Connection Error/Timeout Retry Issue
                    // If the previous request succeeded in DB but failed to return response (timeout),
                    // the user is already verified (status == 1) but OTP is null.
                    // We should allow them to proceed.
                    if ($user->status == 'active') {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'account verified',
                            'user' => $user_details,
                            'token' => $this->generatetoken($user->id)
                        ]);
                    }

                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid OTP'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to verify user'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System',

            ])->setStatusCode(403);
        }
    }
    public function login(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            //our login function over here
            \Log::info('API Login Hit: ' . json_encode($request->except('password')));
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ], [
                'email.required' => 'Your Email Address is Required',
                'email.email' => 'Please provide a valid email address',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'message' => $validator->errors()->first()
                ])->setStatusCode(403);
            } else {
                $check_system = User::where('email', $request->email);
                if ($check_system->count() == 1) {
                    $user = $check_system->first();
                    $t0 = microtime(true);
                    // Fetch settings to check enabled providers
                    try {
                        $settings = DB::table('settings')->select(
                            'palmpay_enabled',
                            'monnify_enabled',
                            'wema_enabled',
                            'xixapay_enabled',
                            'default_virtual_account'
                        )->first();
                        \Log::info('Login Step 1 (Settings): ' . (microtime(true) - $t0) . 's');

                        // Determine which accounts to show based on settings
                        $monnify_enabled = $settings->monnify_enabled ?? true;
                        $wema_enabled = $settings->wema_enabled ?? true;
                        $xixapay_enabled = $settings->xixapay_enabled ?? true;
                        $palmpay_enabled = $settings->palmpay_enabled ?? true;
                        $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                        $default_virtual_account = ($default_virtual_account == 'palmpay') ? 'xixapay' : $default_virtual_account; // Migration for name change if needed
                    } catch (\Exception $e) {
                        $monnify_enabled = true;
                        $wema_enabled = true;
                        $xixapay_enabled = true;
                        $palmpay_enabled = true;
                        $default_virtual_account = 'palmpay';
                    }

                    // Smart Fallback
                    $active_default = $default_virtual_account;
                    if ($active_default == 'wema' && !$wema_enabled)
                        $active_default = null;
                    if ($active_default == 'monnify' && !$monnify_enabled)
                        $active_default = null;
                    if ($active_default == 'xixapay' && !$xixapay_enabled)
                        $active_default = null;
                    if ($active_default == 'palmpay' && !$palmpay_enabled)
                        $active_default = null;

                    if ($active_default == null) {
                        if ($palmpay_enabled)
                            $active_default = 'palmpay';
                        elseif ($wema_enabled)
                            $active_default = 'wema';
                        elseif ($monnify_enabled)
                            $active_default = 'monnify';
                        elseif ($xixapay_enabled)
                            $active_default = 'xixapay';
                    }

                    \Log::info('Login Step 2 (Skipping legacy syncs)');
                    /*
                    // FIX: Smart Account Generation
                    $t1 = microtime(true);
                    try {
                        if ($wema_enabled && empty($user->paystack_account)) {
                            \Log::info('Login Step 2a (Monnify/Wema Start)');
                            $this->monnify_account($user->username);
                            \Log::info('Login Step 2a Finished: ' . (microtime(true) - $t1) . 's');
                        }
                    } catch (\Exception $e) {
                        \Log::error("Login Wema: " . $e->getMessage());
                    }

                    $t2 = microtime(true);
                    try {
                        if ($monnify_enabled && empty($user->palmpay_account_number)) {
                            \Log::info('Login Step 2b (Monnify Start)');
                            $this->monnify_account($user->username);
                            \Log::info('Login Step 2b Finished: ' . (microtime(true) - $t2) . 's');
                        }
                    } catch (\Exception $e) {
                        \Log::error("Login Sterlen: " . $e->getMessage());
                    }

                    $t3 = microtime(true);
                    try {
                        if (empty($user->paystack_account)) {
                            \Log::info('Login Step 2c (Paystack Start)');
                            $this->paystack_account($user->username);
                            \Log::info('Login Step 2c Finished: ' . (microtime(true) - $t3) . 's');
                        }
                    } catch (\Exception $e) {
                        \Log::error("Login Paystack: " . $e->getMessage());
                    }
                    */

                    $t4 = microtime(true);
                    $user = DB::table('users')->where(['id' => $user->id])->first();
                    \Log::info('Login Step 3 (User Refresh): ' . (microtime(true) - $t4) . 's');

                    // Fetch Virtual Account if available
                    $virtualAccount = DB::table('virtual_accounts')->where('user_id', $user->id)->where('status', 'active')->first();

                    // Priority: Virtual Account -> User Table -> Company Table
                    $palmpay_acc = $user->palmpay_account_number;
                    $palmpay_bank = $user->palmpay_bank_name;
                    $palmpay_name = $user->palmpay_account_name;

                    if ($virtualAccount) {
                        $palmpay_acc = $virtualAccount->palmpay_account_number;
                        $palmpay_bank = $virtualAccount->palmpay_bank_name;
                        $palmpay_name = $virtualAccount->palmpay_account_name;
                    }

                    if (empty($palmpay_acc)) {
                        $company = DB::table('companies')->where('user_id', $user->id)->first();
                        if ($company && !empty($company->account_number)) {
                            $palmpay_acc = $company->account_number;
                            $palmpay_bank = $company->bank_name;
                            $palmpay_name = $company->account_name;
                        }
                    }

                    $user_details = $this->getUserDetails($user);

                    $t5 = microtime(true);
                    $hash = substr(sha1(md5($request->password)), 3, 10);
                    $mdpass = md5($request->password);
                    $pass_match = (password_verify($request->password, $user->password)) xor ($request->password == $user->password) xor ($hash == $user->password) xor ($mdpass == $user->password);
                    \Log::info('Login Step 4 (Password Verify): ' . (microtime(true) - $t5) . 's');

                    if ($pass_match) {
                        // Debug Log
                        \Log::info('Login Debug: User=' . $user->username . ', Type="' . $user->type . '", Status=' . $user->status);

                        if ($user->status == 'active' || trim(strtoupper($user->type)) == 'ADMIN' || strcasecmp($user->username, 'Habukhan') == 0) {
                            $t6 = microtime(true);
                            $token = $this->generatetoken($user->id);
                            \Log::info('Login Step 5 (Token Gen): ' . (microtime(true) - $t6) . 's');

                            return response()->json([
                                'status' => 'success',
                                'message' => 'Login successfully',
                                'user' => $user_details,
                                'token' => $token
                            ]);
                        } else if ($user->status == 'suspended') {
                            return response()->json([
                                'status' => 403,
                                'message' => $user->username . ' Your Account Has Been Banned'
                            ])->setStatusCode(403);
                        } else if ($user->status == 'deactivated') {
                            return response()->json([
                                'status' => 403,
                                'message' => $user->username . ' Your Account Has Been Deactivated'
                            ])->setStatusCode(403);
                        } else if ($user->status == 'pending') {
                            if ($origin) {
                                // Web login - auto-verify and login
                                DB::table('users')->where(['id' => $user->id])->update(['status' => 'active']);
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Login successfully (Web Auto-Verify)',
                                    'user' => $user_details,
                                    'token' => $this->generatetoken($user->id)
                                ]);
                            }

                            // Mobile login - send OTP
                            $otp = random_int(100000, 999999);
                            DB::table('users')->where(['id' => $user->id])->update(['otp' => $otp, 'otp_expiry' => Carbon::now()->addMinutes(10)]);

                            $general = $this->general();
                            $email_data = [
                                'name' => $user->name,
                                'email' => $user->email,
                                'username' => $user->username,
                                'title' => 'Account Verification',
                                'sender_mail' => $general->app_email,
                                'app_name' => config('app.name'),
                                'otp' => $otp
                            ];
                            try {
                                MailController::send_mail($email_data, 'email.verify');
                            } catch (\Throwable $e) {
                                \Log::error('OTP Mail Error (AuthController): ' . $e->getMessage());
                            }

                            return response()->json([
                                'status' => 'verify',
                                'message' => $user->username . ' (' . $user->type . ') Your Account Not Yet verified. An OTP has been sent to your email.',
                                'user' => $user_details,
                                'token' => $this->generatetoken($user->id),
                            ]);
                        } else {
                            \Log::warning('Login Failed: Status logic mismatch for User=' . $user->username . ', Status=' . $user->status);
                            return response()->json([
                                'status' => 403,
                                'message' => 'System is unable to verify user. Please contact support.'

                            ])->setStatusCode(403);
                        }
                    } else {
                        \Log::warning('Login Failed: Password mismatch for User=' . $user->username);
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Password Note Password is Case Sensitive'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid Email or Password'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'unauntorized'
            ])->setStatusCode(403);
        }
    }

    public function resendOtp(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (isset($request->id)) {
                $sel_user = DB::table('users')->where('email', $request->id);
                if ($sel_user->count() == 1) {
                    $user = $sel_user->get()[0];
                    $general = $this->general();
                    $otp = random_int(100000, 999999);
                    $data = [
                        'otp' => $otp,
                        'otp_expiry' => Carbon::now()->addMinutes(10)
                    ];
                    $tableid = [
                        'username' => $user->username
                    ];
                    $this->updateData($data, 'users', $tableid);
                    $email_data = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'title' => 'Account Verification',
                        'sender_mail' => $general->app_email,
                        'app_name' => config('app.name'),
                        'otp' => $otp
                    ];
                    MailController::send_mail($email_data, 'email.verify');
                    return response()->json([
                        'status' => 'status',
                        'message' => 'New OTP Resent to Your Email'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to Detect User'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'An Error Occurred'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
        }
    }

    /**
     * Set/Update Transaction PIN
     */
    public function createPin(Request $request)
    {
        $authHeader = $request->header('Authorization');
        if (strpos($authHeader, 'Bearer ') === 0) {
            $authHeader = substr($authHeader, 7);
        }

        // Identify user by token (habukhan_key)
        $user = DB::table('users')->where('habukhan_key', $authHeader)->first();

        if ($user) {
            $validator = Validator::make($request->all(), [
                'pin' => 'required|numeric|digits:4',
                'confirm_pin' => 'required|same:pin',
            ], [
                'pin.required' => 'Transaction PIN is required',
                'pin.digits' => 'PIN must be 4 digits',
                'confirm_pin.same' => 'PINs do not match',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'message' => $validator->errors()->first()
                ])->setStatusCode(403);
            }

            DB::table('users')->where('id', $user->id)->update([
                'pin' => $request->pin,
                'status' => 'active' // Ensure user is active if they reached this step
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction PIN created successfully'
            ]);
        }

        return response()->json([
            'status' => 403,
            'message' => 'Unauthorized or Session Expired'
        ])->setStatusCode(403);
    }
    /**
     * Check if a system feature is locked (Admin/Internal use)
     */
    public function CheckSystemLock($feature)
    {
        // Special Handling for Airtime to Cash (Check network availability)
        if ($feature === 'airtime_to_cash') {
            $availableNetworks = DB::table('network')->where('cash', 1)->count();
            return response()->json([
                'status' => 'success',
                'message' => 'A2C Lock status retrieved from network table',
                'data' => ['is_locked' => ($availableNetworks === 0)]
            ]);
        }

        $lock = DB::table('system_locks')->where('feature_key', $feature)->first();

        if (!$lock) {
            return response()->json([
                'status' => 'success',
                'message' => 'Feature not found, assuming unlocked',
                'data' => ['is_locked' => false]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lock status retrieved',
            'data' => ['is_locked' => (bool) $lock->is_locked]
        ]);
    }

    /**
     * Phase 2: KYC Verification
     */
    public function verifyKyc(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'id_type' => 'required|in:bvn,nin',
            'id_number' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = DB::table('users')->where('id', $request->user()->id ?? 0)->first();
        if (!$user) {
            // Fallback for tokenauth if $request->user() is null (depends on middleware)
            // But auth middleware should handle it.
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        // 2. Check for Duplicates in user_kyc
        // We assume id_number is stored plain or consistent hash. 
        // Logic: if someone else verified this ID, block.
        $existing = DB::table('user_kyc')
            ->where('id_type', $request->id_type)
            ->where('id_number', $request->id_number) // In PROD, might want to hash this for privacy, but for now matching requirements
            ->where('status', 'verified')
            ->first();

        if ($existing) {
            // Allow re-verification if it's the SAME user (e.g. retry or lost status)
            if ($existing->user_id == $user->id) {
                // Already verified, just return success
                return response()->json([
                    'status' => 'success',
                    'message' => 'Identity already verified',
                    'data' => json_decode($existing->full_response_json, true)
                ]);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'This ID is already linked to another account.'
            ], 409);
        }

        // 3. Call Provider
        try {
            // $provider = new \App\Services\Banking\Providers\XixapayProvider();
            // $result = $provider->verifyIdentity($request->id_type, $request->id_number);

            return response()->json([
                'status' => 'error',
                'message' => 'Identity Verification Service is temporarily unavailable.'
            ], 503);

            /*
             if ($result['status'] === 'success') {
             // ... success logic ...
             }
             return response()->json([
             'status' => 'error',
             'message' => $result['message']
             ], 400);
             */

        } catch (\Exception $e) {
            \Log::error("KYC Verification Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Verification Service Unavailable'
            ], 500);
        }
    }

    /**
     * Real-time BVN Verification
     * Matches BVN against phone number and auto-verifies
     */
    public function verifyBvn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bvn' => 'required|string|size:11',
            'phone' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        // 0. Check if already verified to save cost
        $existingKyc = DB::table('user_kyc')
            ->where('user_id', $user->id)
            ->where('id_type', 'bvn')
            ->where('status', 'verified')
            ->first();

        if ($existingKyc || (!empty($user->bvn) && $user->kyc_tier !== 'tier1')) {
            return response()->json([
                'status' => 'success',
                'message' => 'BVN already verified!',
                'data' => $existingKyc ? json_decode($existingKyc->full_response_json, true) : null
            ]);
        }

        try {
            $easeIdService = new \App\Services\EaseID\EaseIDKycService();

            // 1. Verify BVN via EaseID
            $result = $easeIdService->verifyBvn($request->bvn);

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'BVN Verification Failed: ' . ($result['message'] ?? 'Invalid BVN')
                ], 400);
            }

            $bvnData = $result['data'];
            $bvnPhone = $bvnData['phoneNumber'] ?? $bvnData['telephoneNo'] ?? null;

            if (!$bvnPhone) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Could not retrieve phone number associated with this BVN.'
                ], 400);
            }

            // 2. Match Phone Number
            // Normalize phone numbers for comparison (take last 10 digits)
            $inputPhone = substr(preg_replace('/[^0-9]/', '', $request->phone), -10);
            $apiPhone = substr(preg_replace('/[^0-9]/', '', $bvnPhone), -10);

            \Log::info("BVN Verification Debug: Input Phone: $inputPhone, API Phone: $apiPhone");

            if ($inputPhone !== $apiPhone) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Phone number mismatch. The provided phone does not match the one linked to this BVN.'
                ], 400);
            }

            // 3. Auto-Verify and Save
            DB::table('users')->where('id', $user->id)->update([
                'bvn' => $request->bvn,
                'bvn_phone' => $request->phone,
                'kyc_tier' => 'tier2', // Assuming BVN upgrade to tier 2
                'status' => 'active'
            ]);

            // Also update user_kyc table if it exists and is used
            DB::table('user_kyc')->updateOrInsert(
                ['user_id' => $user->id, 'id_type' => 'bvn'],
                [
                    'id_number' => $request->bvn,
                    'status' => 'verified',
                    'verified_at' => now(),
                    'full_response_json' => json_encode($bvnData)
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'BVN verified successfully!',
                'data' => [
                    'first_name' => $bvnData['firstName'] ?? '',
                    'last_name' => $bvnData['lastName'] ?? '',
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("BVN Verification Endpoint Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'System error during BVN verification. Please try again.'
            ], 500);
        }
    }

    /**
     * Real-time NIN Verification
     */
    public function verifyNin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nin' => 'required|string|size:11',
            'phone' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        // 0. Check if already verified to save cost
        $existingKyc = DB::table('user_kyc')
            ->where('user_id', $user->id)
            ->where('id_type', 'nin')
            ->where('status', 'verified')
            ->first();

        if ($existingKyc) {
            return response()->json([
                'status' => 'success',
                'message' => 'NIN already verified!',
                'data' => json_decode($existingKyc->full_response_json, true)
            ]);
        }

        try {
            $easeIdService = new \App\Services\EaseID\EaseIDKycService();

            // 1. Verify NIN via EaseID
            $result = $easeIdService->verifyNin($request->nin);

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'NIN Verification Failed: ' . ($result['message'] ?? 'Invalid NIN')
                ], 400);
            }

            $ninData = $result['data'];
            $ninPhone = $ninData['phoneNumber'] ?? $ninData['telephoneNo'] ?? null;

            if (!$ninPhone) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Could not retrieve phone number associated with this NIN.'
                ], 400);
            }

            // 2. Match Phone Number
            $inputPhone = substr(preg_replace('/[^0-9]/', '', $request->phone), -10);
            $apiPhone = substr(preg_replace('/[^0-9]/', '', $ninPhone), -10);

            if ($inputPhone !== $apiPhone) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Phone number mismatch. The provided phone does not match the one linked to this NIN.'
                ], 400);
            }

            // 3. Auto-Verify and Save
            DB::table('users')->where('id', $user->id)->update([
                'nin' => $request->nin,
                'nin_phone' => $request->phone,
                'status' => 'active'
            ]);

            DB::table('user_kyc')->updateOrInsert(
                ['user_id' => $user->id, 'id_type' => 'nin'],
                [
                    'id_number' => $request->nin,
                    'status' => 'verified',
                    'verified_at' => now(),
                    'full_response_json' => json_encode($ninData)
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'NIN verified successfully!',
                'data' => [
                    'first_name' => $ninData['firstName'] ?? '',
                    'last_name' => $ninData['lastName'] ?? '',
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("NIN Verification Endpoint Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'System error during NIN verification. Please try again.'
            ], 500);
        }
    }
    public function getKycDetails(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $kycs = DB::table('user_kyc')->where('user_id', $user->id)->get();
        $company = DB::table('companies')->where('user_id', $user->id)->first();

        // Granular Feedback and Status
        $feedback = [];
        $sectionStatuses = [];
        if ($company) {
            $approvals = DB::table('company_kyc_approvals')
                ->where('company_id', $company->id)
                ->get();

            foreach ($approvals as $approval) {
                $sectionStatuses[$approval->section] = $approval->status;
                if ($approval->status === 'rejected') {
                    $feedback[$approval->section] = $approval->rejection_reason;
                }
            }
        }

        $data = [
            'bvn' => null,
            'nin' => null,
            'kyc_status' => $user->kyc_status, // Default user status
            'company_kyc_status' => $company ? $company->kyc_status : null,
            'business_name' => $company ? $company->name : ($user->business_name ?? null),
            'feedback' => $feedback, // Sections needing attention
            'section_statuses' => $sectionStatuses, // Status for each section
            'company' => $company, // Full company details for pre-filling
            'user_details' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'bvn' => $user->bvn,
                'nin' => $user->nin,
            ]
        ];

        foreach ($kycs as $kyc) {
            $data[$kyc->id_type] = [
                'status' => $kyc->status,
                'verified_at' => $kyc->verified_at,
                'id_number' => $kyc->id_number,
                'id_card_path' => $kyc->id_card_path ?? null,
                'utility_bill_path' => $kyc->utility_bill_path ?? null,
                'details' => json_decode($kyc->full_response_json, true)
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
    /**
     * Phase 3: Create Customer
     */
    public function createCustomer(Request $request)
    {
        $user = DB::table('users')->where('id', $request->user()->id ?? 0)->first();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);

        // 1. Check if already exists
        if (!empty($user->customer_id)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Customer already created',
                'customer_id' => $user->customer_id
            ]);
        }

        // 2. Check KYC
        $kyc = DB::table('user_kyc')->where('user_id', $user->id)->where('status', 'verified')->first();
        if (!$kyc) {
            return response()->json(['status' => 'error', 'message' => 'Account Verification (KYC) Required'], 403);
        }

        // 3. Validation
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'postal_code' => 'required|string',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'id_card' => 'required|file|mimes:jpeg,png,pdf|max:5120', // Max 5MB
            'utility_bill' => 'required|file|mimes:jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // 4. Prepare Payload
        $nameParts = explode(' ', $user->name, 2);
        $first_name = $nameParts[0];
        $last_name = $nameParts[1] ?? $first_name;

        $payload = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $user->email,
            'phone_number' => $user->phone,
            'id_type' => $kyc->id_type,
            'id_number' => $kyc->id_number,
            // New Fields
            'address' => $request->address,
            'state' => $request->state,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'date_of_birth' => $request->date_of_birth,
            // Files
            'id_card' => $request->file('id_card'),
            'utility_bill' => $request->file('utility_bill')
        ];

        // 5. API Call
        try {
            // $provider = new \App\Services\Banking\Providers\XixapayProvider();
            // $result = $provider->createCustomer($payload);

            return response()->json([
                'status' => 'error',
                'message' => 'Customer creation service is temporarily unavailable.'
            ], 503);

            /*
             if ($result['status'] === 'success') {
             // ... success logic ...
             }
             return response()->json(['status' => 'error', 'message' => $result['message']], 400);
             */

        } catch (\Exception $e) {
            \Log::error("Customer Creation Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }

    /**
     * Update Customer Details (Phase 3 Extra)
     */
    public function updateCustomer(Request $request)
    {
        $user = DB::table('users')->where('id', $request->user()->id ?? 0)->first();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);

        if (empty($user->customer_id)) {
            return response()->json(['status' => 'error', 'message' => 'Customer not found. Create one first.'], 404);
        }

        // Validation - same as create
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'postal_code' => 'required|string',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'id_card' => 'nullable|file|mimes:jpeg,png,pdf|max:5120', // Optional on update
            'utility_bill' => 'nullable|file|mimes:jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // Get KYC Data for ID info
        $kyc = DB::table('user_kyc')->where('user_id', $user->id)->where('status', 'verified')->first();
        if (!$kyc) {
            return response()->json(['status' => 'error', 'message' => 'Valid KYC needed'], 403);
        }

        $nameParts = explode(' ', $user->name, 2);
        $first_name = $nameParts[0];
        $last_name = $nameParts[1] ?? $first_name;

        $payload = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $user->email,
            'phone_number' => $user->phone,
            'id_type' => $kyc->id_type,
            'id_number' => $kyc->id_number,
            'address' => $request->address,
            'state' => $request->state,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'date_of_birth' => $request->date_of_birth,
        ];

        if ($request->hasFile('id_card')) {
            $payload['id_card'] = $request->file('id_card');
        }
        if ($request->hasFile('utility_bill')) {
            $payload['utility_bill'] = $request->file('utility_bill');
        }

        try {
            // $provider = new \App\Services\Banking\Providers\XixapayProvider();
            // $result = $provider->updateCustomer($payload);

            return response()->json([
                'status' => 'error',
                'message' => 'Customer update service is temporarily unavailable.'
            ], 503);

            /*
             if ($result['status'] === 'success') {
             // ... success logic ...
             }
             return response()->json(['status' => 'error', 'message' => $result['message']], 400);
             */

        } catch (\Exception $e) {
            \Log::error("Customer Update Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }

    /**
     * Delete Customer (Phase 3 Extra)
     */
    public function deleteCustomer(Request $request)
    {
        $user = DB::table('users')->where('id', $request->user()->id ?? 0)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        if (empty($user->customer_id) && empty($user->palmpay_account_number)) {
            return response()->json(['status' => 'error', 'message' => 'Customer/Virtual Account not found'], 404);
        }

        try {
            // 1. Delete Virtual Account on PalmPay if one exists
            if (!empty($user->palmpay_account_number)) {
                $vaService = new \App\Services\PalmPay\VirtualAccountService();
                $result = $vaService->deleteVirtualAccount($user->palmpay_account_number);

                if (!$result['success']) {
                    \Log::warning("PalmPay VA Deletion Failed for user {$user->id}: " . $result['message']);
                }
            }

            // 2. Clear customer details locally
            DB::table('users')->where('id', $user->id)->update([
                'customer_id' => null,
                'palmpay_account_number' => null,
                'palmpay_account_name' => null,
                'palmpay_bank_name' => null,
                'palmpay_status' => 'deleted',
                'kyc' => '0'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error("Customer Deletion Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }



    /**
     * Update Virtual Account Status
     * PATCH /api/user/virtual-account/status
     */
    public function updateVirtualAccountStatus(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'accountNumber' => 'required|string',
            'status' => 'required|in:active,deactivated',
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // 2. Call Provider
        try {
            // $provider = new \App\Services\Banking\Providers\XixapayProvider();
            /*
             $result = $provider->updateVirtualAccountStatus(
             $request->accountNumber,
             $request->status,
             $request->reason
             );
             if ($result['status'] === 'success') {
             return response()->json([
             'status' => 'success',
             'message' => $result['message']
             ]);
             }
             return response()->json(['status' => 'error', 'message' => $result['message']], 400);
             */
            return response()->json(['status' => 'error', 'message' => 'Service not available'], 503);

        } catch (\Exception $e) {
            \Log::error("Update VA Status Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }

    public function updateOnboarding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_type' => 'required|in:registered,starter',
            'business_category' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validator->errors()->first()
            ])->setStatusCode(403);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $user->business_type = $request->business_type;
        $user->business_category = $request->business_category;
        $user->onboarding_completed = true;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Business setup completed successfully',
            'user' => $user
        ]);
    }

    public function activateBusiness(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        // Basic Info - Only update if provided
        if ($request->has('business_name'))
            $user->business_name = $request->business_name;
        if ($request->has('rc_number'))
            $user->rc_number = $request->rc_number;
        if ($request->has('description'))
            $user->description = $request->description;
        if ($request->has('country'))
            $user->country = $request->country;
        if ($request->has('state'))
            $user->state = $request->state;
        if ($request->has('lga'))
            $user->lga = $request->lga;
        if ($request->has('address'))
            $user->address = $request->address;
        if ($request->has('website'))
            $user->website = $request->website;
        if ($request->has('facebook'))
            $user->facebook = $request->facebook;
        if ($request->has('x'))
            $user->x = $request->x;
        if ($request->has('instagram'))
            $user->instagram = $request->instagram;
        if ($request->has('linkedin'))
            $user->linkedin = $request->linkedin;

        // Account Info - Only update if provided
        // FIX: Repurposing Paystack fields for SETTLEMENT (User's Bank)
        if ($request->has('bankName'))
            $user->paystack_bank = $request->bankName; // Saving to settlement bank
        if ($request->has('accountNumber'))
            $user->paystack_account = $request->accountNumber; // Saving to settlement number
        if ($request->has('accountName'))
            $user->palmpay_account_name = $request->accountName; // Keeping account name generic (or could move to paystack_account_name if exists)

        // BVN
        if ($request->has('bvn'))
            $user->bvn = $request->bvn;
        if ($request->has('phone'))
            $user->phone = $request->phone;

        // NIN
        if ($request->has('nin'))
            $user->nin = $request->nin;

        // Handle File Uploads - MERGE with existing records
        $kycDocs = (array) $user->kyc_documents; // Cast to array if it was missing or null
        $fileFields = [
            'utility_bill',
            'board_member_utility_bill',
            'id_card',
            'cac_certificate',
            'board_resolution',
            'company_profile',
            'status_report',
            'memart',
            'logo'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('kyc_documents', 'public');
                $kycDocs[$field] = $path;
            }
        }

        // Create/Update Company Record - FETCH EXISTING FIRST TO PRESERVE DATA
        $company = DB::table('companies')->where('user_id', $user->id)->first();

        $companyData = [
            'user_id' => $user->id,
            'name' => $user->business_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'business_registration_number' => $user->rc_number,
            'rc_number' => $user->rc_number, // Also save to rc_number for frontend compatibility
            'bvn' => $user->bvn,
            'nin' => $user->nin,
            'bank_name' => $user->palmpay_bank_name,
            'account_number' => $user->paystack_account, // Use settlement account as account_number
            'account_name' => $user->palmpay_account_name,

            // FIX: Save Settlement Details (Withdrawal Bank)
            'settlement_bank_name' => $user->paystack_bank,
            'settlement_account_number' => $user->paystack_account,
            'settlement_account_name' => $user->palmpay_account_name, // Default or generic

            'kyc_documents' => json_encode($kycDocs),
            'updated_at' => now()
        ];

        // Transition status: If they were partial or pending, move to under_review
        if (!$company || in_array($company->kyc_status, ['unverified', 'rejected', 'partial', 'pending'])) {
            $companyData['kyc_status'] = 'under_review';
        }

        if ($company) {
            DB::table('companies')->where('user_id', $user->id)->update($companyData);
        } else {
            $companyData['created_at'] = now();
            $companyData['status'] = 'pending';
            // Generate API Keys and IDs for new company
            $keys = \App\Models\Company::generateApiKeys();
            $testKeys = \App\Models\Company::generateApiKeys('test_');
            
            $companyData['uuid'] = bin2hex(random_bytes(20)); // 40 chars hex
            $companyData['business_id'] = bin2hex(random_bytes(20)); // 40 chars hex
            $companyData['api_public_key'] = $keys['api_public_key'];
            $companyData['api_secret_key'] = $keys['api_secret_key'];
            $companyData['test_public_key'] = $testKeys['api_public_key'];
            $companyData['test_secret_key'] = $testKeys['api_secret_key'];
            $companyData['public_key'] = $keys['api_public_key'];
            $companyData['secret_key'] = $keys['api_secret_key'];
            $companyData['api_key'] = $keys['api_public_key'];
            $companyData['test_api_key'] = $testKeys['api_public_key'];
            
            $companyId = DB::table('companies')->insertGetId($companyData);
            
            // Create company wallet
            DB::table('company_wallets')->insert([
                'company_id' => $companyId,
                'currency' => 'NGN',
                'balance' => 0,
                'ledger_balance' => 0,
                'pending_balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Set as active company
            $user->active_company_id = $companyId;
        }

        $user->kyc_documents = $kycDocs;
        $user->kyc_status = $companyData['kyc_status'] ?? $user->kyc_status;
        $user->onboarding_completed = true;

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Business activation request submitted successfully. Our team will review it.',
            'user' => $user
        ]);
    }

    public function createNewBusiness(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'business_type' => 'required|string',
            'business_category' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 403);
        }

        // Generate API Keys for new company
        $keys = \App\Models\Company::generateApiKeys();

        $companyData = [
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'business_type' => $request->business_type,
            'business_category' => $request->business_category,
            'status' => 'pending',
            'kyc_status' => 'unverified',
            'uuid' => (string) \Illuminate\Support\Str::uuid() . bin2hex(random_bytes(10)),
            'business_id' => 'BIZ-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $companyData = array_merge($companyData, $keys);

        // Also populate legacy key fields to avoid SQL errors
        $companyData['public_key'] = $keys['api_public_key'];
        $companyData['secret_key'] = $keys['api_secret_key'];
        $companyData['api_key'] = $keys['api_public_key'];

        $companyId = DB::table('companies')->insertGetId($companyData);

        // Auto-switch to the new business
        DB::table('users')->where('id', $user->id)->update(['active_company_id' => $companyId]);

        return response()->json([
            'status' => 'success',
            'message' => 'New business profile created successfully.',
            'company_id' => $companyId,
            'user' => $this->getUserDetails($user->id)
        ]);
    }

    public function switchActiveBusiness(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 403);
        }

        $companyId = $request->company_id;

        // Verify company belongs to user
        $company = DB::table('companies')->where('id', $companyId)->where('user_id', $user->id)->first();
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Business profile not found or access denied.'
            ], 403);
        }

        DB::table('users')->where('id', $user->id)->update(['active_company_id' => $companyId]);

        return response()->json([
            'status' => 'success',
            'message' => 'Business switched successfully to ' . $company->name,
            'user' => $this->getUserDetails($user->id)
        ]);
    }

    /**
     * Helper to consistently format user details for frontend consumption.
     */
    public function getUserDetails($user)
    {
        if (!$user)
            return null;

        // Ensure we have a fresh user object if it's from stdClass or incomplete
        if (is_numeric($user)) {
            $user = User::query()->find($user);
        } else if (isset($user->id)) {
            $user = User::query()->find($user->id);
        }

        $companies = \App\Models\Company::where('user_id', $user->id)->get();

        // Determine active company
        $activeCompany = null;
        if ($user->active_company_id) {
            $activeCompany = $companies->where('id', $user->active_company_id)->first();
        }

        // Fallback to first company if no active one is set
        if (!$activeCompany && $companies->count() > 0) {
            $activeCompany = $companies->first();
            // Update the user record with this default
            DB::table('users')->where('id', $user->id)->update(['active_company_id' => $activeCompany->id]);
        }

        $settings = DB::table('settings')->first();

        $palmpay_enabled = $settings->palmpay_enabled ?? false;
        $monnify_enabled = $settings->monnify_enabled ?? false;
        $xixapay_enabled = $settings->xixapay_enabled ?? false;
        $wema_enabled = $settings->wema_enabled ?? false;

        $active_default = $settings->default_virtual_account ?? 'palmpay';
        if ($active_default == 'wema' && !$wema_enabled)
            $active_default = null;
        if ($active_default == 'monnify' && !$monnify_enabled)
            $active_default = null;
        if ($active_default == 'xixapay' && !$xixapay_enabled)
            $active_default = null;

        $palmpay_acc = $user->palmpay_account_number ?? null;
        $palmpay_bank = $user->palmpay_bank_name ?? 'PalmPay';
        $palmpay_name = $user->palmpay_account_name ?? $user->name;

        // For companies, use company's PalmPay master wallet (for funding)
        if ($activeCompany) {
            $palmpay_acc = $activeCompany->palmpay_account_number ?? $palmpay_acc;
            $palmpay_bank = $activeCompany->palmpay_bank_name ?? $palmpay_bank;
            $palmpay_name = $activeCompany->palmpay_account_name ?? $palmpay_name;
        }

        return [
            'username' => $user->username,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'balance' => number_format($user->balance, 2),
            'referral_balance' => number_format($user->referral_balance, 2),
            'kyc' => $user->kyc,
            'type' => $user->type,
            'pin' => $user->pin,
            'profile_image' => $user->profile_image ?? null,

            'palmpay' => $palmpay_enabled ? $palmpay_acc : null,
            'wema' => null,
            'opay' => $xixapay_enabled ? $palmpay_acc : null,

            'account_number' => $palmpay_acc,
            'bank_name' => $palmpay_bank,
            'account_name' => $palmpay_name,

            // PalmPay Master Wallet (for funding/deposits)
            'palmpay_account_number' => $palmpay_acc,
            'palmpay_bank_name' => $palmpay_bank,
            'palmpay_account_name' => $palmpay_name,

            // Settlement Account (for withdrawals) - OPay or other external bank
            'settlement_number' => ($activeCompany && !empty($activeCompany->settlement_account_number)) ? $activeCompany->settlement_account_number : (($activeCompany && !empty($activeCompany->account_number)) ? $activeCompany->account_number : ($user->paystack_account ?? null)),
            'settlement_bank' => ($activeCompany && !empty($activeCompany->settlement_bank_name)) ? $activeCompany->settlement_bank_name : (($activeCompany && !empty($activeCompany->bank_name)) ? $activeCompany->bank_name : ($user->paystack_bank ?? null)),
            'settlement_bank_code' => ($activeCompany && !empty($activeCompany->bank_code)) ? $activeCompany->bank_code : ($user->paystack_bank_code ?? null),
            'settlement_account_name' => ($activeCompany && !empty($activeCompany->settlement_account_name)) ? $activeCompany->settlement_account_name : (($activeCompany && !empty($activeCompany->account_name)) ? $activeCompany->account_name : ($user->paystack_account_name ?? null)),

            'paystack_account' => $user->paystack_account ?? null,
            'paystack_bank' => $user->paystack_bank ?? null,
            'address' => $user->address ?? null,
            'webhook' => $user->webhook ?? null,
            'about' => $user->about ?? null,
            'api_key' => $user->api_key,
            'default_account' => $active_default,
            'is_bvn' => ($user->bvn ?? null) == null ? false : true,
            'onboarding_completed' => (bool) ($user->onboarding_completed ?? false),

            'business_name' => $activeCompany->name ?? $user->business_name ?? null,
            'business_phone' => $activeCompany->phone ?? $user->phone ?? null,
            'business_type' => $activeCompany->business_type ?? $user->business_type ?? null,
            'business_category' => $activeCompany->business_category ?? $user->business_category ?? null,
            'business_address' => $activeCompany->address ?? $user->address ?? null,
            'business_website' => $activeCompany->website ?? $user->website ?? null,
            'business_description' => $activeCompany->description ?? $user->description ?? null,

            'email_on_payment' => (bool) ($user->email_on_payment ?? false),
            'email_customer_on_success' => (bool) ($user->email_customer_on_success ?? false),
            'resend_failed_webhook' => (bool) ($user->resend_failed_webhook ?? true),
            'resend_failed_webhook_count' => (int) ($user->resend_failed_webhook_count ?? 3),

            'theme' => $user->theme ?? 'light',
            'kyc_status' => $activeCompany->kyc_status ?? $user->kyc_status ?? 'unverified',

            // List of all business profiles
            'companies' => $companies->map(function ($c) {
                return [
                    'id' => $c->id,
                    'business_id' => $c->business_id,
                    'name' => $c->name,
                    'logo' => $c->logo ?? null,
                    'business_type' => $c->business_type,
                    'business_category' => $c->business_category,
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'address' => $c->address,
                    'website' => $c->website ?? null,
                    'status' => $c->status,
                    'is_active' => $c->is_active,
                    'kyc_status' => $c->kyc_status,
                ];
            }),
            'active_company_id' => $activeCompany->id ?? null,
        ];
    }
}
