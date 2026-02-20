<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppController extends Controller
{
    public function system(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            return response()->json([
                'status' => 'success',
                'setting' => $this->core(),
                'service_lock' => DB::table('service_lock')->first(),
                'feature' => $this->feature(),
                'general' => $this->general(),
                'bank' => DB::table('habukhan_key')->select('account_number', 'account_name', 'bank_name', 'min', 'max')->first(),
                'support' => [
                    'support_ai_name' => 'Aboki',
                    'support_call_name' => 'Aminiya',
                    'support_phone' => $this->general()->app_phone ?? '+2348139123922',
                    'support_whatsapp' => $this->general()->app_whatsapp ?? $this->general()->app_phone ?? '+2348139123922',
                    'support_whatsapp_group' => $this->general()->wa_group ?? '',
                    'support_help_url' => $this->general()->help_url ?? ''
                ]
            ]);
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function welcomeMessage()
    {
        $message = DB::table('settings')->value('notif_message');
        return response()->json([
            'status' => 'success',
            'message' => $message ?? 'Glo cooperate have been reduce with 30days validity Dan samun update da zaran munyi posting ka yi save din number mu 08139123922'
        ]);
    }

    public function discountOther(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            // Get KYC service charges
            $user = DB::table('users')->where('id', $this->verifytoken($request->id))->first();
            $cid = $user->active_company_id ?? 1;

            $kycCharges = DB::table('service_charges')
                ->where('company_id', $cid)
                ->where('service_category', 'kyc')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            if ($kycCharges->isEmpty() && $cid != 1) {
                $kycCharges = DB::table('service_charges')
                    ->where('company_id', 1)
                    ->where('service_category', 'kyc')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            }

            $kycCharges = $kycCharges->mapWithKeys(function ($charge) {
                return [
                    $charge->service_name => [
                        'name' => $charge->display_name,
                        'type' => $charge->charge_type,
                        'value' => (float) $charge->charge_value,
                        'cap' => $charge->charge_cap ? (float) $charge->charge_cap : null
                    ]
                ];
            });

            // Get PalmPay VA charge
            $palmpayVA = DB::table('service_charges')
                ->where('company_id', $cid)
                ->where('service_category', 'payment')
                ->where('service_name', 'palmpay_va')
                ->where('is_active', true)
                ->first();

            if (!$palmpayVA && $cid != 1) {
                $palmpayVA = DB::table('service_charges')
                    ->where('company_id', 1)
                    ->where('service_category', 'payment')
                    ->where('service_name', 'palmpay_va')
                    ->where('is_active', true)
                    ->first();
            }

            // Get card settings
            $cardSettings = DB::table('card_settings')->where('company_id', $cid)->first();
            if (!$cardSettings && $cid != 1) {
                $cardSettings = DB::table('card_settings')->where('company_id', 1)->first();
            }

            return response()->json([
                'status' => 'success',
                'kyc_services' => $kycCharges,
                'palmpay_va_charge' => $palmpayVA ? [
                    'type' => $palmpayVA->charge_type,
                    'value' => (float) $palmpayVA->charge_value,
                    'cap' => $palmpayVA->charge_cap ? (float) $palmpayVA->charge_cap : null
                ] : null,
                // Keep existing card charges
                'vcard_ngn_fee' => $cardSettings->ngn_creation_fee ?? 500,
                'vcard_usd_fee' => $cardSettings->usd_creation_fee ?? 3,
                'vcard_usd_rate' => $cardSettings->ngn_rate ?? 1600,
                'vcard_fund_fee' => $cardSettings->funding_fee_percent ?? 1,
                'vcard_usd_failed_fee' => $cardSettings->usd_failed_tx_fee ?? 0.4,
                'vcard_ngn_fund_fee' => $cardSettings->ngn_funding_fee_percent ?? 2,
                'vcard_usd_fund_fee' => $cardSettings->usd_funding_fee_percent ?? 2,
                'vcard_ngn_failed_fee' => $cardSettings->ngn_failed_tx_fee ?? 0,
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getDiscountSystem(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $settings = DB::table('settings')->first();
            return response()->json([
                'status' => 'success',
                'version' => $settings->version ?? '1.0.0',
                'update_url' => $settings->update_url ?? '',
                'playstore_url' => $settings->playstore_url ?? '',
                'appstore_url' => $settings->appstore_url ?? '',
                'update_title' => $settings->app_update_title ?? '',
                'update_desc' => $settings->app_update_desc ?? '',
                'maintenance' => (bool) ($settings->maintenance ?? false),
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getBankCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $user = DB::table('users')->where('id', $this->verifytoken($request->id))->first();
            $cid = $user->active_company_id ?? 1;
            $settings = $this->core($cid);

            // Get company-specific settlement settings (safe check for columns)
            $company = DB::table('companies')->where('id', $cid)->first();
            $useCustomSettlement = false;
            $customDelayHours = 24;
            $customMinimum = 100.00;
            
            // Check if settlement columns exist (after migration)
            if ($company && property_exists($company, 'custom_settlement_enabled')) {
                $useCustomSettlement = $company->custom_settlement_enabled;
                $customDelayHours = $company->custom_settlement_delay_hours ?? 24;
                $customMinimum = $company->custom_settlement_minimum ?? 100.00;
            }

            // Build settlement config (safe check for settings columns)
            $settlementConfig = [
                'enabled' => property_exists($settings, 'auto_settlement_enabled') ? (bool) $settings->auto_settlement_enabled : true,
                'delay_hours' => $useCustomSettlement ? (float) $customDelayHours : (property_exists($settings, 'settlement_delay_hours') ? (float) $settings->settlement_delay_hours : 24),
                'skip_weekends' => property_exists($settings, 'settlement_skip_weekends') ? (bool) $settings->settlement_skip_weekends : true,
                'skip_holidays' => property_exists($settings, 'settlement_skip_holidays') ? (bool) $settings->settlement_skip_holidays : true,
                'settlement_time' => property_exists($settings, 'settlement_time') ? $settings->settlement_time : '03:00:00',
                'minimum_amount' => $useCustomSettlement ? (float) $customMinimum : (property_exists($settings, 'settlement_minimum_amount') ? (float) $settings->settlement_minimum_amount : 100.00),
                'description' => 'T+1 Settlement: Funds settle next business day at 3am (PalmPay settles at 2am). Transactions from Friday/Saturday/Sunday settle on Monday. Holidays are skipped.',
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    // Pay with Transfer
                    'pay_with_transfer' => [
                        'type' => $settings->transfer_charge_type ?? 'FLAT',
                        'value' => (float) ($settings->transfer_charge_value ?? 0),
                        'cap' => $settings->transfer_charge_cap ? (float) $settings->transfer_charge_cap : null,
                    ],
                    // Pay with Wallet
                    'pay_with_wallet' => [
                        'type' => $settings->wallet_charge_type ?? 'PERCENT',
                        'value' => (float) ($settings->wallet_charge_value ?? 1.2),
                        'cap' => $settings->wallet_charge_cap ? (float) $settings->wallet_charge_cap : null,
                    ],
                    // Payout to Bank
                    'payout_to_bank' => [
                        'type' => $settings->payout_bank_charge_type ?? 'FLAT',
                        'value' => (float) ($settings->payout_bank_charge_value ?? 30),
                        'cap' => $settings->payout_bank_charge_cap ? (float) $settings->payout_bank_charge_cap : null,
                    ],
                    // Payout to PalmPay
                    'payout_to_palmpay' => [
                        'type' => $settings->payout_palmpay_charge_type ?? 'FLAT',
                        'value' => (float) ($settings->payout_palmpay_charge_value ?? 15),
                        'cap' => $settings->payout_palmpay_charge_cap ? (float) $settings->payout_palmpay_charge_cap : null,
                    ],
                    // Settlement Rules
                    'settlement' => $settlementConfig,
                ]
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getVirtualAccountStatus(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $settings = DB::table('settings')->select(
                'palmpay_enabled',
                'monnify_enabled',
                'wema_enabled',
                'xixapay_enabled',
                'default_virtual_account'
            )->first();

            return response()->json([
                'status' => 'success',
                'providers' => [
                    'palmpay' => [
                        'enabled' => (bool) ($settings->palmpay_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'palmpay'
                    ],
                    'monnify' => [
                        'enabled' => (bool) ($settings->monnify_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'monnify'
                    ],
                    'wema' => [
                        'enabled' => (bool) ($settings->wema_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'wema'
                    ],
                    'xixapay' => [
                        'enabled' => (bool) ($settings->xixapay_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'xixapay'
                    ]
                ],
                'default_provider' => $settings->default_virtual_account ?? 'palmpay'
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getDiscountCash(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $user = DB::table('users')->where('id', $this->verifytoken($request->id))->first();
            $cid = $user->active_company_id ?? 1;
            $discount = DB::table('cash_discount')->where('company_id', $cid)->first();
            if (!$discount)
                $discount = DB::table('cash_discount')->where('company_id', 1)->first();
            return response()->json([
                'status' => 'success',
                'cash' => [
                    'mtn' => $discount->mtn ?? 80,
                    'glo' => $discount->glo ?? 70,
                    'airtel' => $discount->airtel ?? 70,
                    'mobile' => $discount->mobile ?? 70,
                    'mtn_status' => $discount->mtn_status ?? 1,
                    'glo_status' => $discount->glo_status ?? 1,
                    'airtel_status' => $discount->airtel_status ?? 1,
                    'mobile_status' => $discount->mobile_status ?? 1,
                ]
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }


    public function apiUpgrade(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $validator = validator::make($request->all(), [
                'username' => 'required|max:25',
                'url' => 'required|url',
            ], [
                'url.url' => 'Invalid URL it must contain (https or www)',
                'url.required' => 'Your website URL is Needed For Verification'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 403
                ])->setStatusCode(403);
            } else {
                //api user dat need upgrade
                $check_me = [
                    'username' => $request->username,
                    'status' => 'active'
                ];
                $check_user = DB::table('users')->where($check_me);
                if ($check_user->count() == 1) {
                    $user = $check_user->first();
                    $general = $this->general();
                    $date = $this->system_date();
                    $ref = $this->generate_ref('API_UPGRADE');
                    $get_admins = DB::table('users')->where('status', 'active')->where(function ($query) {
                        $query->where('type', 'ADMIN')
                            ->orWhere('type', 'CUSTOMER');
                    });
                    if ($get_admins->count() > 0) {
                        foreach ($get_admins->get() as $send_admin) {
                            $email_data = [
                                'name' => $user->name,
                                'email' => $send_admin->email,
                                'username' => $user->username,
                                'title' => 'API PACKAGE REQUEST',
                                'sender_mail' => $general->app_email,
                                'user_email' => $user->email,
                                'app_name' => $general->app_name,
                                'website' => $request->url,
                                'date' => $date,
                                'transid' => $ref,
                                'app_phone' => $general->app_phone
                            ];
                            MailController::send_mail($email_data, 'email.apirequest');
                        }
                        $insert_data = [
                            'username' => $user->username,
                            'date' => $date,
                            'transid' => $ref,
                            'status' => 'pending',
                            'title' => 'API UPGRAGE',
                            'message' => $user->username . ', want is account to be upgraded to API Package and is website url is ' . $request->url
                        ];
                        $insert = $this->inserting_data('request', $insert_data);
                        if ($insert) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Your Request has been received and it will be processed within 3-5 days'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'System is unable to send request now',
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Unable to get Admins',
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }



    public function buildWebsite(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $validator = validator::make($request->all(), [
                'username' => 'required|max:25',
                'url' => 'required|url',
            ], [
                'url.url' => 'Invalid URL it must contain (https or www)',
                'url.required' => 'Your website URL is Needed For Verification'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 403
                ])->setStatusCode(403);
            } else {
                $check_me = [
                    'username' => $request->username,
                    'status' => 'active'
                ];
                $check_user = DB::table('users')->where($check_me);
                if ($check_user->count() == 1) {
                    $user = $check_user->first();
                    $general = $this->general();
                    $date = $this->system_date();
                    $setting = $this->core();
                    $ref = $this->generate_ref('AFFLIATE_WEBSITE');
                    if (!empty($setting->affliate_price)) {
                        if ($user->balance > $setting->affliate_price) {
                            $verify = DB::table('message')->where('transid', $ref);
                            if ($verify->count() == 0) {
                                $check_request = DB::table('request')->where('transid', $ref);
                                if ($check_request->count() == 0) {
                                    $debit_user = $user->balance - $setting->affliate_price;
                                    $data = [
                                        'balance' => $debit_user,
                                    ];
                                    $where_user = [
                                        'username' => $user->username,
                                        'id' => $user->id
                                    ];
                                    $update_user = $this->updateData($data, 'users', $where_user);
                                    if ($update_user) {
                                        $insert_message = [
                                            'username' => $user->username,
                                            'amount' => $setting->affliate_price,
                                            'message' => 'Purchase An Affliate Website',
                                            'oldbal' => $user->balance,
                                            'newbal' => $debit_user,
                                            'habukhan_date' => $date,
                                            'transid' => $ref,
                                            'plan_status' => 1,
                                            'role' => 'WEBSITE'
                                        ];
                                        $this->inserting_data('message', $insert_message);
                                        $get_admins = DB::table('users')->where('status', 'active')->where(function ($query) {
                                            $query->where('type', 'ADMIN')
                                                ->orWhere('type', 'CUSTOMER');
                                        });
                                        if ($get_admins->count() > 0) {
                                            foreach ($get_admins->get() as $send_admin) {
                                                $email_data = [
                                                    'name' => $user->name,
                                                    'email' => $send_admin->email,
                                                    'username' => $user->username,
                                                    'title' => 'AFFLIATE WEBSITE',
                                                    'sender_mail' => $general->app_email,
                                                    'user_email' => $user->email,
                                                    'app_name' => $general->app_name,
                                                    'website' => $request->url,
                                                    'date' => $date,
                                                    'transid' => $ref,
                                                    'app_phone' => $general->app_phone
                                                ];
                                                MailController::send_mail($email_data, 'email.affliate_request');
                                            }
                                            $insert_data = [
                                                'username' => $user->username,
                                                'date' => $date,
                                                'transid' => $ref,
                                                'status' => 'pending',
                                                'title' => 'AFFLIATE WEBSITE',
                                                'message' => $user->username . ', want to make an affliate website. Domain Url is (Account Debited)' . $request->url
                                            ];
                                            $insert = $this->inserting_data('request', $insert_data);
                                            if ($insert) {
                                                return response()->json([
                                                    'status' => 'success',
                                                    'message' => 'Your Request has been received and it will be processed within 3-5 days',
                                                ]);
                                            } else {
                                                return response()->json([
                                                    'status' => 403,
                                                    'message' => 'System is unable to send request now',
                                                ])->setStatusCode(403);
                                            }
                                        } else {
                                            return response()->json([
                                                'status' => 403,
                                                'message' => 'Unable to get Admins',
                                            ])->setStatusCode(403);
                                        }
                                    } else {
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Service Currently Not Avialable For You Right Now'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 403,
                                        'message' => 'Please Try Again After Few Mins'
                                    ]);
                                }
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Please Try Again After Few Mins'
                                ]);
                            }
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Insufficient Account Fund Your Wallet And Try Again ~ ₦' . number_format($user->balance, 2)
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'System Is Unable to Detect Price'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User',
                    ])->setStatusCode(403);
                }
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AwufPackage(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!isset($request->id)) {
                return response()->json([
                    'message' => 'User ID Required',
                    'status' => 403
                ])->setStatusCode(403);
            } else {
                $check_me = [
                    'username' => $request->id,
                    'status' => 'active'
                ];
                $check_user = DB::table('users')->where($check_me);
                if ($check_user->count() == 1) {
                    $setting = $this->core();
                    $user = $check_user->first();
                    $ref = $this->generate_ref('AWUF_PACKAGE');
                    $date = $this->system_date();
                    if (!empty($setting->awuf_price)) {
                        if ($user->balance > $setting->awuf_price) {
                            $debit_user = $user->balance - $setting->awuf_price;
                            $credit_user = $debit_user + $setting->awuf_price;
                            if ($this->updateData(['balance' => $debit_user], 'users', ['username' => $user->username, 'id' => $user->id])) {
                                if (DB::table('message')->where('transid', $ref)->count() == 0) {
                                    $data = [
                                        'username' => $user->username,
                                        'amount' => $setting->awuf_price,
                                        'habukhan_date' => $date,
                                        'transid' => $ref,
                                        'plan_status' => 1,
                                        'newbal' => $debit_user,
                                        'oldbal' => $user->balance,
                                        'message' => 'Successfully Upgraded Your Account To AWUF PACKAGE',
                                        'role' => 'UPGRADE'
                                    ];
                                    if ($this->inserting_data('message', $data)) {
                                        $this->updateData(['type' => 'AWUF'], 'users', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Account Upgraded To AWUF PACKAGE Successfully'
                                        ]);
                                    } else {
                                        $this->updateData(['balance' => $credit_user], 'users', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Try Again Later'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    $this->updateData(['balance' => $credit_user], 'users', ['username' => $user->username, 'id' => $user->id]);
                                    return response()->json([
                                        'status' => 403,
                                        'message' => 'Try Again Later'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'System Unavialable Right Now'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Insufficient Account, Fund Your Wallet And Try Again ~ ₦' . number_format($user->balance, 2)
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'System is unable to Detect Price Right Now'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User',
                    ])->setStatusCode(403);
                }
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    //agent package

    public function AgentPackage(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!isset($request->id)) {
                return response()->json([
                    'message' => 'User ID Required',
                    'status' => 403
                ])->setStatusCode(403);
            } else {
                $check_me = [
                    'username' => $request->id,
                    'status' => 'active'
                ];
                $check_user = DB::table('users')->where($check_me);
                if ($check_user->count() == 1) {
                    $setting = $this->core();
                    $user = $check_user->first();
                    $ref = $this->generate_ref('AGENT_PACKAGE');
                    $date = $this->system_date();
                    if (!empty($setting->agent_price)) {
                        if ($user->balance > $setting->agent_price) {
                            $debit_user = $user->balance - $setting->agent_price;
                            $credit_user = $debit_user + $setting->agent_price;
                            if ($this->updateData(['balance' => $debit_user], 'users', ['username' => $user->username, 'id' => $user->id])) {
                                if (DB::table('message')->where('transid', $ref)->count() == 0) {
                                    $data = [
                                        'username' => $user->username,
                                        'amount' => $setting->agent_price,
                                        'habukhan_date' => $date,
                                        'transid' => $ref,
                                        'plan_status' => 1,
                                        'newbal' => $debit_user,
                                        'oldbal' => $user->balance,
                                        'message' => 'Successfully Upgraded Your Account To AGENT PACKAGE',
                                        'role' => 'UPGRADE'
                                    ];
                                    if ($this->inserting_data('message', $data)) {
                                        $this->updateData(['type' => 'AGENT'], 'users', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Account Upgraded To AGENT PACKAGE Successfully'
                                        ]);
                                    } else {
                                        $this->updateData(['balance' => $credit_user], 'users', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Try Again Later'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    $this->updateData(['balance' => $credit_user], 'users', ['username' => $user->username, 'id' => $user->id]);
                                    return response()->json([
                                        'status' => 403,
                                        'message' => 'Try Again Later'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'System Unavialable Right Now'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Insufficient Account, Fund Your Wallet And Try Again ~ ₦' . number_format($user->balance, 2)
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'System is unable to Detect Price Right Now'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User',
                    ])->setStatusCode(403);
                }
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function SystemNetwork(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            return response()->json([
                'status' => 'success',
                'network' => DB::table('network')->select('id', 'network', 'network_vtu', 'network_share', 'network_sme', 'network_cg', 'network_g', 'plan_id', 'cash', 'data_card', 'recharge_card')->get()
            ]);
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function checkNetworkType(Request $type)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $type->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($type->id)) {
                if (isset($type->token)) {
                    $network = DB::table('network')->where('plan_id', $type->id)->orWhere('id', $type->id)->first();

                    if (!$network) {
                        return response()->json([
                            'status' => 404,
                            'message' => 'Network plan not found'
                        ])->setStatusCode(404);
                    }
                    $user = DB::table('users')->where(['id' => $this->verifytoken($type->token), 'status' => 'active']);
                    if ($user->count() == 1) {
                        $habukhan = $user->first();
                        if (in_array(strtoupper($habukhan->type), ['SMART', 'USER', 'CUSTOMER'])) {
                            $user_type = 'smart';
                        } else if (strtoupper($habukhan->type) == 'AGENT') {
                            $user_type = 'agent';
                        } else if (strtoupper($habukhan->type) == 'AWUF') {
                            $user_type = 'awuf';
                        } else if (strtoupper($habukhan->type) == 'API') {
                            $user_type = 'api';
                        } else {
                            $user_type = 'special';
                        }
                        if ($network->network == '9MOBILE') {
                            $real_network = 'mobile';
                        } else {
                            $real_network = $network->network;
                        }
                        $check_for_vtu = strtolower($real_network) . "_vtu_" . $user_type;
                        $check_for_sns = strtolower($real_network) . "_share_" . $user_type;
                        $airtime_discount = DB::table('airtime_discount')->where('company_id', $habukhan->active_company_id)->first();

                        if (!$airtime_discount) {
                            $airtime_discount = DB::table('airtime_discount')->where('company_id', 1)->first();
                        }

                        return response()->json([
                            'status' => 'success',
                            'network' => $network,
                            'price_vtu' => $airtime_discount ? $airtime_discount->$check_for_vtu : 0,
                            'price_sns' => $airtime_discount ? $airtime_discount->$check_for_sns : 0
                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload Your Browser'
                        ])->setStatusCode(403);
                    }
                } else {
                    $network = DB::table('network')->where('plan_id', $type->id)->first();
                    return response()->json([
                        'status' => 'success',
                        'network' => $network,
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'network plan id need'
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

    public function DeleteUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        for ($i = 0; $i < count($request->username); $i++) {
                            $username = $request->username[$i];
                            $delete_user = DB::table('users')->where('username', $username);
                            if ($delete_user->count() > 0) {
                                $delete = DB::table('users')->where('username', $username)->delete();
                                DB::table('wallet_funding')->where('username', $username)->delete();
                            } else {
                                $delete = false;
                            }
                        }
                        if ($delete) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Deleted Successfully'
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
    public function singleDelete(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        $check_user = DB::table('users')->where('username', $request->username);
                        if ($check_user->count() > 0) {
                            if (DB::table('users')->where('username', $request->username)->delete()) {
                                DB::table('wallet_funding')->where('username', $request->username)->delete();
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Account Deleted Successfully'
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
                                'message' => 'User Not Found'
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
    public function UserNotif(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {

                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() > 0) {
                    $habukhan = $check_user->first();
                    $habukhan_username = $habukhan->username;
                    // user request
                    $user_request = DB::table('notif')->where('username', $habukhan_username);
                    if ($user_request->count() > 0) {
                        foreach ($user_request->orderBy('id', 'desc')->get() as $habukhan) {
                            $select_user = DB::table('users')->where('username', $habukhan->username);
                            if ($select_user->count() > 0) {
                                $users = $select_user->first();
                                if ($users->profile_image !== null) {
                                    $profile_image[] = ['username' => $habukhan->username, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->profile_image, 'status' => $habukhan->habukhan];
                                } else {
                                    $profile_image[] = ['username' => $habukhan->username, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->username, 'status' => $habukhan->habukhan];
                                }
                            } else {
                                $profile_image[] = ['username' => $habukhan->username, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $habukhan->username, 'status' => $habukhan->habukhan];
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
    public function ClearNotifUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {

                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() > 0) {
                    $habukhan = $check_user->first();
                    $habukhan_username = $habukhan->username;
                    // user request
                    DB::table('notif')->where('username', $habukhan_username)->delete();
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

    public function CableName(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            return response()->json([
                'status' => 'success',
                'cable' => DB::table('cable_result_lock')->first()
            ]);
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function BillCal(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if ((isset($request->id)) && (!empty($request->id))) {
                if (is_numeric($request->id)) {
                    $user = DB::table('users')->where('id', $this->verifytoken($request->user_id ?? $request->id_token))->first();
                    $cid = $user->active_company_id ?? 1;
                    $bill_d = DB::table('bill_charge')->where('company_id', $cid)->first();
                    if (!$bill_d)
                        $bill_d = DB::table('bill_charge')->where('company_id', 1)->first();

                    if ($bill_d->direct == 1) {
                        $charges = $bill_d->bill;
                    } else {
                        $charges = ($request->id / 100) * $bill_d->bill;
                    }
                    return response()->json([
                        'status' => 'suucess',
                        'charges' => $charges
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'invalid amount'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DiscoList()
    {
        $is_locked = DB::table('service_lock')->where('bill', 0)->exists();
        if ($is_locked) {
            return response()->json([
                'status' => 'success',
                'bill' => [],
                'message' => 'Electricity service is currently locked by admin'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'bill' => DB::table('bill_plan')->where('plan_status', 1)->select('plan_id', 'disco_name')->get()
        ]);
    }
    public function CashNumber()
    {
        return response()->json([
            'numbers' => DB::table('cash_discount')->select('mtn_number', 'glo_number', 'mobile_number', 'airtel_number')->first()
        ]);
    }
    public function AirtimeCash(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->amount)) {
                if (!empty($request->network)) {

                    if ($request->network == '9MOBILE') {
                        $network_name = 'mobile';
                    } else {
                        $network_name = strtolower($request->network);
                    }
                    $system_admin = DB::table('cash_discount')->first();
                    if (!$system_admin || !isset($system_admin->$network_name)) {
                        return response()->json([
                            'message' => 'Configuration for this network is missing',
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    $credit = ($request->amount / 100) * $system_admin->$network_name;

                    return response()->json([
                        'amount' => $credit,
                        'status' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Network Required'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Amount Required'
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
    public function BulksmsCal(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $setting = $this->core();
            return response()->json([
                'amount' => $setting->bulk_sms ?? 0
            ]);
        } else {
            return redirect(config('app.error_500'));
        }
    }
    public function ResultPrice(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $user = DB::table('users')->where('id', $this->verifytoken($request->user_id ?? $request->id_token))->first();
            $cid = $user->active_company_id ?? 1;
            $price = DB::table('result_charge')->where('company_id', $cid)->first();
            if (!$price)
                $price = DB::table('result_charge')->where('company_id', 1)->first();

            return response()->json([
                'price' => $price
            ]);
        } else {
            return redirect(config('app.error_500'));
        }
    }

    public function getAppInfo(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $general = $this->general();
            $faqs = DB::table('faqs')->where('status', 1)->get();

            return response()->json([
                'status' => 'success',
                'system' => [
                    'name' => $general->app_name ?? 'Kobopoint',
                    'email' => $general->app_email,
                    'phone' => $general->app_phone,
                    'address' => $general->app_address,
                ],
                'contact' => [
                    'phone' => $general->app_phone,
                    'email' => $general->app_email,
                    'whatsapp' => $general->app_whatsapp,
                    'address' => $general->app_address,
                    'facebook' => $general->facebook,
                    'tiktok' => $general->tiktok,
                    'instagram' => $general->instagram,

                    // Support Identity
                    'support_ai_name' => 'Aboki',
                    'support_call_name' => 'Aminiya',
                    'support_phone' => $general->app_phone,
                    'support_whatsapp' => $general->app_whatsapp ?? $general->app_phone,
                ],
                'faqs' => $faqs
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function emailReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'transid' => 'required',
            'pdf_base64' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $userId = $this->verifyapptoken($request->user_id) ?? $this->verifytoken($request->user_id);
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found or session expired'], 401);
        }

        try {
            $pdfData = base64_decode($request->pdf_base64);
            $general = $this->general();

            $email_data = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'title' => 'Your Purchase Receipt - ' . $request->transid,
                'sender_mail' => $general->app_email,
                'app_name' => config('app.name'),
                'transid' => $request->transid,
                'date' => now()->toDayDateTimeString(),
            ];

            $attachment = [
                'data' => $pdfData,
                'name' => 'Receipt_' . $request->transid . '.pdf',
                'mime' => 'application/pdf'
            ];

            $sent = MailController::send_mail($email_data, 'email.receipt', $attachment);

            if ($sent) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Receipt has been sent to your email: ' . $user->email
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Failed to send email. Please try again.'], 500);

        } catch (\Exception $e) {
            \Log::error('Email Receipt Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'An internal error occurred.'], 500);
        }
    }
}
