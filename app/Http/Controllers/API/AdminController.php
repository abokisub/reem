<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{

    public function userRequest(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {

                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    // user request
                    $user_request = DB::table('request')->select('username', 'message', 'date', 'transid', 'status', 'title', 'transid', 'id');
                    if ($user_request->count() > 0) {
                        foreach ($user_request->orderBy('id', 'desc')->get() as $habukhan) {
                            $select_user = DB::table('users')->where('username', $habukhan->username);
                            if ($select_user->count() > 0) {
                                $users = $select_user->first();
                                if ($users->profile_image !== null) {
                                    $profile_image[] = ['username' => $habukhan->username, 'transid' => $habukhan->transid, 'title' => $habukhan->title, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->profile_image, 'status' => $habukhan->status];
                                } else {
                                    $profile_image[] = ['username' => $habukhan->username, 'transid' => $habukhan->transid, 'title' => $habukhan->title, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->username, 'status' => $habukhan->status];
                                }
                            } else {
                                $profile_image[] = ['username' => $habukhan->username, 'transid' => $habukhan->transid, 'title' => $habukhan->title, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $habukhan->username, 'status' => $habukhan->status];
                            }
                        }
                        return response()->json([
                            'status' => 'success',
                            'notif' => $profile_image
                        ]);
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
    public function ClearRequest(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    DB::table('request')->delete();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Done'
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
    public function UserSystem(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {

                    // Calculate total revenue from successful customer deposits (virtual account credits)
                    $total_revenue = DB::table('transactions')
                        ->where('category', 'virtual_account_credit')
                        ->where('status', 'success')
                        ->sum('amount');

                    // Get transaction statistics (filtered to customer deposits)
                    $total_transactions = DB::table('transactions')
                        ->where('category', 'virtual_account_credit')
                        ->count();
                    $successful_transactions = DB::table('transactions')
                        ->where('category', 'virtual_account_credit')
                        ->where('status', 'success')
                        ->count();
                    $failed_transactions = DB::table('transactions')
                        ->where('category', 'virtual_account_credit')
                        ->where('status', 'failed')
                        ->count();
                    $pending_settlement = 0;
                    if (Schema::hasTable('settlement_queue')) {
                        $pending_settlement = (float) DB::table('settlement_queue')->where('status', 'pending')->sum('amount');
                    }

                    // Get business statistics
                    $active_businesses = DB::table('companies')->where('status', 'active')->count();
                    $registered_businesses = DB::table('companies')->count();
                    $pending_activations = DB::table('companies')->where('status', 'pending')->count();
                    $total_virtual_accounts = DB::table('virtual_accounts')->count();

                    try {
                        $users_info = [
                            // New payment gateway metrics
                            'total_revenue' => $total_revenue,
                            'total_transactions' => $total_transactions,
                            'successful_transactions' => $successful_transactions,
                            'failed_transactions' => $failed_transactions,
                            'pending_settlement' => $pending_settlement,
                            'active_businesses' => $active_businesses,
                            'registered_businesses' => $registered_businesses,
                            'pending_activations' => $pending_activations,
                            'total_virtual_accounts' => $total_virtual_accounts,

                            // Existing user wallet metrics (for backward compatibility)
                            'wallet_balance' => DB::table('users')->sum('balance'),
                            'ref_balance' => DB::table('users')->sum('referral_balance'),
                            'all_user' => DB::table('users')->count(),
                            'smart_total' => DB::table('users')->where('type', 'SMART')->count(),
                            'awuf_total' => DB::table('users')->where('type', 'AWUF')->count(),
                            'special_total' => DB::table('users')->where('type', 'SPECIAL')->count(),
                            'api_total' => DB::table('users')->where('type', 'API')->count(),
                            'agent_total' => DB::table('users')->where('type', 'AGENT')->count(),
                            'customer_total' => DB::table('users')->where('type', 'CUSTOMER')->count(),
                            'admin_total' => DB::table('users')->whereIn('type', ['admin', 'ADMIN'])->count(),
                            'active_user' => DB::table('users')->where('status', 'active')->count(),
                            'deactivate_user' => DB::table('users')->where('status', 'suspended')->count(),
                            'banned_user' => DB::table('users')->where('status', 'suspended')->count(),
                            'unverified_user' => DB::table('users')->where('status', 'pending')->count(),
                            'mtn_cg_bal' => DB::table('wallet_funding')->sum('mtn_cg_bal'),
                            'mtn_g_bal' => DB::table('wallet_funding')->sum('mtn_g_bal'),
                            'mtn_sme_bal' => DB::table('wallet_funding')->sum('mtn_sme_bal'),
                            'airtel_cg_bal' => DB::table('wallet_funding')->sum('airtel_cg_bal'),
                            'airtel_g_bal' => DB::table('wallet_funding')->sum('airtel_g_bal'),
                            'airtel_sme_bal' => DB::table('wallet_funding')->sum('airtel_sme_bal'),
                            'glo_cg_bal' => DB::table('wallet_funding')->sum('glo_cg_bal'),
                            'glo_g_bal' => DB::table('wallet_funding')->sum('glo_g_bal'),
                            'glo_sme_bal' => DB::table('wallet_funding')->sum('glo_sme_bal'),
                            'mobile_cg_bal' => DB::table('wallet_funding')->sum('mobile_cg_bal'),
                            'mobile_g_bal' => DB::table('wallet_funding')->sum('mobile_g_bal'),
                            'mobile_sme_bal' => DB::table('wallet_funding')->sum('mobile_sme_bal'),
                            'total_process' => DB::table('message')->where(['plan_status' => 0])->count(),
                            'total_data_proccess' => DB::table('data')->where(['plan_status' => 0])->count(),
                            // Charity Stats
                            'charity_escrow' => DB::table('charities')->sum('pending_balance'),
                            'charity_available' => DB::table('charities')->sum('available_balance'),
                            'total_donations' => DB::table('donations')->sum('amount'),
                            'today_donations' => $today_donations = DB::table('donations')->whereDate('created_at', Carbon::today())->sum('amount'),
                            'total_campaigns' => DB::table('campaigns')->count(),
                            'total_organizations' => DB::table('charities')->count(),

                            'today_data_success' => $today_data = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->count(),
                            'today_airtime_success' => $today_airtime = DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->count(),
                            'today_sales' => $today_sales = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->sum('amount') +
                                DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->sum('discount') +
                                $today_donations,

                            'yesterday_sales' => $yesterday_sales = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->sum('amount') +
                                DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->sum('discount') +
                                DB::table('donations')->whereDate('created_at', Carbon::yesterday())->sum('amount'),

                            'yesterday_trans' => $yesterday_trans = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->count() +
                                DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->count() +
                                DB::table('donations')->whereDate('created_at', Carbon::yesterday())->count(),

                            'sales_percent' => $yesterday_sales > 0 ? round((($today_sales - $yesterday_sales) / $yesterday_sales) * 100, 1) : ($today_sales > 0 ? 100 : 0),
                            'trans_percent' => $yesterday_trans > 0 ? round(((($today_data + $today_airtime + DB::table('donations')->whereDate('created_at', Carbon::today())->count()) - $yesterday_trans) / $yesterday_trans) * 100, 1) : (($today_data + $today_airtime + DB::table('donations')->whereDate('created_at', Carbon::today())->count()) > 0 ? 100 : 0),

                            'total_pending' => DB::table('data')->where('plan_status', 0)->count() +
                                DB::table('airtime')->where('plan_status', 0)->count() +
                                DB::table('cable')->where('plan_status', 0)->count() +
                                DB::table('bill')->where('plan_status', 0)->count() +
                                DB::table('campaigns')->where('payout_status', 'pending')->where('status', 'closed')->count(),
                        ];
                    } catch (\Exception $e) {
                        // If legacy tables don't exist, return payment gateway metrics only
                        $users_info = [
                            'total_revenue' => $total_revenue,
                            'total_transactions' => $total_transactions,
                            'successful_transactions' => $successful_transactions,
                            'failed_transactions' => $failed_transactions,
                            'pending_settlement' => $pending_settlement,
                            'active_businesses' => $active_businesses,
                            'registered_businesses' => $registered_businesses,
                            'pending_activations' => $pending_activations,
                            'total_virtual_accounts' => $total_virtual_accounts,
                            'wallet_balance' => 0,
                            'ref_balance' => 0,
                            'all_user' => DB::table('users')->count(),
                            'total_pending' => 0,
                        ];
                    }

                    return response()->json([
                        'status' => 'success',
                        'user' => $users_info,
                        'payment' => DB::table('habukhan_key')->first(),
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
    public function editUserDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    if (!empty($request->username)) {
                        $verify_user = DB::table('users')->where('id', $request->username);
                        if ($verify_user->count() == 1) {
                            return response()->json([
                                'status' => 'success',
                                'user' => $verify_user->first()
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'User ID Not Found'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'User ID Required'
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function CreateNewUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $main_validator = validator::make($request->all(), [
                        'name' => 'required|max:199|min:8',
                        'email' => 'required|unique:users,email|max:255|email',
                        'phone' => 'required|numeric|unique:users,phone|digits:11',
                        'password' => 'required|min:8',
                        'username' => 'required|unique:users,username|max:12|string|alpha_num',
                        'status' => 'required',
                        'type' => 'required'
                    ], [
                        'name.required' => 'Full Name is Required',
                        'email.required' => 'E-mail is Required',
                        'phone.required' => 'Phone Number Required',
                        'password.required' => 'Password Required',
                        'username.required' => 'Username Required',
                        'username.unique' => 'Username already Taken',
                        'phone.unique' => 'Phone Number already Taken',
                        'username.max' => 'Username Maximum Length is 12 ' . $request->username,
                        'email.unique' => 'Email Alreay Taken',
                        'password.min' => 'Password Not Strong Enough',
                        'name.min' => 'Invalid Full Name',
                        'name.max' => 'Invalid Full Name',
                        'phone.numeric' => 'Phone Number Must be Numeric ' . $request->phone,
                        'status.required' => 'Account Status Required',
                        'type.required' => 'Account Role Required'
                    ]);
                    //declaring user status
                    if ($request->status == 'Active' || $request->status == 'active') {
                        $status = 'active';
                    } else if ($request->status == 'Deactivate' || $request->status == 'suspended') {
                        $status = 'suspended';
                    } else if ($request->status == 'Banned' || $request->status == 'suspended') {
                        $status = 'suspended';
                    } else if ($request->status == 'Unverified' || $request->status == 'pending') {
                        $status = 'pending';
                    } else {
                        $status = 'pending';
                    }

                    //system kyc
                    if ($request->kyc == 'true') {
                        $kyc = 1;
                    } else {
                        $kyc = 0;
                    }
                    //checking referral username
                    if ($request->ref != null) {
                        $check_ref = DB::table('users')
                            ->where('username', '=', $request->ref)
                            ->count();
                    }
                    //profile_image
                    if ($request->hasFile('profile_image')) {
                        $validator = validator::make($request->all(), [
                            'profile_image' => 'required|image|max:2047|mimes:jpg,png,jpeg',
                        ]);
                        if ($validator->fails()) {
                            $path = null;
                            return response()->json([
                                'message' => $validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        } else {
                            $profile_image = $request->file('profile_image');
                            $profile_image_name = $request->username . '_' . $profile_image->getClientOriginalName();
                            $save_here = 'profile_image';
                            $path = $request->file('profile_image')->storeAs($save_here, $profile_image_name);
                        }
                    } else {
                        $path = null;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (substr($request->phone, 0, 1) != '0') {
                        return response()->json([
                            'message' => 'Invalid Phone Number',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if ($request->ref != null && $check_ref == 0) {
                        return response()->json([
                            'message' => 'Invalid Referral Username You can Leave the referral Box Empty',
                            'status' => '403'
                        ])->setStatusCode(403);
                    } elseif ($request->pin != null && !is_numeric($request->pin)) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Transaction Pin Must be Numeric'
                        ])->setStatusCode(403);
                    } else if ($request->pin != null && strlen($request->pin) != 4) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Transaction Pin Must be 4 Digit'
                        ])->setStatusCode(403);
                    } else {
                        // checking
                        $user = new User();
                        $user->name = $request->name;
                        $user->username = $request->username;
                        $user->email = $request->email;
                        $user->phone = $request->phone;
                        $user->password = password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16));
                        // $user->password = Hash::make($request->password);
                        $user->api_key = bin2hex(openssl_random_pseudo_bytes(30));
                        $user->balance = '0.00';
                        $user->referral_balance = '0.00';
                        $user->ref = $request->ref;
                        $user->type = $request->type;
                        $user->date = Carbon::now("Africa/Lagos");
                        $user->kyc = $kyc;
                        $user->status = $status;
                        $user->user_limit = $this->habukhan_key()->default_limit;
                        $user->pin = $request->pin;
                        $user->webhook = $request->webhook;
                        $user->about = $request->about;
                        $user->address = $request->address;
                        $user->profile_image = url('') . '/' . $path;
                        $user->save();
                        if ($user != null) {
                            $general = $this->general();
                            if ($status == 'pending' && $request->isVerified == false) {
                                $otp = random_int(100000, 999999);
                                $data = [
                                    'otp' => $otp
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
                            } else {
                                $email_data = [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'username' => $user->username,
                                    'title' => 'WELCOME EMAIL',
                                    'sender_mail' => $general->app_email,
                                    'system_email' => $general->app_email,
                                    'app_name' => $general->app_name
                                ];
                                MailController::send_mail($email_data, 'email.welcome');
                            }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Created'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable to Register User'
                            ])->setStatusCode(403);
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
    public function ChangeApiKey(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    if (DB::table('users')->where('username', $request->username)->count() > 0) {
                        if ($this->updateData(['api_key' => bin2hex(openssl_random_pseudo_bytes(30))], 'users', ['username' => $request->username])) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'ApiKey Upgraded'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'An Error Occured'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid User ID'
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    //validate all here
                    if (DB::table('users')->where(['id' => $request->user_id])->count() == 1) {
                        $main_validator = validator::make($request->all(), [
                            'name' => 'required',
                            'email' => "required|unique:user,email,$request->user_id",
                            'phone' => "required|numeric|unique:user,phone,$request->user_id|digits:11",
                            'status' => 'required',
                            'type' => 'required',
                            'user_limit' => 'required|numeric|digits_between:2,6',
                        ], [
                            'name.required' => 'Full Name is Required',
                            'email.required' => 'E-mail is Required',
                            'phone.required' => 'Phone Number Required',
                            'username.required' => 'Username Required',
                            'username.unique' => 'Username already Taken',
                            'phone.unique' => 'Phone Number already Taken',
                            'username.max' => 'Username Maximum Length is 12 ' . $request->username,
                            'email.unique' => 'Email Alreay Taken',
                            'password.min' => 'Password Not Strong Enough',
                            'name.min' => 'Invalid Full Name',
                            'name.max' => 'Invalid Full Name',
                            'phone.numeric' => 'Phone Number Must be Numeric ' . $request->phone,
                            'status.required' => 'Account Status Required',
                            'type.required' => 'Account Role Required'
                        ]);
                        //declaring user status
                        if ($request->status == 'Active' || $request->status == 'active') {
                            $status = 'active';
                        } else if ($request->status == 'Deactivate' || $request->status == 'suspended') {
                            $status = 'suspended';
                        } else if ($request->status == 'Banned' || $request->status == 'suspended') {
                            $status = 'suspended';
                        } else if ($request->status == 'Unverified' || $request->status == 'pending') {
                            $status = 'pending';
                        } else {
                            $status = 'pending';
                        }

                        //system kyc
                        if ($request->kyc == 'true') {
                            $kyc = 1;
                        } else {
                            $kyc = 0;
                        }
                        //checking referral username
                        if ($request->ref != null) {
                            $check_ref = DB::table('users')
                                ->where('username', '=', $request->ref)
                                ->count();
                        }
                        //profile_image
                        if ($request->hasFile('profile_image')) {
                            $validator = validator::make($request->all(), [
                                'profile_image' => 'required|image|max:2047|mimes:jpg,png,jpeg',
                            ]);
                            if ($validator->fails()) {
                                $path = null;
                                return response()->json([
                                    'message' => $validator->errors()->first(),
                                    'status' => 403
                                ])->setStatusCode(403);
                            } else {
                                $profile_image = $request->file('profile_image');
                                $profile_image_name = $request->username . '_' . $profile_image->getClientOriginalName();
                                $save_here = 'profile_image';
                                $path = url('') . '/' . $request->file('profile_image')->storeAs($save_here, $profile_image_name);
                            }
                        } else {
                            $path = $request->profile_image;
                        }
                        if ($main_validator->fails()) {
                            return response()->json([
                                'message' => $main_validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        } else if (substr($request->phone, 0, 1) != '0') {
                            return response()->json([
                                'message' => 'Invalid Phone Number',
                                'status' => 403
                            ])->setStatusCode(403);
                        } else if ($request->ref != null && $check_ref == 0) {
                            return response()->json([
                                'message' => 'Invalid Referral Username You can Leave the referral Box Empty',
                                'status' => '403'
                            ])->setStatusCode(403);
                        } elseif ($request->pin != null && !is_numeric($request->pin)) {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Transaction Pin Must be Numeric'
                            ])->setStatusCode(403);
                        } else if ($request->pin != null && strlen($request->pin) != 4) {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Transaction Pin Must be 4 Digit'
                            ])->setStatusCode(403);
                        } else {
                            // updateing
                            $user = User::find($request->user_id);
                            $user->name = $request->name;
                            $user->email = $request->email;
                            $user->phone = $request->phone;
                            $user->ref = $request->ref;
                            $user->type = $request->type;
                            $user->kyc = $kyc;
                            $user->status = $status;
                            $user->user_limit = $request->user_limit;
                            $user->reason = $request->reason;
                            $user->pin = $request->pin;
                            $user->webhook = $request->webhook;
                            $user->about = $request->about;
                            $user->address = $request->address;
                            $user->profile_image = $path;
                            $user->save();
                            $user->save();
                            if ($user != null) {
                                $general = $this->general();
                                if ($status == 'pending' && $request->isVerified == false) {
                                    $otp = random_int(100000, 999999);
                                    $data = [
                                        'otp' => $otp
                                    ];
                                    $tableid = [
                                        'username' => $request->username
                                    ];
                                    $this->updateData($data, 'users', $tableid);
                                    $email_data = [
                                        'name' => $request->name,
                                        'email' => $request->email,
                                        'username' => $request->username,
                                        'title' => 'Account Verification',
                                        'sender_mail' => $general->app_email,
                                        'app_name' => config('app.name'),
                                        'otp' => $otp
                                    ];
                                    MailController::send_mail($email_data, 'email.verify');
                                }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated Success'
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable to Update User'
                                ])->setStatusCode(403);
                            }
                        }
                    } else {
                        return response()->json([
                            'staus' => 403,
                            'message' => 'An Error Occured'
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function FilterUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN'])->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $users = DB::table('users')->where('username', 'LIKE', "%$request->username%")->orWhere('email', 'LIKE', "%$request->username%")->orWhere('phone', 'LIKE', "%$request->username%")->orWhere('name', 'LIKE', "%$request->username%")->limit(10)
                        ->get();

                    return response()->json([
                        'status' => 'success',
                        'user' => $users
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
    public function CreditUserHabukhan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN'])->orwhere('type', 'CUSTOMER');
                });
                $admin = $check_user->first();
                $general = $this->general();
                $all_admin = DB::table('users')->where(['status' => 'active'])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN'])->orwhere('type', 'CUSTOMER');
                })->get();
                if ($check_user->count() > 0) {
                    $validator = validator::make($request->all(), [
                        'user_username' => 'required|string',
                        'wallet' => 'required|string',
                        'amount' => 'required|numeric|integer|not_in:0|gt:0',
                        'credit' => 'required|string',
                        'reason' => 'required|string'
                    ], [
                        'credit.required' => 'Credit/Debit Required',
                        'wallet.required' => 'User Wallet Required'
                    ]);
                    //get which user
                    $user = DB::table('users')->where('username', $request->user_username);
                    $user_details = $user->first();
                    // wallet statement
                    if ($request->wallet == 'wallet') {
                        $wallet = 'User Wallet';
                    } else if ($request->wallet == 'business_wallet') {
                        $wallet = 'Business Wallet';
                    } else if ($request->wallet == 'mtn_cg_bal') {
                        $wallet = 'MTN CG WALLET';
                    } else if ($request->wallet == 'mtn_g_bal') {
                        $wallet = 'MTN GIFTING WALLET';
                    } else if ($request->wallet == 'mtn_sme_bal') {
                        $wallet = 'MTN SME WALLET';
                    } else if ($request->wallet == 'airtel_cg_bal') {
                        $wallet = 'AIRTEL CG WALLET';
                    } else if ($request->wallet == 'airtel_g_bal') {
                        $wallet = 'AIRTEL GIFTING WALLET';
                    } else if ($request->wallet == 'airtel_sme_bal') {
                        $wallet = 'AIRTEL SME WALLET';
                    } else if ($request->wallet == 'glo_cg_bal') {
                        $wallet = 'GLO CG WALLET';
                    } else if ($request->wallet == 'glo_g_bal') {
                        $wallet = 'GLO GIFTING WALLET';
                    } else if ($request->wallet == 'glo_sme_bal') {
                        $wallet = 'GLO SME WALLET';
                    } else if ($request->wallet == 'mobile_cg_bal') {
                        $wallet = '9MOBILE CG WALLET';
                    } else if ($request->wallet == 'mobile_g_bal') {
                        $wallet = '9MOBILE GIFTING WALLET';
                    } else if ($request->wallet == 'mobile_sme_bal') {
                        $wallet = '9MOBILE SME WALLET';
                    }
                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if ($user->count() != 1) {
                        return response()->json([
                            'message' => 'Unable to Get the Correspond User Username',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (empty($wallet)) {
                        return response()->json([
                            'message' => 'Account Wallet Not Found',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        if ($request->credit == 'credit') {
                            $all_amount_credited = DB::table('transactions')
                                ->where('status', 'success')
                                ->where('type', 'credit')
                                ->where('metadata->credit_by', $admin->username)
                                ->where('created_at', '>=', Carbon::today())
                                ->sum('amount');
                            if ($admin->type == 'CUSTOMER' && $request->amount > $this->core()->customer_amount) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Maximum Amount to Credit Users Daily is ₦' . number_format($this->core()->customer_amount, 2)
                                ])->setStatusCode(403);
                            } else if ($admin->type == 'CUSTOMER' && $all_amount_credited > $this->core()->customer_amount) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Credit User Daily Amount Exhausted'
                                ])->setStatusCode(403);
                            } else if ($admin->type == 'CUSTOMER' && $all_amount_credited + $request->amount > $this->core()->customer_amount) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Daliy Amount Remaining To Credit A User is ₦' . number_format($this->core()->customer_amount - $all_amount_credited, 2)
                                ])->setStatusCode(403);
                            } else {
                                $deposit_ref = $this->generate_ref('Credit');
                                // ==========================================
                                // crediting users here
                                if ($request->wallet == 'business_wallet') {
                                    // credit business wallet
                                    $company = DB::table('companies')->where('user_id', $user_details->id)->first();
                                    if (!$company) {
                                        return response()->json(['status' => 403, 'message' => 'No Business Account found for this user'])->setStatusCode(403);
                                    }

                                    $companyWallet = DB::table('company_wallets')->where('company_id', $company->id)->first();
                                    if (!$companyWallet) {
                                        // Create wallet if not exists
                                        $walletId = DB::table('company_wallets')->insertGetId([
                                            'company_id' => $company->id,
                                            'currency' => 'NGN',
                                            'balance' => $request->amount,
                                            'created_at' => now(),
                                            'updated_at' => now()
                                        ]);
                                        $oldBal = 0;
                                        $newBal = $request->amount;
                                    } else {
                                        $oldBal = $companyWallet->balance;
                                        $newBal = $oldBal + $request->amount;
                                        DB::table('company_wallets')->where('id', $companyWallet->id)->update([
                                            'balance' => $newBal,
                                            'updated_at' => now()
                                        ]);
                                    }

                                    // Deposit log
                                    $tx = \App\Models\Transaction::create([
                                        'user_id' => $user_details->id,
                                        'company_id' => $user_details->active_company_id,
                                        'type' => 'credit',
                                        'category' => 'other',
                                        'amount' => $request->amount,
                                        'total_amount' => $request->amount,
                                        'status' => 'success',
                                        'reference' => $deposit_ref,
                                        'description' => $wallet . ' Manual Credit - ' . $request->reason,
                                        'balance_before' => $oldBal,
                                        'balance_after' => $newBal,
                                        'metadata' => ['credit_by' => $admin->username, 'wallet_type' => $wallet],
                                        'processed_at' => now(),
                                    ]);

                                    return response()->json(['status' => 200, 'message' => 'Business Wallet Credited Successfully', 'account_type' => 'Business Wallet']);
                                }

                                if ($request->wallet == 'wallet') {
                                    //credit user wallet
                                    // now update user
                                    $update_data = [
                                        'balance' => $user_details->balance + $request->amount
                                    ];
                                    if ($this->updateData($update_data, 'users', ['id' => $user_details->id])) {
                                        // insert into message
                                        $message_data = [
                                            'username' => $user_details->username,
                                            'amount' => $request->amount,
                                            'message' => $request->reason,
                                            'oldbal' => $user_details->balance,
                                            'newbal' => $user_details->balance + $request->amount,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $deposit_ref,
                                            'role' => 'credit'
                                        ];
                                        $this->inserting_data('message', $message_data);
                                        // inserting notif
                                        $notif_data = [
                                            'username' => $user_details->username,
                                            'message' => 'Your Account has been credited ₦' . $request->amount . ' by admin',
                                            'date' => $this->system_date(),
                                            'habukhan' => 0
                                        ];
                                        $this->inserting_data('notif', $notif_data);
                                        // inserting into transactions table
                                        $tx = \App\Models\Transaction::create([
                                            'user_id' => $user_details->id,
                                            'company_id' => $user_details->active_company_id,
                                            'type' => 'credit',
                                            'category' => 'other',
                                            'amount' => $request->amount,
                                            'total_amount' => $request->amount,
                                            'status' => 'success',
                                            'reference' => $deposit_ref,
                                            'description' => $wallet . ' Manual Credit - ' . $request->reason,
                                            'balance_before' => $user_details->balance,
                                            'balance_after' => $user_details->balance + $request->amount,
                                            'metadata' => ['credit_by' => $admin->username, 'wallet_type' => $wallet],
                                            'processed_at' => now(),
                                        ]);

                                        // Handle referral for manual credit
                                        if ($this->core()->referral == 1 && $user_details->ref) {
                                            if (DB::table('transactions')->where(['user_id' => $user_details->id, 'status' => 'success', 'type' => 'credit'])->count() == 1) {
                                                if (DB::table('users')->where(['username' => $user_details->ref, 'status' => 'active'])->exists()) {
                                                    $user_ref = DB::table('users')->where(['username' => $user_details->ref, 'status' => 'active'])->first();
                                                    $credit_ref = ($request->amount / 100) * $this->core()->referral_price;
                                                    DB::table('users')->where(['username' => $user_details->ref, 'status' => 'active'])->update(['referral_balance' => $user_ref->referral_balance + $credit_ref]);

                                                    DB::table('message')->insert([
                                                        'username' => $user_ref->username,
                                                        'amount' => $credit_ref,
                                                        'message' => 'Referral Earning From ' . ucfirst($user_details->username),
                                                        'oldbal' => $user_ref->referral_balance,
                                                        'newbal' => $user_ref->referral_balance + $credit_ref,
                                                        'habukhan_date' => $this->system_date(),
                                                        'plan_status' => 1,
                                                        'transid' => $deposit_ref . '-REF',
                                                        'role' => 'referral'
                                                    ]);
                                                }
                                            }
                                        }

                                        if ($request->isnotif == true) {
                                            //sending mail over here
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $user_details->email,
                                                'username' => $user_details->username,
                                                'title' => 'Account Funding',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_details->balance, 2),
                                                'newbal' => number_format($user_details->balance + $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref
                                            ];
                                            MailController::send_mail($email_data, 'email.deposit');
                                        }
                                        foreach ($all_admin as $habukhan) {
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $habukhan->email,
                                                'username' => strtoupper($user_details->username),
                                                'title' => 'Account Funding',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_details->balance, 2),
                                                'newbal' => number_format($user_details->balance + $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref,
                                                'credited_by' => strtoupper($admin->username)
                                            ];
                                            MailController::send_mail($email_data, 'email.admin');
                                        }
                                        return response()->json([
                                            'status' => 'success',
                                            'account_type' => $wallet,
                                            'message' => 'Account Credited SuccessFully'
                                        ]);
                                    } else {
                                        return response()->json([
                                            'message' => 'Unable to Credit User',
                                            'status' => 403
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    // funding the wallet funding (Stock Funding)
                                    $stock_user_wallet = DB::table('wallet_funding')->where('username', $request->user_username);
                                    if ($stock_user_wallet->count() == 1) {
                                        $user_stock_details = $stock_user_wallet->first();
                                        $ad = $request->wallet;
                                        $update_data = [
                                            $request->wallet => $user_stock_details->$ad + $request->amount
                                        ];
                                        if ($this->updateData($update_data, 'wallet_funding', ['id' => $user_stock_details->id])) {
                                            // insert into message
                                            $message_data = [
                                                'username' => $user_details->username,
                                                'amount' => $request->amount,
                                                'message' => $request->reason,
                                                'oldbal' => $user_stock_details->$ad,
                                                'newbal' => $user_stock_details->$ad + $request->amount,
                                                'habukhan_date' => $this->system_date(),
                                                'plan_status' => 1,
                                                'transid' => $deposit_ref,
                                                'role' => 'credit'
                                            ];
                                            $this->inserting_data('message', $message_data);
                                            // inserting notif
                                            $notif_data = [
                                                'username' => $user_details->username,
                                                'message' => 'Your Account has been credited ₦' . $request->amount . ' by admin',
                                                'date' => $this->system_date(),
                                                'habukhan' => 0
                                            ];
                                            $this->inserting_data('notif', $notif_data);
                                            // inserting into transactions table
                                            $tx = \App\Models\Transaction::create([
                                                'user_id' => $user_details->id,
                                                'company_id' => $user_details->active_company_id,
                                                'type' => 'credit',
                                                'category' => 'other',
                                                'amount' => $request->amount,
                                                'total_amount' => $request->amount,
                                                'status' => 'success',
                                                'reference' => $deposit_ref,
                                                'description' => $wallet . ' Manual Credit - ' . $request->reason,
                                                'balance_before' => $user_stock_details->$ad,
                                                'balance_after' => $user_stock_details->$ad + $request->amount,
                                                'metadata' => ['credit_by' => $admin->username, 'wallet_type' => $wallet],
                                                'processed_at' => now(),
                                            ]);
                                            if ($request->isnotif == true) {
                                                //sending mail over here
                                                $email_data = [
                                                    'name' => $user_details->name,
                                                    'email' => $user_details->email,
                                                    'username' => $user_details->username,
                                                    'title' => 'Account Funding',
                                                    'sender_mail' => $general->app_email,
                                                    'app_name' => config('app.name'),
                                                    'wallet' => $wallet,
                                                    'amount' => number_format($request->amount, 2),
                                                    'oldbal' => number_format($user_stock_details->$ad, 2),
                                                    'newbal' => number_format($user_stock_details->$ad + $request->amount, 2),
                                                    'deposit_type' => strtoupper($request->credit),
                                                    'transid' => $deposit_ref
                                                ];
                                                MailController::send_mail($email_data, 'email.deposit');
                                            }
                                            foreach ($all_admin as $habukhan) {
                                                $email_data = [
                                                    'name' => $user_details->name,
                                                    'email' => $habukhan->email,
                                                    'username' => strtoupper($user_details->username),
                                                    'title' => 'Account Funding',
                                                    'sender_mail' => $general->app_email,
                                                    'app_name' => config('app.name'),
                                                    'wallet' => $wallet,
                                                    'amount' => number_format($request->amount, 2),
                                                    'oldbal' => number_format($user_details->balanceance, 2),
                                                    'newbal' => number_format($user_details->balanceance + $request->amount, 2),
                                                    'deposit_type' => strtoupper($request->credit),
                                                    'transid' => $deposit_ref,
                                                    'credited_by' => strtoupper($admin->username)
                                                ];
                                                MailController::send_mail($email_data, 'email.admin');
                                            }
                                            return response()->json([
                                                'status' => 'success',
                                                'account_type' => $wallet,
                                                'message' => 'Account Credited SuccessFully'
                                            ]);
                                        } else {
                                            return response()->json([
                                                'status' => 403,
                                                'message' => 'Unable to Fund User Stock Wallet'
                                            ])->setStatusCode(403);
                                        }
                                    } else {
                                        return response()->json([
                                            'status' => 403,
                                            'message' => strtoupper($user_details->username) . ' has not login and is wallet funnding account has not been created'
                                        ])->setStatusCode(403);
                                    }
                                }
                            }
                        } else if ($request->credit == 'debit') {
                            $deposit_ref = $this->generate_ref('Debit');
                            // debiting user over here
                            if ($request->wallet == 'wallet') {
                                // debiting ain wallet
                                $update_data = [
                                    'balance' => $user_details->balance - $request->amount
                                ];
                                if ($this->updateData($update_data, 'users', ['id' => $user_details->id])) {
                                    // insert into message
                                    $message_data = [
                                        'username' => $user_details->username,
                                        'amount' => $request->amount,
                                        'message' => $request->reason,
                                        'oldbal' => $user_details->balance,
                                        'newbal' => $user_details->balance - $request->amount,
                                        'habukhan_date' => $this->system_date(),
                                        'plan_status' => 1,
                                        'transid' => $deposit_ref,
                                        'role' => 'debit'
                                    ];
                                    $this->inserting_data('message', $message_data);
                                    // inserting notif
                                    $notif_data = [
                                        'username' => $user_details->username,
                                        'message' => 'Your Account has been debited ₦' . $request->amount . ' by admin',
                                        'date' => $this->system_date(),
                                        'habukhan' => 0
                                    ];
                                    $this->inserting_data('notif', $notif_data);

                                    // inserting into transactions table
                                    $tx = \App\Models\Transaction::create([
                                        'user_id' => $user_details->id,
                                        'company_id' => $user_details->active_company_id,
                                        'type' => 'debit',
                                        'category' => 'other',
                                        'amount' => $request->amount,
                                        'total_amount' => $request->amount,
                                        'status' => 'success',
                                        'reference' => $deposit_ref,
                                        'description' => $wallet . ' Manual Debit - ' . $request->reason,
                                        'balance_before' => $user_details->balance,
                                        'balance_after' => $user_details->balance - $request->amount,
                                        'metadata' => ['credit_by' => $admin->username, 'wallet_type' => $wallet],
                                        'processed_at' => now(),
                                    ]);
                                    if ($request->isnotif == true) {
                                        //sending mail over here
                                        $email_data = [
                                            'name' => $user_details->name,
                                            'email' => $user_details->email,
                                            'username' => $user_details->username,
                                            'title' => 'Account Debited',
                                            'sender_mail' => $general->app_email,
                                            'app_name' => config('app.name'),
                                            'wallet' => $wallet,
                                            'amount' => number_format($request->amount, 2),
                                            'oldbal' => number_format($user_details->balanceance, 2),
                                            'newbal' => number_format($user_details->balanceance - $request->amount, 2),
                                            'deposit_type' => strtoupper($request->credit),
                                            'transid' => $deposit_ref
                                        ];
                                        MailController::send_mail($email_data, 'email.deposit');
                                    }
                                    foreach ($all_admin as $habukhan) {
                                        $email_data = [
                                            'name' => $user_details->name,
                                            'email' => $habukhan->email,
                                            'username' => strtoupper($user_details->username),
                                            'title' => 'Account Funding',
                                            'sender_mail' => $general->app_email,
                                            'app_name' => config('app.name'),
                                            'wallet' => $wallet,
                                            'amount' => number_format($request->amount, 2),
                                            'oldbal' => number_format($user_details->balanceance, 2),
                                            'newbal' => number_format($user_details->balanceance - $request->amount, 2),
                                            'deposit_type' => strtoupper($request->credit),
                                            'transid' => $deposit_ref,
                                            'credited_by' => strtoupper($admin->username)
                                        ];
                                        MailController::send_mail($email_data, 'email.admin');
                                    }
                                    return response()->json([
                                        'status' => 'success',
                                        'account_type' => $wallet,
                                        'message' => 'Account Debited SuccessFully'
                                    ]);
                                } else {
                                    return response()->json([
                                        'message' => 'Unable to Debit User',
                                        'status' => 403
                                    ])->setStatusCode(403);
                                }
                            } else {
                                // debiting stock wallet
                                $stock_user_wallet = DB::table('wallet_funding')->where('username', $request->user_username);
                                if ($stock_user_wallet->count() == 1) {
                                    $user_stock_details = $stock_user_wallet->first();
                                    $ad = $request->wallet;
                                    $update_data = [
                                        $request->wallet => $user_stock_details->$ad - $request->amount
                                    ];
                                    if ($this->updateData($update_data, 'wallet_funding', ['id' => $user_stock_details->id])) {
                                        // insert into message
                                        $message_data = [
                                            'username' => $user_details->username,
                                            'amount' => $request->amount,
                                            'message' => $request->reason,
                                            'oldbal' => $user_stock_details->$ad,
                                            'newbal' => $user_stock_details->$ad - $request->amount,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $deposit_ref,
                                            'role' => 'debit'
                                        ];
                                        $this->inserting_data('message', $message_data);
                                        // inserting notif
                                        $notif_data = [
                                            'username' => $user_details->username,
                                            'message' => 'Your Account has been debited ₦' . $request->amount . ' by admin',
                                            'date' => $this->system_date(),
                                            'habukhan' => 0
                                        ];
                                        $this->inserting_data('notif', $notif_data);

                                        // inserting into transactions table
                                        $tx = \App\Models\Transaction::create([
                                            'user_id' => $user_details->id,
                                            'company_id' => $user_details->active_company_id,
                                            'type' => 'debit',
                                            'category' => 'other',
                                            'amount' => $request->amount,
                                            'total_amount' => $request->amount,
                                            'status' => 'success',
                                            'reference' => $deposit_ref,
                                            'description' => $wallet . ' Manual Debit - ' . $request->reason,
                                            'balance_before' => $user_stock_details->$ad,
                                            'balance_after' => $user_stock_details->$ad - $request->amount,
                                            'metadata' => ['credit_by' => $admin->username, 'wallet_type' => $wallet],
                                            'processed_at' => now(),
                                        ]);
                                        if ($request->isnotif == true) {
                                            //sending mail over here
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $user_details->email,
                                                'username' => $user_details->username,
                                                'title' => 'Account Debited',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_stock_details->$ad, 2),
                                                'newbal' => number_format($user_stock_details->$ad - $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref
                                            ];
                                            MailController::send_mail($email_data, 'email.deposit');
                                        }
                                        foreach ($all_admin as $habukhan) {
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $habukhan->email,
                                                'username' => strtoupper($user_details->username),
                                                'title' => 'Account Funding',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_details->balance, 2),
                                                'newbal' => number_format($user_details->balance - $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref,
                                                'credited_by' => strtoupper($admin->username)
                                            ];
                                            MailController::send_mail($email_data, 'email.admin');
                                        }
                                        return response()->json([
                                            'status' => 'success',
                                            'account_type' => $wallet,
                                            'message' => 'Account Debited SuccessFully'
                                        ]);
                                    } else {
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Unable to Debit User Stock Wallet'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 403,
                                        'message' => strtoupper($user_details->username) . ' has not login and is wallet funnding account has not been created'
                                    ])->setStatusCode(403);
                                }
                            }
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Account Debit/Credit Unknown'
                            ])->setStatusCode(403);
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
    public function UpgradeUserAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                $general = $this->general();
                $user = DB::table('users')->where('username', $request->user_username);
                $details = $user->first();
                if ($check_user->count() > 0) {
                    $validator = validator::make($request->all(), [
                        'user_username' => 'required|string',
                        'role' => 'required|string',
                    ], [
                        'role.required' => 'Account Role Required',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if ($user->count() != 1) {
                        return response()->json([
                            'message' => 'Unable to Get the Correspond User Username',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        if (
                            $this->updateData([
                                'type' => $request->role
                            ], 'users', ['id' => $details->id])
                        ) {
                            $dis = $this->generate_ref('Upgrade/Downgrade');
                            $message_data = [
                                'username' => $details->username,
                                'amount' => 0.00,
                                'message' => 'Your Acount Has Been Upgrade to ' . $request->role . ' Package',
                                'oldbal' => $details->balanceance,
                                'newbal' => $details->balanceance,
                                'habukhan_date' => $this->system_date(),
                                'plan_status' => 1,
                                'transid' => $dis,
                                'role' => 'upgrade'
                            ];
                            $this->inserting_data('message', $message_data);
                            if ($request->isnotif == true) {
                                //sending mail over here
                                $email_data = [
                                    'name' => $details->name,
                                    'email' => $details->email,
                                    'username' => $details->username,
                                    'title' => 'Account Upgrade/Downgrade',
                                    'sender_mail' => $general->app_email,
                                    'app_name' => config('app.name'),
                                    'amount' => 0.00,
                                    'oldbal' => number_format($details->balanceance, 2),
                                    'newbal' => number_format($details->balanceance, 2),
                                    'deposit_type' => strtoupper($request->credit),
                                    'transid' => $dis,
                                    'role' => $request->role
                                ];
                                MailController::send_mail($email_data, 'email.upgrade');
                            }
                            // inserting notif
                            $notif_data = [
                                'username' => $details->username,
                                'message' => 'Your Acount Has Been Upgrade to ' . $request->role . ' Package',
                                'date' => $this->system_date(),
                                'habukhan' => 0
                            ];
                            $this->inserting_data('notif', $notif_data);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Acount Upgraded'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable to upgrade user'
                            ])->setStatusCode(403);
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
    public function ResetUserPassword(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                $general = $this->general();
                $user = DB::table('users')->where('username', $request->user_username);
                $details = $user->first();
                if ($check_user->count() > 0) {
                    $validator = validator::make($request->all(), [
                        'user_username' => 'required|string',
                        'password' => 'required|string|min:8',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if ($user->count() != 1) {
                        return response()->json([
                            'message' => 'Unable to Get the Correspond User Username',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        if (
                            $this->updateData([
                                'password' => password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16)),
                            ], 'users', ['id' => $details->id])
                        ) {
                            if ($request->isnotif == true) {
                                //sending mail over here
                                $email_data = [
                                    'name' => $details->name,
                                    'email' => $details->email,
                                    'title' => 'Password Reset',
                                    'sender_mail' => $general->app_email,
                                    'app_name' => config('app.name'),
                                    'password' => $request->password,
                                    'username' => $details->username
                                ];
                                MailController::send_mail($email_data, 'email.admin_reset');
                            }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Password Reseted'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable to Reset User Password'
                            ])->setStatusCode(403);
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
    public function Automated(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        $successCount = 0;
                        for ($i = 0; $i < count($request->username); $i++) {
                            $username = $request->username[$i];
                            $delete_user = DB::table('users')->where('username', $username);
                            $user_id = $delete_user->first();
                            if ($user_id) {
                                $id = $user_id->id;
                                $data = [
                                    'autofund' => null,
                                    'wema' => null,
                                    'kolomoni_mfb' => null,
                                    'sterlen' => null,
                                    'fed' => null
                                ];
                                $updated = $this->updateData($data, 'users', ['id' => $id]);
                                if ($updated || $updated === 0) {
                                    $successCount++;
                                }
                            }
                        }
                        if ($successCount > 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Details Deleted Successfully'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To delete Account or No Changes Made'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'User ID  Required'
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function BankDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        $successCount = 0;
                        for ($i = 0; $i < count($request->username); $i++) {
                            $username = $request->username[$i];
                            $delete_user = DB::table('users')->where('username', $username);
                            $user = $delete_user->first();
                            if ($user) {
                                $id = $user->username;
                                // user_bank table is obsolete. Skipping deletion.
                                $successCount++;
                            }
                        }
                        if ($successCount > 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Details Deleted Successfully'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To delete Account'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'User ID  Required'
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AddBlock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                $admin = $check_user->first();
                if ($check_user->count() == 1) {
                    if (!empty($request->number)) {
                        if (DB::table('block')->where('number', $request->number)->count() == 0) {
                            if ($this->inserting_data('block', ['number' => $request->number, 'date' => $this->system_date(), 'added_by' => $admin->username])) {
                                return response()->json([
                                    'status' => 'success'
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable to Add Block Number'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Block Number Added Already'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Block Number Required'
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DeleteBlock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    if (isset($request->number)) {
                        $successCount = 0;
                        for ($i = 0; $i < count($request->number); $i++) {
                            $number = $request->number[$i];
                            $delete_block = DB::table('block')->where('number', $number);
                            $block = $delete_block->first();
                            if ($block) {
                                $id = $block->id;
                                $deleted = DB::table('block')->where('id', $id)->delete();
                                if ($deleted) {
                                    $successCount++;
                                }
                            }
                        }
                        if ($successCount > 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Blocked Number Deleted Successfully'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To delete Account'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Block Id Required'
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
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function Discount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                $database_name = null;
                if ($check_user->count() == 1) {
                    if (isset($request->database_name)) {
                        $database_name = $request->database_name;
                        $search = strtolower($request->search);
                    }

                    if ($database_name == 'wallet_funding') {
                        if (!empty($search)) {
                            return response()->json([
                                'all_stock' => DB::table('wallet_funding')->where(function ($query) use ($search) {
                                    $query->orWhere('username', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->input('perPage', 15))
                            ]);
                        } else {
                            return response()->json([
                                'all_stock' => DB::table('wallet_funding')->orderBy('id', 'desc')->paginate($request->input('perPage', 15))
                            ]);
                        }
                    } else {
                        $habukhan = $check_user->first();
                        $cid = $habukhan->active_company_id;

                        return response()->json([
                            'airtime_discount' => DB::table('airtime_discount')->where('company_id', $cid)->first(),
                            'cable_charges' => Schema::hasTable('cable_charge') ? DB::table('cable_charge')->where('company_id', $cid)->first() : (object) [],
                            'bill_charges' => Schema::hasTable('bill_charge') ? DB::table('bill_charge')->where('company_id', $cid)->first() : (object) [],
                            'cash_discount' => DB::table('cash_discount')->where('company_id', $cid)->first(),
                            'result_charges' => Schema::hasTable('result_charge') ? DB::table('result_charge')->where('company_id', $cid)->first() : (object) [],
                            'all_network' => DB::table('network')->get(),
                            'cable_result_lock' => Schema::hasTable('cable_result_lock') ? DB::table('cable_result_lock')->first() : (object) [],

                            'other_api' => (object) array_merge((array) DB::table('other_api')->first(), ['autopilot_key' => DB::table('habukhan_key')->first()->autopilot_key ?? '']),
                            'airtime_sel' => DB::table('airtime_sel')->first(),
                            'bill_sel' => DB::table('bill_sel')->first(),
                            'cable_sel' => DB::table('cable_sel')->first(),
                            'bulksms_sel' => DB::table('bulksms_sel')->first(),
                            'exam_sel' => DB::table('exam_sel')->first(),
                            'card_settings' => DB::table('card_settings')->where('id', 1)->first(),
                        ]);
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
    public function AirtimeDiscount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn_vtu_smart' => 'required|numeric|between:0,100',
                        'mtn_vtu_agent' => 'required|numeric|between:0,100',
                        'mtn_vtu_awuf' => 'required|numeric|between:0,100',
                        'mtn_vtu_api' => 'required|numeric|between:0,100',
                        'mtn_vtu_special' => 'required|numeric|between:0,100',
                        // airtel vtu
                        'airtel_vtu_smart' => 'required|numeric|between:0,100',
                        'airtel_vtu_agent' => 'required|numeric|between:0,100',
                        'airtel_vtu_awuf' => 'required|numeric|between:0,100',
                        'airtel_vtu_api' => 'required|numeric|between:0,100',
                        'airtel_vtu_special' => 'required|numeric|between:0,100',
                        //  glo vtu
                        'glo_vtu_smart' => 'required|numeric|between:0,100',
                        'glo_vtu_agent' => 'required|numeric|between:0,100',
                        'glo_vtu_awuf' => 'required|numeric|between:0,100',
                        'glo_vtu_api' => 'required|numeric|between:0,100',
                        'glo_vtu_special' => 'required|numeric|between:0,100',
                        // 9mobile
                        'mobile_vtu_smart' => 'required|numeric|between:0,100',
                        'mobile_vtu_agent' => 'required|numeric|between:0,100',
                        'mobile_vtu_awuf' => 'required|numeric|between:0,100',
                        'mobile_vtu_api' => 'required|numeric|between:0,100',
                        'mobile_vtu_special' => 'required|numeric|between:0,100',

                        // mtn share and sell
                        'mtn_share_smart' => 'required|numeric|between:0,100',
                        'mtn_share_agent' => 'required|numeric|between:0,100',
                        'mtn_share_awuf' => 'required|numeric|between:0,100',
                        'mtn_share_api' => 'required|numeric|between:0,100',
                        'mtn_share_special' => 'required|numeric|between:0,100',
                        // airtel share and sell
                        'airtel_share_smart' => 'required|numeric|between:0,100',
                        'airtel_share_agent' => 'required|numeric|between:0,100',
                        'airtel_share_awuf' => 'required|numeric|between:0,100',
                        'airtel_share_api' => 'required|numeric|between:0,100',
                        'airtel_share_special' => 'required|numeric|between:0,100',
                        //  glo share and sell
                        'glo_share_smart' => 'required|numeric|between:0,100',
                        'glo_share_agent' => 'required|numeric|between:0,100',
                        'glo_share_awuf' => 'required|numeric|between:0,100',
                        'glo_share_api' => 'required|numeric|between:0,100',
                        'glo_share_special' => 'required|numeric|between:0,100',
                        // 9mobile share and sell
                        'mobile_share_smart' => 'required|numeric|between:0,100',
                        'mobile_share_agent' => 'required|numeric|between:0,100',
                        'mobile_share_awuf' => 'required|numeric|between:0,100',
                        'mobile_share_api' => 'required|numeric|between:0,100',
                        'mobile_share_special' => 'required|numeric|between:0,100',

                        // min and max
                        'min_airtime' => 'required|numeric',
                        'max_airtime' => 'required|numeric'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $habukhan = $check_user->first();
                        $cid = $habukhan->active_company_id;
                        $data = [
                            'mtn_vtu_smart' => $request->mtn_vtu_smart,
                            'mtn_vtu_awuf' => $request->mtn_vtu_awuf,
                            'mtn_vtu_agent' => $request->mtn_vtu_agent,
                            'mtn_vtu_api' => $request->mtn_vtu_api,
                            'mtn_vtu_special' => $request->mtn_vtu_special,
                            // airtel vtu
                            'airtel_vtu_smart' => $request->airtel_vtu_smart,
                            'airtel_vtu_awuf' => $request->airtel_vtu_awuf,
                            'airtel_vtu_agent' => $request->airtel_vtu_agent,
                            'airtel_vtu_api' => $request->airtel_vtu_api,
                            'airtel_vtu_special' => $request->airtel_vtu_special,

                            // glo vtu
                            'glo_vtu_smart' => $request->glo_vtu_smart,
                            'glo_vtu_awuf' => $request->glo_vtu_awuf,
                            'glo_vtu_agent' => $request->glo_vtu_agent,
                            'glo_vtu_api' => $request->glo_vtu_api,
                            'glo_vtu_special' => $request->glo_vtu_special,

                            // 9mobile vtu
                            'mobile_vtu_smart' => $request->mobile_vtu_smart,
                            'mobile_vtu_awuf' => $request->mobile_vtu_awuf,
                            'mobile_vtu_agent' => $request->mobile_vtu_agent,
                            'mobile_vtu_api' => $request->mobile_vtu_api,
                            'mobile_vtu_special' => $request->mobile_vtu_special,

                            // mtn share and sell

                            'mtn_share_smart' => $request->mtn_share_smart,
                            'mtn_share_awuf' => $request->mtn_share_awuf,
                            'mtn_share_agent' => $request->mtn_share_agent,
                            'mtn_share_api' => $request->mtn_share_api,
                            'mtn_share_special' => $request->mtn_share_special,
                            // airtel share ad sell
                            'airtel_share_smart' => $request->airtel_share_smart,
                            'airtel_share_awuf' => $request->airtel_share_awuf,
                            'airtel_share_agent' => $request->airtel_share_agent,
                            'airtel_share_api' => $request->airtel_share_api,
                            'airtel_share_special' => $request->airtel_share_special,

                            // glo share and sell
                            'glo_share_smart' => $request->glo_share_smart,
                            'glo_share_awuf' => $request->glo_share_awuf,
                            'glo_share_agent' => $request->glo_share_agent,
                            'glo_share_api' => $request->glo_share_api,
                            'glo_share_special' => $request->glo_share_special,

                            // 9mobile share and sell
                            'mobile_share_smart' => $request->mobile_share_smart,
                            'mobile_share_awuf' => $request->mobile_share_awuf,
                            'mobile_share_agent' => $request->mobile_share_agent,
                            'mobile_share_api' => $request->mobile_share_api,
                            'mobile_share_special' => $request->mobile_share_special,

                            // max and min

                            'max_airtime' => $request->max_airtime,
                            'min_airtime' => $request->min_airtime
                        ];

                        $existing = DB::table('airtime_discount')->where('company_id', $cid)->first();
                        if ($existing) {
                            DB::table('airtime_discount')->where('id', $existing->id)->update($data);
                            $message = "Airtime Discount Updated Successfully";
                        } else {
                            $data['company_id'] = $cid;
                            DB::table('airtime_discount')->insert($data);
                            $message = "Custom Airtime Discount Created for Your Company";
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => $message
                        ]);
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
    public function CableCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'dstv' => 'required|numeric',
                        'gotv' => 'required|numeric',
                        'startimes' => 'required|numeric',
                        'showmax' => 'required|numeric',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $habukhan = $check_user->first();
                        $cid = $habukhan->active_company_id;
                        $data = [
                            'dstv' => $request->dstv,
                            'gotv' => $request->gotv,
                            'startimes' => $request->startimes,
                            'showmax' => $request->showmax,
                            'direct' => $request->direct ? 1 : 0
                        ];

                        $existing = DB::table('cable_charge')->where('company_id', $cid)->first();
                        if ($existing) {
                            DB::table('cable_charge')->where('id', $existing->id)->update($data);
                            $message = "Cable Charges Updated Successfully";
                        } else {
                            $data['company_id'] = $cid;
                            DB::table('cable_charge')->insert($data);
                            $message = "Custom Cable Charges Created for Your Company";
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => $message
                        ]);
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
    public function BillCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'bill' => 'required|numeric',
                        'bill_min' => 'required|numeric',
                        'bill_max' => 'required|numeric',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $habukhan = $check_user->first();
                        $cid = $habukhan->active_company_id;
                        $data = [
                            'bill' => $request->bill,
                            'bill_min' => $request->bill_min,
                            'bill_max' => $request->bill_max,
                            'direct' => $request->direct ? 1 : 0
                        ];

                        $existing = DB::table('bill_charge')->where('company_id', $cid)->first();
                        if ($existing) {
                            DB::table('bill_charge')->where('id', $existing->id)->update($data);
                            $message = "Bill Charges Updated Successfully";
                        } else {
                            $data['company_id'] = $cid;
                            DB::table('bill_charge')->insert($data);
                            $message = "Custom Bill Charges Created for Your Company";
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => $message
                        ]);
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
    public function CashDiscount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {

                    $main_validator = validator::make($request->all(), [
                        'mtn_number' => 'required|numeric|digits:11',
                        'airtel_number' => 'required|numeric|digits:11',
                        'glo_number' => 'required|numeric|digits:11',
                        'mobile_number' => 'required|numeric|digits:11',
                        'mtn' => 'required|numeric|between:0,100',
                        'airtel' => 'required|numeric|between:0,100',
                        'glo' => 'required|numeric|between:0,100',
                        'mobile' => 'required|numeric|between:0,100'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $habukhan = $check_user->first();
                        $cid = $habukhan->active_company_id;
                        $data = [
                            'mtn' => $request->mtn,
                            'glo' => $request->glo,
                            'airtel' => $request->airtel,
                            'mobile' => $request->mobile,
                            'mtn_number' => $request->mtn_number,
                            'glo_number' => $request->glo_number,
                            'airtel_number' => $request->airtel_number,
                            'mobile_number' => $request->mobile_number,
                        ];

                        $existing = DB::table('cash_discount')->where('company_id', $cid)->first();
                        if ($existing) {
                            DB::table('cash_discount')->where('id', $existing->id)->update($data);
                            $message = "Airtime to Cash Settings Updated Successfully";
                        } else {
                            $data['company_id'] = $cid;
                            DB::table('cash_discount')->insert($data);
                            $message = "Custom Airtime to Cash Settings Created for Your Company";
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => $message
                        ]);
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
    public function ResultCharge(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {

                    $main_validator = validator::make($request->all(), [
                        'waec' => 'required|numeric',
                        'neco' => 'required|numeric',
                        'nabteb' => 'required|numeric',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $habukhan = $check_user->first();
                        $cid = $habukhan->active_company_id;
                        $data = [
                            'waec' => $request->waec,
                            'neco' => $request->neco,
                            'nabteb' => $request->nabteb,
                        ];

                        $existing = DB::table('result_charge')->where('company_id', $cid)->first();
                        if ($existing) {
                            DB::table('result_charge')->where('id', $existing->id)->update($data);
                            $message = "Result Charges Updated Successfully";
                        } else {
                            $data['company_id'] = $cid;
                            DB::table('result_charge')->insert($data);
                            $message = "Custom Result Charges Created for Your Company";
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => $message
                        ]);
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
    public function OtherCharge(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {

                    $main_validator = validator::make($request->all(), [
                        // General charges removed per user request
                        // Card Settings Validation
                        'vcard_ngn_fee' => 'nullable|numeric',
                        'vcard_usd_fee' => 'nullable|numeric',
                        'vcard_usd_rate' => 'nullable|numeric',
                        'vcard_fund_fee' => 'nullable|numeric',
                        'vcard_usd_failed_fee' => 'nullable|numeric',
                        'vcard_ngn_fund_fee' => 'nullable|numeric',
                        'vcard_usd_fund_fee' => 'nullable|numeric',
                        'vcard_ngn_failed_fee' => 'nullable|numeric',
                        // Service Charges Validation
                        'kyc_charges' => 'nullable|array',
                        'kyc_charges.*.value' => 'nullable|numeric',
                        'kyc_charges.*.cap' => 'nullable|numeric',
                        'palmpay_charge' => 'nullable|array',
                        'palmpay_charge.value' => 'nullable|numeric',
                        'palmpay_charge.cap' => 'nullable|numeric',
                        'palmpay_charge.type' => 'nullable|string|in:PERCENT,FIXED',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $habukhan = $check_user->first();
                        $cid = $habukhan->active_company_id;

                        $data = [
                            // General charges removed per user request to avoid SQL errors and remove features
                        ];
                        if (!empty($data)) {
                            DB::table('settings')->where('company_id', $cid)->update($data);
                        }

                        // Update Card Settings
                        $cardData = [];
                        if ($request->has('vcard_ngn_fee'))
                            $cardData['ngn_creation_fee'] = $request->vcard_ngn_fee;
                        if ($request->has('vcard_usd_fee'))
                            $cardData['usd_creation_fee'] = $request->vcard_usd_fee;
                        if ($request->has('vcard_usd_rate'))
                            $cardData['ngn_rate'] = $request->vcard_usd_rate;
                        if ($request->has('vcard_fund_fee'))
                            $cardData['funding_fee_percent'] = $request->vcard_fund_fee;
                        if ($request->has('vcard_usd_failed_fee'))
                            $cardData['usd_failed_tx_fee'] = $request->vcard_usd_failed_fee;
                        if ($request->has('vcard_ngn_fund_fee'))
                            $cardData['ngn_funding_fee_percent'] = $request->vcard_ngn_fund_fee;
                        if ($request->has('vcard_usd_fund_fee'))
                            $cardData['usd_funding_fee_percent'] = $request->vcard_usd_fund_fee;
                        if ($request->has('vcard_ngn_failed_fee'))
                            $cardData['ngn_failed_tx_fee'] = $request->vcard_ngn_failed_fee;

                        if (!empty($cardData)) {
                            DB::table('card_settings')->updateOrInsert(['company_id' => $cid], $cardData);
                        }

                        // Update Service Charges (KYC)
                        if ($request->has('kyc_charges')) {
                            foreach ($request->kyc_charges as $serviceName => $val) {
                                $chargeValue = $val['value'] ?? 0;
                                $chargeCap = isset($val['cap']) ? $val['cap'] : null;

                                $existing = DB::table('service_charges')
                                    ->where('company_id', $cid)
                                    ->where('service_category', 'kyc')
                                    ->where('service_name', $serviceName)
                                    ->first();

                                if ($existing) {
                                    DB::table('service_charges')->where('id', $existing->id)->update([
                                        'charge_value' => $chargeValue,
                                        'charge_cap' => $chargeCap
                                    ]);
                                } else {
                                    // Copy from global default if first time
                                    $global = DB::table('service_charges')
                                        ->where('company_id', 1)
                                        ->where('service_category', 'kyc')
                                        ->where('service_name', $serviceName)
                                        ->first();

                                    DB::table('service_charges')->insert([
                                        'company_id' => $cid,
                                        'service_category' => 'kyc',
                                        'service_name' => $serviceName,
                                        'service_id' => $global->service_id ?? 0,
                                        'charge_value' => $chargeValue,
                                        'charge_cap' => $chargeCap,
                                        'charge_type' => $global->charge_type ?? 'FIXED',
                                        'status' => $global->status ?? 1
                                    ]);
                                }
                            }
                        }

                        // Update PalmPay VA Charge
                        if ($request->has('palmpay_charge')) {
                            $pCharge = $request->palmpay_charge;
                            $existing = DB::table('service_charges')
                                ->where('company_id', $cid)
                                ->where('service_category', 'payment')
                                ->where('service_name', 'palmpay_va')
                                ->first();

                            if ($existing) {
                                DB::table('service_charges')->where('id', $existing->id)->update([
                                    'charge_value' => $pCharge['value'] ?? 0,
                                    'charge_cap' => isset($pCharge['cap']) ? $pCharge['cap'] : null,
                                    'charge_type' => $pCharge['type'] ?? 'PERCENT'
                                ]);
                            } else {
                                // Copy from global default
                                $global = DB::table('service_charges')
                                    ->where('company_id', 1)
                                    ->where('service_category', 'payment')
                                    ->where('service_name', 'palmpay_va')
                                    ->first();

                                DB::table('service_charges')->insert([
                                    'company_id' => $cid,
                                    'service_category' => 'payment',
                                    'service_name' => 'palmpay_va',
                                    'service_id' => $global->service_id ?? 0,
                                    'charge_value' => $pCharge['value'] ?? 0,
                                    'charge_cap' => isset($pCharge['cap']) ? $pCharge['cap'] : null,
                                    'charge_type' => $pCharge['type'] ?? 'PERCENT',
                                    'status' => $global->status ?? 1
                                ]);
                            }
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Successfully'
                        ]);
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
    public function RechargeCardSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn' => 'required',
                        'airtel' => 'required',
                        'glo' => 'required',
                        'mobile' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }

                    $habukhan = $check_user->first();
                    $cid = $habukhan->active_company_id;
                    $data = [
                        'mtn' => $request->mtn,
                        'airtel' => $request->airtel,
                        'glo' => $request->glo,
                        'mobile' => $request->mobile,
                    ];

                    $existing = DB::table('recharge_card_sel')->where('company_id', $cid)->first();
                    if ($existing) {
                        DB::table('recharge_card_sel')->where('id', $existing->id)->update($data);
                    } else {
                        $data['company_id'] = $cid;
                        DB::table('recharge_card_sel')->insert($data);
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Updated Success'
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
    public function DataCardSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn' => 'required',
                        'airtel' => 'required',
                        'glo' => 'required',
                        'mobile' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }

                    $habukhan = $check_user->first();
                    $cid = $habukhan->active_company_id;
                    $data = [
                        'mtn' => $request->mtn,
                        'airtel' => $request->airtel,
                        'glo' => $request->glo,
                        'mobile' => $request->mobile,
                    ];

                    $existing = DB::table('data_card_sel')->where('company_id', $cid)->first();
                    if ($existing) {
                        DB::table('data_card_sel')->where('id', $existing->id)->update($data);
                    } else {
                        $data['company_id'] = $cid;
                        DB::table('data_card_sel')->insert($data);
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Updated Success'
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
    public function DataSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn_sme' => 'required',
                        'airtel_sme' => 'required',
                        'glo_sme' => 'required',
                        'mobile_sme' => 'required',
                        'mtn_cg' => 'required',
                        'airtel_cg' => 'required',
                        'glo_cg' => 'required',
                        'mobile_cg' => 'required',
                        'mtn_g' => 'required',
                        'airtel_g' => 'required',
                        'glo_g' => 'required',
                        'mobile_g' => 'required',
                        'mtn_sme2' => 'sometimes|required',
                        'airtel_sme2' => 'sometimes|required',
                        'glo_sme2' => 'sometimes|required',
                        'mobile_sme2' => 'sometimes|required',
                        'mtn_datashare' => 'sometimes|required',
                        'airtel_datashare' => 'sometimes|required',
                        'glo_datashare' => 'sometimes|required',
                        'mobile_datashare' => 'sometimes|required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'mtn_sme' => $request->mtn_sme,
                            'airtel_sme' => $request->airtel_sme,
                            'glo_sme' => $request->glo_sme,
                            'mobile_sme' => $request->mobile_sme,

                            'mtn_cg' => $request->mtn_cg,
                            'airtel_cg' => $request->airtel_cg,
                            'glo_cg' => $request->glo_cg,
                            'mobile_cg' => $request->mobile_cg,

                            'mtn_g' => $request->mtn_g,
                            'airtel_g' => $request->airtel_g,
                            'glo_g' => $request->glo_g,
                            'mobile_g' => $request->mobile_g,
                            'mtn_sme2' => $request->mtn_sme2,
                            'airtel_sme2' => $request->airtel_sme2,
                            'glo_sme2' => $request->glo_sme2,
                            'mobile_sme2' => $request->mobile_sme2,
                            'mtn_datashare' => $request->mtn_datashare,
                            'airtel_datashare' => $request->airtel_datashare,
                            'glo_datashare' => $request->glo_datashare,
                            'mobile_datashare' => $request->mobile_datashare
                        ];

                        // Safe filter: remove columns that don't exist in the database
                        $safe_data = [];
                        foreach ($data as $key => $value) {
                            if (Schema::hasColumn('data_sel', $key)) {
                                $safe_data[$key] = $value;
                            }
                        }

                        DB::table('data_sel')->update($safe_data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
    public function AirtimeSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn_vtu' => 'required',
                        'airtel_vtu' => 'required',
                        'glo_vtu' => 'required',
                        'mobile_vtu' => 'required',
                        'mtn_share' => 'required',
                        'airtel_share' => 'required',
                        'glo_share' => 'required',
                        'mobile_share' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'mtn_vtu' => $request->mtn_vtu,
                            'airtel_vtu' => $request->airtel_vtu,
                            'glo_vtu' => $request->glo_vtu,
                            'mobile_vtu' => $request->mobile_vtu,

                            'mtn_share' => $request->mtn_share,
                            'airtel_share' => $request->airtel_share,
                            'glo_share' => $request->glo_share,
                            'mobile_share' => $request->mobile_share,
                        ];
                        DB::table('airtime_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
    public function CashSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn' => 'required',
                        'airtel' => 'required',
                        'glo' => 'required',
                        'mobile' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'mtn' => $request->mtn,
                            'airtel' => $request->airtel,
                            'glo' => $request->glo,
                            'mobile' => $request->mobile,
                        ];
                        DB::table('cash_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
    public function CableSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'dstv' => 'required',
                        'startime' => 'required',
                        'gotv' => 'required',
                        'showmax' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'startime' => $request->startime,
                            'gotv' => $request->gotv,
                            'dstv' => $request->dstv,
                            'showmax' => $request->showmax,
                        ];
                        DB::table('cable_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
    public function BillSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'bill' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'bill' => $request->bill,
                        ];
                        DB::table('bill_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
    public function BulkSMSsel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'bulksms' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'bulksms' => $request->bulksms,
                        ];
                        DB::table('bulksms_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
    public function ExamSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'waec' => 'required',
                        'neco' => 'required',
                        'nabteb' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'waec' => $request->waec,
                            'neco' => $request->neco,
                            'nabteb' => $request->nabteb,
                        ];
                        DB::table('exam_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
    public function BankTransferSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'bank_transfer' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $data = [
                            'bank_transfer' => $request->bank_transfer,
                        ];
                        DB::table('bank_transfer_sel')->update($data);
                        DB::table('settings')->update(['primary_transfer_provider' => $request->bank_transfer]);
                        // FIX: Auto-unlock the selected provider
                        // DB::table('transfer_providers')->where('slug', $request->bank_transfer)->update(['is_locked' => 0]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
                    }
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
            return redirect(config('app.error_500'));
        }
    }
    public function AllUsersInfo(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $status = $request->status;
                    if ($status != 'ALL') {
                        if ($status == 0)
                            $status = 'unverified';
                        else if ($status == 1)
                            $status = 'active';
                        else if ($status == 2)
                            $status = 'banned';
                        else if ($status == 3)
                            $status = 'deactivated';
                    }

                    if ($request->role == 'ALL' && $status == 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id', 'companies.kyc_status as business_kyc_status', 'users.kyc_status as user_kyc_status')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else if ($request->role != 'ALL' && $status == 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->where(['users.type' => $request->role])
                                ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id', 'companies.kyc_status as business_kyc_status', 'users.kyc_status as user_kyc_status')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else if ($request->role == 'ALL' && $status != 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->where(['users.status' => $status])
                                ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id', 'companies.kyc_status as business_kyc_status', 'users.kyc_status as user_kyc_status')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else if ($request->role != 'ALL' && $status != 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->where(['users.status' => $status, 'users.type' => $request->role])
                                ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id', 'companies.kyc_status as business_kyc_status', 'users.kyc_status as user_kyc_status')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else if ($request->role == 'ALL' && $status == 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->where(function ($query) use ($search) {
                                    $query->orWhere('users.username', 'LIKE', "%$search%")->orWhere('users.name', 'LIKE', "%$search%")->orWhere('users.email', 'LIKE', "%$search%")->orWhere('users.date', 'LIKE', "%$search%")->orWhere('users.phone', 'LIKE', "%$search%")->orWhere('users.pin', 'LIKE', "%$search%")->orWhere('users.type', 'LIKE', "%$search%")->orWhere('companies.name', 'LIKE', "%$search%");
                                })->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id', 'companies.kyc_status as business_kyc_status', 'users.kyc_status as user_kyc_status')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else if ($request->role != 'ALL' && $status == 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->where(['users.type' => $request->role])->where(function ($query) use ($search) {
                                    $query->orWhere('users.username', 'LIKE', "%$search%")->orWhere('users.name', 'LIKE', "%$search%")->orWhere('users.email', 'LIKE', "%$search%")->orWhere('users.date', 'LIKE', "%$search%")->orWhere('users.phone', 'LIKE', "%$search%")->orWhere('users.pin', 'LIKE', "%$search%")->orWhere('users.type', 'LIKE', "%$search%")->orWhere('companies.name', 'LIKE', "%$search%");
                                })->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id', 'companies.kyc_status as business_kyc_status', 'users.kyc_status as user_kyc_status')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else if ($request->role == 'ALL' && $status != 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->where(['users.status' => $status])->where(function ($query) use ($search) {
                                    $query->orWhere('users.username', 'LIKE', "%$search%")->orWhere('users.name', 'LIKE', "%$search%")->orWhere('users.email', 'LIKE', "%$search%")->orWhere('users.date', 'LIKE', "%$search%")->orWhere('users.phone', 'LIKE', "%$search%")->orWhere('users.pin', 'LIKE', "%$search%")->orWhere('users.type', 'LIKE', "%$search%")->orWhere('companies.name', 'LIKE', "%$search%");
                                })->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else if ($request->role != 'ALL' && $status != 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->where(['users.status' => $status, 'users.type' => $request->role])->where(function ($query) use ($search) {
                                    $query->orWhere('users.username', 'LIKE', "%$search%")->orWhere('users.name', 'LIKE', "%$search%")->orWhere('users.email', 'LIKE', "%$search%")->orWhere('users.date', 'LIKE', "%$search%")->orWhere('users.phone', 'LIKE', "%$search%")->orWhere('users.pin', 'LIKE', "%$search%")->orWhere('users.type', 'LIKE', "%$search%")->orWhere('companies.name', 'LIKE', "%$search%");
                                })->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
                    } else {
                        return response()->json([
                            'all_users' => DB::table('users')
                                ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                                ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.pin', 'users.phone', 'users.balance', 'users.referral_balance', 'users.kyc', 'users.status', 'users.type', 'users.profile_image', 'users.date', 'companies.name as business_name', 'companies.is_active as business_active', 'companies.id as company_id')
                                ->orderBy('users.id', 'desc')->paginate($request->input('perPage', 15)),
                        ]);
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
    public function AllBankDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    if (!empty($search)) {
                        return response()->json([
                            'autobank' => DB::table('users')
                                ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
                                ->where('users.autofund', 'ACTIVE')
                                ->select('users.id', 'users.username', 'users.profile_image', 'users.wema', 'users.kolomoni_mfb', 'users.sterlen', 'users.fed', 'users.balance', 'users.referral_balance', 'users.status', 'companies.business_name')
                                ->orderBy('users.id', 'desc')
                                ->where(function ($query) use ($search) {
                                    $query->orWhere('users.username', 'LIKE', "%$search%")
                                        ->orWhere('users.name', 'LIKE', "%$search%")
                                        ->orWhere('companies.business_name', 'LIKE', "%$search%")
                                        ->orWhere('users.email', 'LIKE', "%$search%")
                                        ->orWhere('users.phone', 'LIKE', "%$search%");
                                })->paginate($request->adex),
                        ]);
                    } else {
                        return response()->json([
                            'autobank' => DB::table('users')
                                ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
                                ->where('users.autofund', 'ACTIVE')
                                ->select('users.id', 'users.username', 'users.profile_image', 'users.wema', 'users.kolomoni_mfb', 'users.sterlen', 'users.fed', 'users.balance', 'users.referral_balance', 'users.status', 'companies.business_name')
                                ->orderBy('users.id', 'desc')
                                ->paginate($request->adex),
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
            }
        } else {
            return redirect(config('app.error_500'));
        }
    }

    /**
     * Get all virtual accounts with advanced filtering
     */
    public function getVirtualAccounts(Request $request)
    {
        if (!$this->verifyOrigin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized origin'], 403);
        }

        $userId = $this->verifytoken($request->id);
        if (!$userId || !DB::table('users')->where(['id' => $userId, 'type' => 'ADMIN'])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $query = DB::table('virtual_accounts')
            ->leftJoin('companies', 'virtual_accounts.company_id', '=', 'companies.id')
            ->leftJoin('users', 'virtual_accounts.user_id', '=', 'users.id')
            ->select(
                'virtual_accounts.id',
                'virtual_accounts.uuid',
                DB::raw('COALESCE(virtual_accounts.account_number, virtual_accounts.palmpay_account_number) as account_number'),
                DB::raw('COALESCE(virtual_accounts.account_name, virtual_accounts.palmpay_account_name) as account_name'),
                'virtual_accounts.account_type',
                'virtual_accounts.status',
                'virtual_accounts.customer_email',
                'virtual_accounts.customer_name',
                'virtual_accounts.bvn',
                'virtual_accounts.provider',
                'virtual_accounts.created_at',
                'companies.name as company_name',
                'users.username as platform_username'
            );

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('virtual_accounts.account_number', 'LIKE', "%{$search}%")
                    ->orWhere('virtual_accounts.account_name', 'LIKE', "%{$search}%")
                    ->orWhere('virtual_accounts.customer_name', 'LIKE', "%{$search}%")
                    ->orWhere('virtual_accounts.customer_email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('provider')) {
            $query->where('virtual_accounts.provider', $request->provider);
        }

        if ($request->filled('status')) {
            $query->where('virtual_accounts.status', $request->status);
        }

        if ($request->filled('startDate')) {
            $query->whereDate('virtual_accounts.created_at', '>=', $request->startDate);
        }

        if ($request->filled('endDate')) {
            $query->whereDate('virtual_accounts.created_at', '<=', $request->endDate);
        }

        $virtualAccounts = $query->orderBy('virtual_accounts.created_at', 'desc')
            ->paginate($request->input('perPage', 10));

        return response()->json([
            'status' => 'success',
            'data' => $virtualAccounts
        ]);
    }

    /**
     * Toggle status of a specific virtual account
     */
    public function toggleVirtualAccountStatus(Request $request)
    {
        if (!$this->verifyOrigin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized origin'], 403);
        }

        $userId = $this->verifytoken($request->id);
        if (!$userId || !DB::table('users')->where(['id' => $userId, 'type' => 'ADMIN'])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'uuid' => 'required',
            'status' => 'required|in:active,disabled'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $updated = DB::table('virtual_accounts')
            ->where('uuid', $request->uuid)
            ->update([
                'status' => $request->status,
                'updated_at' => now()
            ]);

        if ($updated) {
            return response()->json([
                'status' => 'success',
                'message' => 'Virtual account status updated to ' . $request->status
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Virtual account not found or status unchanged'], 404);
    }

    public function updateCompanyProfile(Request $request)
    {
        if (!$this->verifyOrigin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized origin'], 403);
        }

        $userId = $this->verifytoken($request->id);
        if (!$userId || !DB::table('users')->where(['id' => $userId, 'type' => 'ADMIN'])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'business_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'password' => 'sometimes|nullable|string|min:8',
            'pin' => 'sometimes|nullable|numeric|digits:4',
            'rc_number' => 'sometimes|nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $company = DB::table('companies')->where('id', $request->company_id)->first();
        if (!$company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        // Update Company Table
        $companyData = [];
        if ($request->filled('business_name'))
            $companyData['name'] = $request->business_name;
        if ($request->filled('email'))
            $companyData['email'] = $request->email;
        if ($request->filled('phone'))
            $companyData['phone'] = $request->phone;
        if ($request->filled('rc_number'))
            $companyData['rc_number'] = $request->rc_number;

        if (!empty($companyData)) {
            DB::table('companies')->where('id', $request->company_id)->update($companyData);
        }

        // Update User Table
        $userData = [];
        if ($request->filled('business_name'))
            $userData['business_name'] = $request->business_name;
        if ($request->filled('email'))
            $userData['email'] = $request->email;
        if ($request->filled('phone'))
            $userData['phone'] = $request->phone;
        if ($request->filled('password'))
            $userData['password'] = password_hash($request->password, PASSWORD_DEFAULT, ['cost' => 16]);
        if ($request->filled('pin'))
            $userData['pin'] = $request->pin;

        if (!empty($userData)) {
            DB::table('users')->where('id', $company->user_id)->update($userData);
        }

        return response()->json(['status' => 'success', 'message' => 'Profile updated successfully']);
    }

    public function toggleCompanyStatus(Request $request)
    {
        if (!$this->verifyOrigin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized origin'], 403);
        }

        $userId = $this->verifytoken($request->id);
        if (!$userId || !DB::table('users')->where(['id' => $userId, 'type' => 'ADMIN'])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'status' => 'required|in:active,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $company = DB::table('companies')->where('id', $request->company_id)->first();
        if (!$company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        // Update User Status (which controls login)
        DB::table('users')->where('id', $company->user_id)->update([
            'status' => $request->status,
            'updated_at' => now()
        ]);

        // Optionally update company status too (for consistency)
        DB::table('companies')->where('id', $request->company_id)->update([
            'status' => $request->status == 'active' ? 'active' : 'suspended',
            'is_active' => $request->status == 'active' ? 1 : 0,
            'updated_at' => now()
        ]);

        return response()->json(['status' => 'success', 'message' => 'Status updated to ' . $request->status]);
    }

    public function UserBankAccountD(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $query = DB::table('users')
                        ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
                        ->whereNotNull('users.palmpay_account_number')
                        ->select('users.username', 'users.palmpay_account_number as account_number', 'users.palmpay_bank_name as bank', 'users.name', 'users.palmpay_account_name as account_name', 'companies.business_name');

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->where('users.username', 'LIKE', "%$search%")
                                ->orWhere('users.palmpay_account_number', 'LIKE', "%$search%")
                                ->orWhere('companies.business_name', 'LIKE', "%$search%")
                                ->orWhere('users.name', 'LIKE', "%$search%");
                        });
                    }

                    return response()->json([
                        'userbank' => $query->orderBy('id', 'desc')->paginate($request->adex ?? 10)
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
    public function AllUserBanned(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    return response()->json([
                        'autobanned' => DB::table('banned_companies')
                            ->leftJoin("users", "users.username", "=", "banned_companies.added_by")
                            ->join("companies", "banned_companies.company_id", "=", "companies.id")
                            ->select('banned_companies.id', 'banned_companies.company_id', 'banned_companies.added_by', 'banned_companies.created_at as date', 'users.profile_image', 'companies.name as business_name')
                            ->orderBy('banned_companies.id', 'desc')
                            ->get(),
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
    public function AllSystemPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });

                if ($check_user->count() > 0) {
                    $habukhan = $check_user->first();
                    $cid = $habukhan->active_company_id;

                    return response()->json([
                        'data_plans' => DB::table('data_plan')
                            ->whereIn('company_id', [$cid, 1])
                            ->leftJoin("users", "users.username", "=", "data_plan.added_by")
                            ->orderBy('data_plan.id', 'desc')->get(),
                        'cable_plans' => DB::table('cable_plan')
                            ->whereIn('company_id', [$cid, 1])
                            ->leftJoin("users", "users.username", "=", "cable_plan.added_by")
                            ->orderBy('cable_plan.id', 'desc')->get(),
                        'bill_plans' => DB::table('bill_plan')
                            ->whereIn('company_id', [$cid, 1])
                            ->leftJoin("users", "users.username", "=", "bill_plan.added_by")
                            ->orderBy('bill_plan.id', 'desc')->get(),
                        'result_plans' => DB::table('stock_result_pin')
                            ->leftJoin("users", "users.username", "=", "stock_result_pin.added_by")
                            ->orderBy('stock_result_pin.id', 'desc')->get(),
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

    public function ApiBalance(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $other_api = DB::table('other_api')->first();
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $other_api->habukhan_website1 . "/api/user/");
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt(
                        $ch,
                        CURLOPT_HTTPHEADER,
                        [
                            "Authorization: Basic " . base64_encode($other_api->habukhan1_username . ":" . $other_api->habukhan1_password),
                        ]
                    );
                    $json = curl_exec($ch);
                    curl_close($ch);
                    $decode_habukhan = json_decode($json, true);
                    if (isset($decode_habukhan)) {
                        if (isset($decode_habukhan['status'])) {
                            if ($decode_habukhan['status'] == 'success') {
                                $admin_balance = '₦' . $decode_habukhan['balance'];
                            } else {
                                $admin_balance = 'API NOT CONNECTED';
                            }
                        } else {
                            $admin_balance = 'API NOT CONNECTED';
                        }
                    } else {
                        $admin_balance = 'API NOT CONNECTED';
                    }
                    return response()->json([
                        'status' => 'success',
                        'admin_url' => $other_api->habukhan_website1,
                        'balance' => $admin_balance
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

    public function lockVirtualAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });

                if ($check_user->count() > 0) {
                    $validator = Validator::make($request->all(), [
                        'provider' => 'required|in:palmpay,monnify,wema,xixapay',
                        'enabled' => 'required|boolean'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $validator->errors()->first()
                        ], 400);
                    }

                    $provider = $request->provider;
                    $enabled = $request->enabled;

                    // Check if trying to disable all providers
                    if (!$enabled) {
                        $settings = DB::table('settings')->first();
                        $enabledCount = 0;
                        if ($settings->palmpay_enabled)
                            $enabledCount++;
                        if ($settings->monnify_enabled)
                            $enabledCount++;
                        if ($settings->wema_enabled)
                            $enabledCount++;
                        if ($settings->xixapay_enabled)
                            $enabledCount++;

                        if ($enabledCount <= 1) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Cannot disable all providers. At least one must remain enabled.'
                            ], 400);
                        }
                    }

                    // Update the provider status
                    $column = $provider . '_enabled';
                    DB::table('settings')->update([$column => $enabled]);

                    return response()->json([
                        'status' => 'success',
                        'message' => ucfirst($provider) . ' has been ' . ($enabled ? 'enabled' : 'disabled')
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unauthorized'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function setDefaultVirtualAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });

                if ($check_user->count() > 0) {
                    $validator = Validator::make($request->all(), [
                        'default_provider' => 'required|in:palmpay,monnify,wema,xixapay'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $validator->errors()->first()
                        ], 400);
                    }

                    $provider = $request->default_provider;

                    // Check if the provider is enabled
                    $settings = DB::table('settings')->first();
                    $column = $provider . '_enabled';

                    if (!$settings->$column) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Cannot set a disabled provider as default. Please enable it first.'
                        ], 400);
                    }

                    // Update the default provider
                    DB::table('settings')->update(['default_virtual_account' => $provider]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Default provider set to ' . ucfirst($provider)
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unauthorized'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    // SMART TRANSFER ROUTER METHODS

    public function getTransferSettings(Request $request, $id = null)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $token = $id ?: $request->id;
            $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($token)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0) {
                $habukhan = $check_user->first();
                $cid = $habukhan->active_company_id;
                $settings = DB::table('settings')->where('company_id', $cid)->select('transfer_lock_all', 'transfer_charge_type', 'transfer_charge_value', 'transfer_charge_cap')->first();
                if (!$settings) {
                    $settings = DB::table('settings')->where('company_id', 1)->select('transfer_lock_all', 'transfer_charge_type', 'transfer_charge_value', 'transfer_charge_cap')->first();
                }
                // $providers = DB::table('transfer_providers')->orderBy('priority', 'asc')->get();
                $providers = [
                    (object) ['name' => 'PalmPay', 'slug' => 'palmpay', 'priority' => 1, 'is_locked' => 0]
                ];

                return response()->json([
                    'status' => 'success',
                    'providers' => $providers,
                    'settings' => $settings
                ]);
            } else {
                \Log::error('Check User Failed', ['id' => $token, 'verified' => $this->verifytoken($token)]);
                return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Invalid Origin'])->setStatusCode(403);
    }

    public function lockTransferProvider(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0) {
                // $request->slug (e.g., 'paystack'), $request->action ('lock' or 'unlock')

                // FIX: Prevent locking the primary provider
                if ($request->action == 'lock') {
                    $currentPrimary = DB::table('settings')->value('primary_transfer_provider');
                    if ($currentPrimary === $request->slug) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Cannot lock the active Primary Provider (' . ucfirst($currentPrimary) . '). Please select a different primary provider first.'
                        ]);
                    }
                }

                $is_locked = ($request->action == 'lock') ? 1 : 0;
                // Simulation: The transfer_providers table has been removed.
                // Logic updated to use settings table or PalmPay directly in the future.

                return response()->json([
                    'status' => 'success',
                    'message' => ucfirst($request->slug) . ' status updated (Simulation)'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => ucfirst($request->slug) . ' has been ' . ($is_locked ? 'Locked' : 'Unlocked')
                ]);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function setTransferPriority(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0 && is_array($request->priorities)) {
                // Expects array like: [['slug' => 'xixapay', 'priority' => 1], ['slug' => 'monnify', 'priority' => 2]]
                /*
                foreach ($request->priorities as $p) {
                    // DB::table('transfer_providers')->where('slug', $p['slug'])->update(['priority' => $p['priority']]);
                }
                */
                return response()->json(['status' => 'success', 'message' => 'Priorities Updated']);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function updateBankCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('users')
                ->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])
                ->where(['type' => 'ADMIN']);

            if ($check_user->count() > 0) {
                $updateData = [];

                // Pay with Transfer (Funding with Bank Transfer)
                if ($request->has('transfer_type')) {
                    $updateData['transfer_charge_type'] = $request->transfer_type;
                    $updateData['transfer_charge_value'] = $request->transfer_value ?? 0;
                    $updateData['transfer_charge_cap'] = $request->transfer_cap ?? 0;
                }

                // Pay with Wallet (Internal Transfer)
                if ($request->has('wallet_type')) {
                    $updateData['wallet_charge_type'] = $request->wallet_type;
                    $updateData['wallet_charge_value'] = $request->wallet_value ?? 0;
                    $updateData['wallet_charge_cap'] = $request->wallet_cap ?? 0;
                }

                // Payout to Bank (External Transfer - Other Banks)
                if ($request->has('payout_bank_type')) {
                    $updateData['payout_bank_charge_type'] = $request->payout_bank_type;
                    $updateData['payout_bank_charge_value'] = $request->payout_bank_value ?? 0;
                    $updateData['payout_bank_charge_cap'] = $request->payout_bank_cap ?? 0;
                }

                // Payout to PalmPay (Settlement Withdrawal)
                if ($request->has('payout_palmpay_type')) {
                    $updateData['payout_palmpay_charge_type'] = $request->payout_palmpay_type;
                    $updateData['payout_palmpay_charge_value'] = $request->payout_palmpay_value ?? 0;
                    $updateData['payout_palmpay_charge_cap'] = $request->payout_palmpay_cap ?? 0;
                }

                // Settlement Rules
                if ($request->has('auto_settlement_enabled')) {
                    $updateData['auto_settlement_enabled'] = $request->auto_settlement_enabled ? 1 : 0;
                }
                if ($request->has('settlement_delay_hours')) {
                    // Allow decimal hours (e.g., 0.0167 for 1 minute, 0.5 for 30 minutes)
                    $updateData['settlement_delay_hours'] = max(0.0167, min(168, (float) $request->settlement_delay_hours));
                }
                if ($request->has('settlement_skip_weekends')) {
                    $updateData['settlement_skip_weekends'] = $request->settlement_skip_weekends ? 1 : 0;
                }
                if ($request->has('settlement_skip_holidays')) {
                    $updateData['settlement_skip_holidays'] = $request->settlement_skip_holidays ? 1 : 0;
                }
                if ($request->has('settlement_time')) {
                    $updateData['settlement_time'] = $request->settlement_time;
                }
                if ($request->has('settlement_minimum_amount')) {
                    $updateData['settlement_minimum_amount'] = max(0, (float) $request->settlement_minimum_amount);
                }

                if (!empty($updateData)) {
                    // Update global settings (company_id = 1 or NULL)
                    $settings = DB::table('settings')->first();
                    if ($settings) {
                        DB::table('settings')->where('id', $settings->id)->update($updateData);
                    } else {
                        // Create if doesn't exist
                        $updateData['company_id'] = 1;
                        DB::table('settings')->insert($updateData);
                    }

                    return response()->json(['status' => 'success', 'message' => 'Charges & Settlement Rules Updated']);
                }

                return response()->json(['status' => 403, 'message' => 'No data to update'])->setStatusCode(403);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function updateServiceCharge(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('users')
                ->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])
                ->where(['type' => 'ADMIN']);

            if ($check_user->count() > 0) {
                // Handle bulk update for PalmPay VA and KYC charges
                if ($request->has('palmpay_charge')) {
                    $palmpay = $request->palmpay_charge;
                    DB::table('service_charges')
                        ->where('service_name', 'palmpay_va')
                        ->where('company_id', 1)
                        ->update([
                            'charge_type' => $palmpay['type'] ?? 'PERCENT',
                            'charge_value' => $palmpay['value'] ?? 0,
                            'charge_cap' => $palmpay['cap'] ?? null,
                            'updated_at' => now(),
                        ]);
                }

                // Handle KYC charges bulk update
                if ($request->has('kyc_charges')) {
                    foreach ($request->kyc_charges as $serviceName => $chargeData) {
                        if (isset($chargeData['value'])) {
                            DB::table('service_charges')
                                ->where('service_name', $serviceName)
                                ->where('service_category', 'kyc')
                                ->where('company_id', 1)
                                ->update([
                                    'charge_value' => $chargeData['value'],
                                    'charge_cap' => $chargeData['cap'] ?? null,
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                }

                // Handle single charge update (legacy)
                if ($request->has('charge_id')) {
                    $updateData = [];

                    if ($request->has('type')) {
                        $updateData['charge_type'] = $request->type;
                    }
                    if ($request->has('value')) {
                        $updateData['charge_value'] = $request->value;
                    }
                    if ($request->has('cap')) {
                        $updateData['charge_cap'] = $request->cap;
                    }
                    if ($request->has('is_active')) {
                        $updateData['is_active'] = $request->is_active;
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_at'] = now();
                        DB::table('service_charges')
                            ->where('id', $request->charge_id)
                            ->update($updateData);
                    }
                }

                return response()->json(['status' => 'success', 'message' => 'Service Charges Updated']);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function toggleGlobalTransferLock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0) {
                $habukhan = $check_user->first();
                $cid = $habukhan->active_company_id;
                $lock = ($request->action == 'lock') ? 1 : 0;
                DB::table('settings')->where('company_id', $cid)->update(['transfer_lock_all' => $lock]);
                return response()->json(['status' => 'success', 'message' => 'Transfer Lock ' . ($lock ? 'Enabled' : 'Disabled')]);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
    public function RejectUserKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $admin = $check_user->first();
                    $user_id = $request->user_id;
                    $reason = $request->reason ?? 'Documents Rejected by Admin';
                    $corrections = $request->corrections; // Expecting ['business_info' => 'bad name']
                    $approvals = $request->approvals; // Expecting ['account_info', 'bvn_info']

                    if (!$user_id) {
                        return response()->json(['status' => 400, 'message' => 'User ID is required']);
                    }

                    // Handle Granular Feedback for Companies
                    $company = DB::table('companies')->where('user_id', $user_id)->first();
                    if ($company) {
                        // Handle Rejections
                        if (is_array($corrections)) {
                            foreach ($corrections as $section => $note) {
                                if (!empty($note)) {
                                    DB::table('company_kyc_approvals')->updateOrInsert(
                                        ['company_id' => $company->id, 'section' => $section],
                                        [
                                            'status' => 'rejected',
                                            'rejection_reason' => $note,
                                            'reviewed_by' => $admin->id,
                                            'reviewed_at' => now(),
                                            'updated_at' => now()
                                        ]
                                    );

                                    // Rule #2: Log specifically into company_documents for audit
                                    DB::table('company_documents')->insert([
                                        'company_id' => $company->id,
                                        'document_type' => $section,
                                        'file_path' => 'N/A', // Handled by activateBusiness, here we log the review
                                        'status' => 'rejected',
                                        'rejection_reason' => $note,
                                        'reviewed_by' => $admin->id,
                                        'reviewed_at' => now(),
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);
                                }
                            }
                        }

                        // Handle Approvals
                        if (is_array($approvals)) {
                            foreach ($approvals as $section) {
                                DB::table('company_kyc_approvals')->updateOrInsert(
                                    ['company_id' => $company->id, 'section' => $section],
                                    [
                                        'status' => 'approved',
                                        'rejection_reason' => null,
                                        'reviewed_by' => $admin->id,
                                        'reviewed_at' => now(),
                                        'updated_at' => now()
                                    ]
                                );

                                DB::table('company_documents')->insert([
                                    'company_id' => $company->id,
                                    'document_type' => $section,
                                    'file_path' => 'N/A',
                                    'status' => 'approved',
                                    'reviewed_by' => $admin->id,
                                    'reviewed_at' => now(),
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                            }
                        }

                        // Recalculate status: Fintech Logic
                        // If all 5 sections are approved -> approved
                        // Else if any rejection exists -> partial
                        // Else -> under_review
                        $allSections = ['business_info', 'account_info', 'bvn_info', 'board_members', 'documents'];
                        $approvedCount = DB::table('company_kyc_approvals')
                            ->where('company_id', $company->id)
                            ->where('status', 'approved')
                            ->whereIn('section', $allSections)
                            ->count();

                        $rejectionCount = DB::table('company_kyc_approvals')
                            ->where('company_id', $company->id)
                            ->where('status', 'rejected')
                            ->count();

                        if ($approvedCount === 5) {
                            $newStatus = 'approved';
                        } elseif ($rejectionCount > 0) {
                            $newStatus = 'partial';
                        } else {
                            $newStatus = 'under_review';
                        }

                        DB::table('companies')->where('user_id', $user_id)->update([
                            'kyc_status' => $newStatus,
                            'kyc_rejection_reason' => is_array($corrections) ? json_encode($corrections) : $reason,
                            'kyc_reviewed_at' => now(),
                            'kyc_reviewed_by' => ($newStatus === 'approved') ? $admin->id : null
                        ]);

                        // Update User table accordingly
                        $user = User::find($user_id);
                        if ($user) {
                            $user->kyc_status = $newStatus;
                            $user->kyc_rejection_reason = is_array($corrections) ? json_encode($corrections) : $reason;
                            $user->kyc_rejection_date = now();
                            if ($newStatus === 'approved') {
                                $user->kyc = '1';
                                $user->status = 'active'; // Ensure user is active upon approval
                            }
                            $user->save();
                        }
                    }

                    // Also update Legacy table if exists
                    DB::table('user_kyc')->where('user_id', $user_id)->update(['status' => 'rejected']);

                    return response()->json(['status' => 'success', 'message' => 'KYC Feedback Sent Successfully']);
                } else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unable to Authenticate'])->setStatusCode(403);
    }

    public function GetKycSectionStatuses(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_admin = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->whereIn('type', ['admin', 'ADMIN'])->first();
                if ($check_admin) {
                    $user_id = $request->user_id;
                    $company = DB::table('companies')->where('user_id', $user_id)->first();
                    if ($company) {
                        $statuses = DB::table('company_kyc_approvals')
                            ->where('company_id', $company->id)
                            ->select('section', 'status', 'rejection_reason')
                            ->get()
                            ->keyBy('section');

                        return response()->json(['status' => 'success', 'data' => $statuses]);
                    }
                    return response()->json(['status' => 'error', 'message' => 'Company not found']);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized Admin Access'], 403);
    }

    public function DeleteKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) { // Admin ID
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $target_user_id = $request->user_id;
                    if (!$target_user_id) {
                        return response()->json(['status' => 403, 'message' => 'Target User ID Required'])->setStatusCode(403);
                    }

                    DB::table('user_kyc')->where('user_id', $target_user_id)->delete();

                    DB::table('users')->where('id', $target_user_id)->update([
                        'kyc' => '0',
                        'nin' => null,
                        'bvn' => null,
                        'dob' => null,
                        'xixapay_kyc_data' => null,
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'User KYC Deleted Successfully'
                    ]);
                } else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            } else {
                return response()->json(['status' => 403, 'message' => 'Unable to Authenticate System'])->setStatusCode(403);
            }
        } else {
            return response()->json(['status' => 403, 'message' => 'Unable to Authenticate System'])->setStatusCode(403);
        }
    }

    public function UpdateDiscountOther(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    // Update Logic
                    if ($request->has('card_ngn_creation_fee')) {
                        DB::table('card_settings')->updateOrInsert(['id' => 1], ['ngn_creation_fee' => $request->card_ngn_creation_fee]);
                    }
                    if ($request->has('card_usd_creation_fee')) {
                        DB::table('card_settings')->updateOrInsert(['id' => 1], ['usd_creation_fee' => $request->card_usd_creation_fee]);
                    }
                    if ($request->has('card_ngn_rate')) {
                        DB::table('card_settings')->updateOrInsert(['id' => 1], ['ngn_rate' => $request->card_ngn_rate]); // For funding? Or generic
                    }

                    // Also handle existing 'discount' table updates if needed?
                    // The requirement is "Set Fees/FX".
                    // Since I cannot modify existing tables, I will use `card_settings` table.
                    // I need to create this table via migration first!

                    return response()->json(['status' => 'success', 'message' => 'Settings Updated']);
                } else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unable to Authenticate'])->setStatusCode(403);
    }

    public function getCardSettings(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $settings = DB::table('card_settings')->where('id', 1)->first();
                    return response()->json(['status' => 'success', 'data' => $settings]);
                } else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unable to Authenticate'])->setStatusCode(403);
    }
    public function AllUsersKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $subQuery = DB::table('users')
                        ->leftJoin('user_kyc', 'users.id', '=', 'user_kyc.user_id')
                        ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
                        ->where('users.type', '!=', 'ADMIN')
                        ->where('users.type', '!=', 'admin')
                        ->where(function ($q) {
                            $q->whereNotNull('users.kyc_submitted_at')
                                ->orWhere('users.kyc_status', '!=', 'unverified')
                                ->orWhereNotNull('user_kyc.id')
                                ->orWhereNotNull('companies.id');
                        })
                        ->select(
                            'users.id',
                            'users.id as user_id',
                            'users.username',
                            'users.name as user_real_name',
                            'users.email',
                            'users.phone',
                            'users.profile_image',
                            'users.address as user_address',
                            'users.id_card_path',
                            'users.utility_bill_path',
                            'users.type as user_type',
                            'users.business_name',
                            'users.bvn',
                            'users.nin',
                            'users.palmpay_account_number',
                            'users.palmpay_account_name',
                            'users.palmpay_bank_name',
                            'companies.kyc_documents',
                            'companies.name as business_company_name',
                            'companies.address as business_address',
                            'companies.email as business_email',
                            'companies.phone as business_phone',
                            // Dynamic Settlement Fields
                            DB::raw("COALESCE(companies.bank_name, users.palmpay_bank_name, 'N/A') as settlement_bank"),
                            DB::raw("COALESCE(companies.account_number, users.palmpay_account_number, 'N/A') as settlement_account"),
                            DB::raw("COALESCE(companies.account_name, users.palmpay_account_name, 'N/A') as settlement_name"),
                            DB::raw("COALESCE(companies.name, users.business_name, users.name) as name"),
                            // Aggregated status: Prioritize under_review/pending over others
                            DB::raw("CASE 
                            WHEN companies.kyc_status = 'under_review' OR users.kyc_status = 'under_review' OR user_kyc.status = 'pending' THEN 'under_review'
                            WHEN companies.kyc_status = 'pending' OR users.kyc_status = 'pending' THEN 'pending'
                            WHEN companies.kyc_status = 'partial' OR users.kyc_status = 'partial' THEN 'partial'
                            WHEN companies.kyc_status = 'verified' OR users.kyc_status = 'verified' OR user_kyc.status = 'verified' THEN 'verified'
                            WHEN companies.kyc_status = 'rejected' OR users.kyc_status = 'rejected' OR user_kyc.status = 'rejected' THEN 'rejected'
                            ELSE 'unverified'
                        END as status"),
                            // Aggregated submitted_at
                            DB::raw("COALESCE(companies.created_at, users.kyc_submitted_at, user_kyc.verified_at, users.created_at) as submitted_at"),
                            DB::raw("COALESCE(users.created_at) as created_at"),
                            // Merged ID Type / Number
                            DB::raw("COALESCE(user_kyc.id_type, 'Merchant Identity') as id_type"),
                            DB::raw("COALESCE(companies.business_registration_number, user_kyc.id_number, users.bvn, users.nin, 'N/A') as id_number"),
                            // Merged Data (EaseID / Verification Data)
                            DB::raw("COALESCE(user_kyc.full_response_json, users.xixapay_kyc_data, companies.verification_data) as full_response_json"),
                            DB::raw("'unified' as kyc_source"),
                            DB::raw("CONCAT('@', users.username) as display_user")
                        );

                    $finalQuery = DB::table(DB::raw("({$subQuery->toSql()}) as combined_kyc"))
                        ->mergeBindings($subQuery);

                    $search = strtolower($request->search);
                    $status = $request->status ?? 'ALL';

                    if ($status != 'ALL') {
                        if ($status === 'pending') {
                            // Merchants needing review or corrections (including partial) stay in this list
                            $finalQuery->whereIn('status', ['pending', 'under_review', 'partial', 'rejected']);
                        } else {
                            $finalQuery->where('status', '=', $status);
                        }
                    }

                    if (!empty($search)) {
                        $finalQuery->where(function ($q) use ($search) {
                            $q->orWhere('username', 'LIKE', "%$search%")
                                ->orWhere('name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('bvn', 'LIKE', "%$search%")
                                ->orWhere('nin', 'LIKE', "%$search%")
                                ->orWhere('business_company_name', 'LIKE', "%$search%");
                        });
                    }

                    $finalQuery->orderBy('submitted_at', 'desc');

                    $pagination = $finalQuery->paginate($request->input('perPage', 15));

                    return response()->json([
                        'status' => 200,
                        'kyc' => $pagination->items(),
                        'total_records' => $pagination->total(),
                        'current_page' => $pagination->currentPage(),
                        'last_page' => $pagination->lastPage()
                    ]);
                } else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function ApproveUserKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_admin = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->whereIn('type', ['admin', 'ADMIN'])->first();
                if ($check_admin) {
                    $userId = $request->user_id ?? $request->kyc_id ?? $request->target_id;

                    if (!$userId) {
                        return response()->json(['status' => 'error', 'message' => 'Missing User ID for approval']);
                    }

                    // 1. Resolve Company and User (Greedy Strategy)
                    // We look for a company associated with this ID (which could be user_id or company_id)
                    $company = DB::table('companies')->where('user_id', $userId)->orWhere('id', $userId)->first();
                    $resolvedUserId = $company ? $company->user_id : $userId;

                    // 2. Greedy Approval: Update Company if exists
                    if ($company) {
                        DB::table('companies')->where('id', $company->id)->update([
                            'kyc_status' => 'verified',
                            'is_active' => true,
                            'kyc_reviewed_at' => now(),
                            'kyc_reviewed_by' => $check_admin->id
                        ]);

                        // Synchronize all sectional statuses for 100% progress
                        $sections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];
                        foreach ($sections as $section) {
                            DB::table('company_kyc_approvals')->updateOrInsert(
                                ['company_id' => $company->id, 'section' => $section],
                                [
                                    'status' => 'approved',
                                    'reviewed_by' => $check_admin->id,
                                    'reviewed_at' => now(),
                                    'updated_at' => now()
                                ]
                            );
                        }

                        // Log history if table exists
                        if (Schema::hasTable('company_kyc_histories')) {
                            DB::table('company_kyc_histories')->insert([
                                'company_id' => $company->id,
                                'section' => 'all',
                                'status' => 'approved',
                                'reviewed_by' => $check_admin->id,
                                'notes' => 'Greedy approval activated via ' . ($source ?? 'admin'),
                                'created_at' => now()
                            ]);
                        }
                    }

                    // 3. Update User Record
                    DB::table('users')->where('id', $resolvedUserId)->update([
                        'kyc' => '1',
                        'kyc_status' => 'verified',
                        'status' => 'active'
                    ]);

                    // 4. Update Identity Table (user_kyc)
                    $kycUpdate = DB::table('user_kyc')->where('user_id', $resolvedUserId);
                    if ($kycUpdate->count() === 0 && $request->kyc_id) {
                        $kycUpdate = DB::table('user_kyc')->where('id', $request->kyc_id);
                    }

                    $kycUpdate->update([
                        'status' => 'verified',
                        'verified_at' => now()
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'KYC Approved and Business Activated Successfully',
                        'resolved_user_id' => $resolvedUserId,
                        'company_activated' => (bool) $company
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized Admin Access'])->setStatusCode(403);
    }



    public function DeleteUserKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });
                if ($check_user->count() > 0) {
                    $kyc_id = $request->kyc_id ?? $request->id_kyc ?? $request->target_id;

                    // 1. Try deleting from user_kyc (Legacy)
                    $kyc = DB::table('user_kyc')->where('id', $kyc_id)->first();
                    if ($kyc) {
                        DB::table('user_kyc')->where('id', $kyc_id)->delete();
                        // Reset user status
                        DB::table('users')->where('id', $kyc->user_id)->update(['kyc' => '0', 'kyc_status' => 'unverified']);
                        return response()->json(['status' => 'success', 'message' => 'KYC Record Deleted']);
                    }

                    // 2. Try Deleting Company (Business KYC)
                    // In the frontend, we pass row.id which is company.id for business kyc
                    $company = DB::table('companies')->where('id', $kyc_id)->first();
                    if ($company) {
                        // Soft delete or Hard delete? Let's Hard delete for "Delete" action
                        DB::table('companies')->where('id', $kyc_id)->delete();
                        return response()->json(['status' => 'success', 'message' => 'Business KYC Deleted']);
                    }

                    // 3. Try Deleting from User Table (Smart KYC)
                    // If the ID matches a user ID
                    $user = DB::table('users')->where('id', $kyc_id)->first();
                    if ($user && $user->kyc_submitted_at) {
                        DB::table('users')->where('id', $kyc_id)->update([
                            'kyc_status' => 'unverified',
                            'kyc_submitted_at' => null,
                            'id_card_path' => null,
                            'utility_bill_path' => null,
                            'kyc_documents' => null
                        ]);
                        return response()->json(['status' => 'success', 'message' => 'User KYC Reset']);
                    }

                    return response()->json(['status' => 'error', 'message' => 'Record not found']);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AllVirtualCards(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->whereIn('type', ['admin', 'ADMIN']);
                if ($check_user->count() > 0) {
                    $page = $request->query('page', 0);
                    $rowsPerPage = $request->query('rowsPerPage', 10);
                    $search = $request->query('search', '');

                    $query = DB::table('virtual_cards')
                        ->join('users', 'virtual_cards.user_id', '=', 'users.id')
                        ->select('virtual_cards.*', 'users.username', 'users.email');

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->where('users.username', 'like', "%$search%")
                                ->orWhere('virtual_cards.card_id', 'like', "%$search%")
                                ->orWhere('virtual_cards.card_pan', 'like', "%$search%");
                        });
                    }

                    $total = $query->count();
                    $cards = $query->orderBy('virtual_cards.created_at', 'desc')
                        ->offset($page * $rowsPerPage)
                        ->limit($rowsPerPage)
                        ->get();

                    return response()->json([
                        'status' => 'success',
                        'data' => $cards,
                        'total' => $total
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminTerminateCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->whereIn('type', ['admin', 'ADMIN']);
                if ($check_user->count() > 0) {
                    $cardId = $request->card_id;
                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();
                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);

                    // Provider removed
                    /*
                     $provider = new \App\Services\Banking\Providers\XixapayProvider();
                     $result = $provider->changeCardStatus($cardId, 'blocked');
                     if ($result['status'] === 'success') {
                     DB::table('virtual_cards')->where('card_id', $cardId)->update([
                     'status' => 'terminated',
                     'updated_at' => now()
                     ]);
                     return response()->json(['status' => 'success', 'message' => 'Card Terminated Successfully']);
                     }
                     return response()->json(['status' => 'error', 'message' => $result['message']], 400);
                     */
                    return response()->json(['status' => 'error', 'message' => 'Card service unavailable'], 503);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminDebitCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->whereIn('type', ['admin', 'ADMIN']);
                if ($check_user->count() > 0) {
                    $cardId = $request->card_id;
                    $amount = $request->amount;

                    if ($amount <= 0)
                        return response()->json(['status' => 'error', 'message' => 'Invalid amount'], 400);

                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();
                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);

                    // Provider removed
                    /*
                     $provider = new \App\Services\Banking\Providers\XixapayProvider();
                     $result = $provider->withdrawVirtualCard($cardId, $amount);
                     if ($result['status'] === 'success') {
                     // Log Transaction
                     DB::table('card_transactions')->insert([
                     'card_id' => $cardId,
                     'xixapay_transaction_id' => 'ADMIN_DEBIT_' . time(),
                     'amount' => $amount,
                     'currency' => $card->card_type,
                     'status' => 'success',
                     'merchant_name' => 'Admin Debit/Withdrawal',
                     'created_at' => now(),
                     'updated_at' => now()
                     ]);
                     return response()->json(['status' => 'success', 'message' => 'Card Debited Successfully']);
                     }
                     return response()->json(['status' => 'error', 'message' => $result['message']], 400);
                     */
                    return response()->json(['status' => 'error', 'message' => 'Card service unavailable'], 503);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminDeleteCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->whereIn('type', ['admin', 'ADMIN']);
                if ($check_user->count() > 0) {
                    $cardId = $request->card_id;
                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();

                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);
                    if ($card->status !== 'terminated')
                        return response()->json(['status' => 'error', 'message' => 'Only terminated cards can be deleted'], 400);

                    DB::table('virtual_cards')->where('card_id', $cardId)->delete();
                    return response()->json(['status' => 'success', 'message' => 'Card Record Deleted Successfully']);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminCardCustomerInfo(Request $request, $cardId)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->whereIn('type', ['admin', 'ADMIN']);
                if ($check_user->count() > 0) {
                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();
                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);

                    $user = DB::table('users')->where('id', $card->user_id)->first();
                    // Fetch latest balance from provider
                    // $provider = new \App\Services\Banking\Providers\XixapayProvider();
                    // $details = $provider->getCardDetails($cardId);
                    $details = null; // Provider removed

                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'user' => $user,
                            'card' => $card,
                            'provider_details' => $details['data'] ?? null
                        ]
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
    // ==========================================
    // COMPANY VERIFICATION (GRANULAR)
    // ==========================================

    public function GetCompanyDetail(Request $request, $id)
    {
        // 1. Authenticate Admin
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->admin_id)) { // Changed from 'id' to 'admin_id' to avoid conflict with route param
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->admin_id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                });

                if ($check_user->count() > 0) {
                    // 2. Fetch Company
                    $company = DB::table('companies')->where('id', $id)->first();
                    if (!$company) {
                        return response()->json(['status' => 404, 'message' => 'Company not found']);
                    }

                    // 3. Fetch Documents (Granular)
                    // If documents are not yet in the new table (migration phase), we might need to seed them from `kyc_documents` JSON column.
                    // For now, let's assume we read from the new table OR fallback to the JSON if empty.

                    $documents = DB::table('company_documents')->where('company_id', $company->id)->get();

                    // MIGRATION HELPER: If new table is empty but old JSON exists, populate it on the fly (Self-healing)
                    if ($documents->isEmpty() && !empty($company->kyc_documents)) {
                        $oldDocs = json_decode($company->kyc_documents, true);
                        if (is_array($oldDocs)) {
                            $now = now();
                            foreach ($oldDocs as $type => $path) {
                                DB::table('company_documents')->insert([
                                    'company_id' => $company->id,
                                    'document_type' => $type,
                                    'file_path' => $path,
                                    'status' => 'pending',
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ]);
                            }
                            $documents = DB::table('company_documents')->where('company_id', $company->id)->get();
                        }
                    }

                    // 3b. Ensure Virtual Documents for Data Sections exist (for Admin Comments/Rejection)
                    $virtualTypes = ['business_data', 'address_data', 'owner_data', 'account_data', 'bvn_data'];
                    $hasNew = false;
                    foreach ($virtualTypes as $type) {
                        if (!$documents->contains('document_type', $type)) {
                            DB::table('company_documents')->insert([
                                'company_id' => $company->id,
                                'document_type' => $type,
                                'file_path' => 'virtual',
                                'status' => 'pending',
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                            $hasNew = true;
                        }
                    }

                    if ($hasNew) {
                        $documents = DB::table('company_documents')->where('company_id', $company->id)->get();
                    }

                    // 4. Fetch User Details (Owner)
                    $owner = DB::table('users')->where('id', $company->user_id)->first();

                    // 5. Fetch BVN Verification Data (if exists)
                    $owner_bvn = null;
                    if ($owner) {
                        $bvn_record = DB::table('user_kyc')
                            ->where('user_id', $owner->id)
                            ->where('id_type', 'bvn')
                            ->orderBy('id', 'desc')
                            ->first();

                        if ($bvn_record && $bvn_record->full_response_json) {
                            $owner_bvn = json_decode($bvn_record->full_response_json, true);
                        }
                    }

                    return response()->json([
                        'status' => 200,
                        'company' => $company,
                        'documents' => $documents,
                        'user' => $owner,
                        'owner_bvn' => $owner_bvn
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function ReviewCompanyDocument(Request $request)
    {
        // Inputs: admin_id, document_id, status ('approved', 'rejected'), reason (optional)
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->admin_id)) {
                $admin = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->admin_id)])->where(function ($query) {
                    $query->whereIn('type', ['admin', 'ADMIN']);
                })->first();

                if ($admin) {
                    $docId = $request->document_id;
                    $status = $request->status;
                    $reason = $request->reason;

                    $document = DB::table('company_documents')->where('id', $docId)->first();
                    if (!$document) {
                        return response()->json(['status' => 404, 'message' => 'Document not found']);
                    }

                    // UPDATE DOCUMENT STATUS
                    DB::table('company_documents')->where('id', $docId)->update([
                        'status' => $status,
                        'rejection_reason' => ($status === 'rejected') ? $reason : null,
                        'reviewed_at' => now(),
                        'reviewed_by' => $admin->id
                    ]);

                    // SYNC WITH company_kyc_approvals table for user dashboard feedback
                    $sectionMapping = [
                        'business_data' => 'business_info',
                        'address_data' => 'business_info',
                        'account_data' => 'account_info',
                        'bvn_data' => 'bvn_info',
                        'owner_data' => 'board_members',
                        'cac_certificate' => 'documents',
                        'board_resolution' => 'documents',
                        'company_profile' => 'documents',
                        'memart' => 'documents',
                        'id_card' => 'documents',
                        'board_member_utility_bill' => 'documents'
                    ];

                    $section = $sectionMapping[$document->document_type] ?? null;
                    if ($section) {
                        // We update the section status. 
                        // Note: If multiple docs belong to one section (like 'documents'), 
                        // a rejection in one marks the section as rejected.
                        DB::table('company_kyc_approvals')->updateOrInsert(
                            ['company_id' => $document->company_id, 'section' => $section],
                            [
                                'status' => $status,
                                'rejection_reason' => ($status === 'rejected') ? $reason : null,
                                'reviewed_by' => $admin->id,
                                'reviewed_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                    }

                    // CHECK OVERALL COMPANY STATUS
                    // If any document is rejected -> Company is 'partially_approved' (or pending if none approved yet)
                    // If all documents are approved -> Company is 'verified'

                    $allDocs = DB::table('company_documents')->where('company_id', $document->company_id)->get();
                    $totalDocs = $allDocs->count();
                    $approvedDocs = $allDocs->where('status', 'approved')->count();
                    $rejectedDocs = $allDocs->where('status', 'rejected')->count();

                    $newCompanyStatus = 'pending';
                    if ($rejectedDocs > 0) {
                        $newCompanyStatus = 'partial'; // Or customize as needed
                    } elseif ($approvedDocs === $totalDocs && $totalDocs > 0) {
                        $newCompanyStatus = 'verified';
                    }

                    DB::table('companies')->where('id', $document->company_id)->update([
                        'kyc_status' => $newCompanyStatus,
                        'kyc_reviewed_at' => now(),
                        'kyc_reviewed_by' => $admin->id
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => "Document marked as $status",
                        'company_status' => $newCompanyStatus
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
    public function getAllWebhookLogs(Request $request)
    {
        $id = $request->id;
        $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($id)])->whereIn('type', ['admin', 'ADMIN']);
        if ($check_user->count() > 0) {
            $logs = DB::table('webhook_logs')
                ->leftJoin('companies', 'webhook_logs.company_id', '=', 'companies.id')
                ->select('webhook_logs.*', 'companies.name as company_name')
                ->orderBy('webhook_logs.created_at', 'desc')
                ->paginate(20);
            return response()->json(['status' => 'success', 'data' => $logs]);
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function getAllApiLogs(Request $request)
    {
        $id = $request->id;
        $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($id)])->whereIn('type', ['admin', 'ADMIN']);
        if ($check_user->count() > 0) {
            $logs = DB::table('api_request_logs')
                ->leftJoin('companies', 'api_request_logs.company_id', '=', 'companies.id')
                ->select('api_request_logs.*', 'companies.name as company_name')
                ->orderBy('api_request_logs.created_at', 'desc')
                ->paginate(20);
            return response()->json(['status' => 'success', 'data' => $logs]);
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
}