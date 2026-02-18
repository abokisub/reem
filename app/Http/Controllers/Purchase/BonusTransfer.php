<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class BonusTransfer extends Controller
{

    public function Convert(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $transid = $this->purchase_ref('BONUS_');
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            // Mobile app authentication
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
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }

        if ($accessToken) {
            $earning_min = $this->core()->earning_min;
            $validator = Validator::make($request->all(), [
                'amount' => "required|numeric|integer|not_in:0|gt:0|min:$earning_min"
            ]);
            $user_check = DB::table('users')->where(['api_key' => $accessToken, 'status' => 'active']);
            if ($user_check->count() == 1) {
                $user = $user_check->first();
                if ($validator->fails()) {
                    return response()->json([
                        'message' => $validator->errors()->first(),
                        'status' => 'fail'
                    ])->setStatusCode(403);
                } else {
                    if ($this->core()->referral == 1) {
                        if ($request->amount > 0) {
                            DB::beginTransaction();
                            $user = DB::table('users')->where(['id' => $user->id])->lockForUpdate()->first();
                            if ($user->referral_balance > 0) {
                                if ($user->referral_balance >= $request->amount) {
                                    $debit = $user->referral_balance - $request->amount;
                                    if (DB::table('users')->where(['id' => $user->id])->update(['referral_balance' => $debit, 'balance' => $user->balance + $request->amount])) {
                                        DB::commit();
                                        $trans_history = [
                                            'username' => $user->username,
                                            'amount' => $request->amount,
                                            'message' => 'you have successfully transfer ₦' . number_format($request->amount, 2) . ' to you main  wallet',
                                            'oldbal' => $user->referral_balance,
                                            'newbal' => $debit,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $transid,
                                            'role' => 'earning'
                                        ];
                                        $notif = [
                                            'username' => $user->username,
                                            'message' => 'you have successfully transfer ₦' . number_format($request->amount, 2) . ' to you main  wallet',
                                            'date' => $this->system_date(),
                                            'habukhan' => 0,
                                        ];
                                        DB::table('notif')->insert($notif);
                                        DB::table('message')->insert($trans_history);
                                        return response()->json([
                                            'status' => 'success',
                                            'transid' => $transid,
                                            'message' => 'you have successfully transfer ₦' . number_format($request->amount, 2) . ' to you main  wallet',
                                        ]);
                                    } else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'An error Occur'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Insufficient Account Kindly Refer more people to the system => ₦' . number_format($user->referral_balance, 2)
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Insufficient Account Kindly Refer more people to the system => ₦' . number_format($user->referral_balance, 2)
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
                            'message' => 'Bonus Transfer Not Available Right Now'
                        ])->setStatusCode(403);
                    }
                }
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'kindly reload ur browser token expired'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Accesstoken required'
            ])->setStatusCode(403);
        }
    }
}
