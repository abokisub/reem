<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionCalculator extends Controller
{

    public function Admin(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifyapptoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $card_settings = DB::table('card_settings')->first();
                    $ngn_rate = $card_settings->ngn_rate ?? 1600;
                    // all here
                    if ($request->status == 'TODAY') {
                        $data_trans = DB::table('data')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1])->get();
                        $airtime_trans = DB::table('airtime')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1])->get();
                        $cable_trans = DB::table('cable')->whereDate('date', Carbon::now("Africa/Lagos"))->where(['status' => 1])->get();
                        $exam_trans = DB::table('exam')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1])->get();
                        $bulksms_trans = DB::table('bulksms')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1])->get();
                        $deposit_trans = DB::table('transactions')->whereDate('created_at', Carbon::now("Africa/Lagos"))->where(['status' => 'success', 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->whereDate('habukhan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1])->get();
                        $cash_trans = DB::table('cash')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1])->get();
                        $bill_trans = DB::table('bill')->whereDate('date', Carbon::now("Africa/Lagos"))->where(['status' => 1])->get();
                        $transfer_trans = DB::table('transactions')->whereDate('created_at', Carbon::now("Africa/Lagos"))->where(['status' => 'success', 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $card_ext_trans = DB::table('card_transactions')->join('virtual_cards', 'card_transactions.card_id', '=', 'virtual_cards.card_id')->select('card_transactions.*', 'virtual_cards.card_type')->whereDate('card_transactions.created_at', Carbon::now("Africa/Lagos"))->get();
                        $charity_donations = DB::table('donations')->whereDate('created_at', Carbon::now("Africa/Lagos"))->where('status', 'confirmed')->get();
                        $charity_withdrawals = DB::table('donations')->whereDate('created_at', Carbon::now("Africa/Lagos"))->where('status', 'withdrawn')->get();

                    } else if ($request->status == '7DAYS') {
                        $data_trans = DB::table('data')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $airtime_trans = DB::table('airtime')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $cable_trans = DB::table('cable')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $exam_trans = DB::table('exam')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $bulksms_trans = DB::table('bulksms')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $deposit_trans = DB::table('transactions')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['status' => 'success', 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->whereDate('habukhan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $cash_trans = DB::table('cash')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $bill_trans = DB::table('bill')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1])->get();
                        $transfer_trans = DB::table('transactions')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['status' => 'success', 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $card_ext_trans = DB::table('card_transactions')->join('virtual_cards', 'card_transactions.card_id', '=', 'virtual_cards.card_id')->select('card_transactions.*', 'virtual_cards.card_type')->whereDate('card_transactions.created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->get();
                        $charity_donations = DB::table('donations')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->where('status', 'confirmed')->get();
                        $charity_withdrawals = DB::table('donations')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->where('status', 'withdrawn')->get();

                    } else if ($request->status == '30DAYS') {
                        $data_trans = DB::table('data')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $airtime_trans = DB::table('airtime')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $cable_trans = DB::table('cable')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $exam_trans = DB::table('exam')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $bulksms_trans = DB::table('bulksms')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $deposit_trans = DB::table('transactions')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['status' => 'success', 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->whereDate('habukhan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $cash_trans = DB::table('cash')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $bill_trans = DB::table('bill')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1])->get();
                        $transfer_trans = DB::table('transactions')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['status' => 'success', 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $card_ext_trans = DB::table('card_transactions')->join('virtual_cards', 'card_transactions.card_id', '=', 'virtual_cards.card_id')->select('card_transactions.*', 'virtual_cards.card_type')->whereDate('card_transactions.created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->get();
                        $charity_donations = DB::table('donations')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->where('status', 'confirmed')->get();
                        $charity_withdrawals = DB::table('donations')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->where('status', 'withdrawn')->get();

                    } else if ($request->status == 'ALL TIME') {
                        $data_trans = DB::table('data')->where(['plan_status' => 1])->get();
                        $airtime_trans = DB::table('airtime')->where(['plan_status' => 1])->get();
                        $cable_trans = DB::table('cable')->where(['plan_status' => 1])->get();
                        $exam_trans = DB::table('exam')->where(['plan_status' => 1])->get();
                        $bulksms_trans = DB::table('bulksms')->where(['plan_status' => 1])->get();
                        $deposit_trans = DB::table('transactions')->where(['status' => 'success', 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->where(['plan_status' => 1])->get();
                        $cash_trans = DB::table('cash')->where(['plan_status' => 1])->get();
                        $bill_trans = DB::table('bill')->where(['plan_status' => 1])->get();
                        $transfer_trans = DB::table('transactions')->where(['status' => 'success', 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $card_ext_trans = DB::table('card_transactions')->join('virtual_cards', 'card_transactions.card_id', '=', 'virtual_cards.card_id')->select('card_transactions.*', 'virtual_cards.card_type')->get();
                        $charity_donations = DB::table('donations')->where('status', 'confirmed')->get();
                        $charity_withdrawals = DB::table('donations')->where('status', 'withdrawn')->get();

                    } else if ($request->status == 'CUSTOM USER') {
                        if ((isset($request->from)) and isset($request->to)) {
                            if (!empty($request->username)) {
                                if ((!empty($request->from)) and !empty($request->to)) {
                                    if (DB::table('users')->where(['username' => $request->username])->count() == 1) {
                                        $start_date = Carbon::parse($request->from . ' 00:00:00')->toDateTimeString();
                                        $end_date = Carbon::parse($request->to . ' 23:59:59')->toDateTimeString();
                                        $data_trans = DB::table('data')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $airtime_trans = DB::table('airtime')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $cable_trans = DB::table('cable')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $exam_trans = DB::table('exam')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $bulksms_trans = DB::table('bulksms')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $deposit_trans = DB::table('transactions')->whereBetween('created_at', [$start_date, $end_date])->where(['status' => 'success', 'type' => 'credit', 'category' => 'funding'])->whereIn('user_id', function ($q) use ($request) {
                                            $q->select('id')->from('users')->where('username', $request->username);
                                        })->get();
                                        $spend_trans = DB::table('message')->where(function ($query) {
                                            $query->where('role', '!=', 'credit');
                                            $query->where('plan_status', '!=', 2);
                                        })->whereBetween('habukhan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $cash_trans = DB::table('cash')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $bill_trans = DB::table('bill')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $request->username])->get();
                                        $transfer_trans = DB::table('transactions')->whereBetween('created_at', [$start_date, $end_date])->where(['status' => 'success', 'type' => 'debit', 'category' => 'transfer_out'])->whereIn('user_id', function ($q) use ($request) {
                                            $q->select('id')->from('users')->where('username', $request->username);
                                        })->get();
                                        $card_ext_trans = DB::table('card_transactions')->join('virtual_cards', 'card_transactions.card_id', '=', 'virtual_cards.card_id')->select('card_transactions.*', 'virtual_cards.card_type')->whereBetween('card_transactions.created_at', [$start_date, $end_date])->whereIn('card_transactions.card_id', function ($q) use ($request) {
                                            $q->select('card_id')->from('virtual_cards')->whereIn('user_id', function ($u) use ($request) {
                                                $u->select('id')->from('users')->where('username', $request->username);
                                            });
                                        })->get();
                                        $charity_donations = DB::table('donations')->whereBetween('created_at', [$start_date, $end_date])->where([
                                            'status' => 'confirmed',
                                            'user_id' => function ($q) use ($request) {
                                                $q->select('id')->from('users')->where('username', $request->username);
                                            }
                                        ])->get();
                                        $charity_withdrawals = DB::table('donations')->whereBetween('created_at', [$start_date, $end_date])->where(['status' => 'withdrawn'])->get(); // Withdrawals aren't per-user usually but per-charity

                                    } else {
                                        return response()->json([
                                            'message' => 'Invalid User Username'
                                        ])->setStatusCode(403);
                                    }
                                } else {

                                    return response()->json([
                                        'message' => 'start date and end date required'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'messsage' => ' Username Required'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'message' => 'start date and end date required'
                            ])->setStatusCode(403);
                        }
                    } else {
                        if ((isset($request->from)) and isset($request->to)) {
                            if ((!empty($request->from)) and !empty($request->to)) {
                                $start_date = Carbon::parse($request->from . ' 00:00:00')->toDateTimeString();
                                $end_date = Carbon::parse($request->to . ' 23:59:59')->toDateTimeString();
                                $data_trans = DB::table('data')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $airtime_trans = DB::table('airtime')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $cable_trans = DB::table('cable')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $exam_trans = DB::table('exam')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $bulksms_trans = DB::table('bulksms')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $deposit_trans = DB::table('transactions')->whereBetween('created_at', [$start_date, $end_date])->where(['status' => 'success', 'type' => 'credit', 'category' => 'funding'])->get();
                                $spend_trans = DB::table('message')->where(function ($query) {
                                    $query->where('role', '!=', 'credit');
                                    $query->where('role', '!=', 'transfer');
                                    $query->where('plan_status', '!=', 2);
                                })->whereBetween('habukhan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $cash_trans = DB::table('cash')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $bill_trans = DB::table('bill')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1])->get();
                                $transfer_trans = DB::table('transactions')->whereBetween('created_at', [$start_date, $end_date])->where(['status' => 'success', 'type' => 'debit', 'category' => 'transfer_out'])->get();
                                $card_ext_trans = DB::table('card_transactions')->join('virtual_cards', 'card_transactions.card_id', '=', 'virtual_cards.card_id')->select('card_transactions.*', 'virtual_cards.card_type')->whereBetween('card_transactions.created_at', [$start_date, $end_date])->get();
                                $charity_donations = DB::table('donations')->whereBetween('created_at', [$start_date, $end_date])->where('status', 'confirmed')->get();
                                $charity_withdrawals = DB::table('donations')->whereBetween('created_at', [$start_date, $end_date])->where('status', 'withdrawn')->get();

                            } else {
                                return response()->json([
                                    'message' => 'start date and end date required'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'message' => 'start date and end date required'
                            ])->setStatusCode(403);
                        }
                    }
                    // FOR DATA
                    $mtn_g = 0;
                    $mtn_sme = 0;
                    $mtn_cg = 0;
                    $mtn_g_bal = 0;
                    $mtn_cg_bal = 0;
                    $mtn_sme_bal = 0;

                    $airtel_g = 0;
                    $airtel_sme = 0;
                    $airtel_cg = 0;
                    $airtel_g_bal = 0;
                    $airtel_cg_bal = 0;
                    $airtel_sme_bal = 0;

                    $glo_g = 0;
                    $glo_sme = 0;
                    $glo_cg = 0;
                    $glo_g_bal = 0;
                    $glo_cg_bal = 0;
                    $glo_sme_bal = 0;

                    $mobile_g = 0;
                    $mobile_sme = 0;
                    $mobile_cg = 0;
                    $mobile_g_bal = 0;
                    $mobile_cg_bal = 0;
                    $mobile_sme_bal = 0;

                    $mtn_sme2 = 0;
                    $mtn_sme2_bal = 0;
                    $mtn_datashare = 0;
                    $mtn_datashare_bal = 0;

                    $airtel_sme2 = 0;
                    $airtel_sme2_bal = 0;
                    $airtel_datashare = 0;
                    $airtel_datashare_bal = 0;

                    $glo_sme2 = 0;
                    $glo_sme2_bal = 0;
                    $glo_datashare = 0;
                    $glo_datashare_bal = 0;

                    $mobile_sme2 = 0;
                    $mobile_sme2_bal = 0;
                    $mobile_datashare = 0;
                    $mobile_datashare_bal = 0;
                    foreach ($data_trans as $data) {
                        if ($data->network == 'MTN' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_g += $gb;
                            $mtn_g_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_sme += $gb;
                            $mtn_sme_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_cg += $gb;
                            $mtn_cg_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_g += $gb;
                            $airtel_g_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_sme += $gb;
                            $airtel_sme_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_cg += $gb;
                            $airtel_cg_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_g += $gb;
                            $glo_g_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_sme += $gb;
                            $glo_sme_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_cg += $gb;
                            $glo_cg_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_g += $gb;
                            $mobile_g_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_sme += $gb;
                            $mobile_sme_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_cg += $gb;
                            $mobile_cg_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_sme2 += $gb;
                            $mtn_sme2_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_datashare += $gb;
                            $mtn_datashare_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_sme2 += $gb;
                            $airtel_sme2_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_datashare += $gb;
                            $airtel_datashare_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_sme2 += $gb;
                            $glo_sme2_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_datashare += $gb;
                            $glo_datashare_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_sme2 += $gb;
                            $mobile_sme2_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_datashare += $gb;
                            $mobile_datashare_bal += $data->amount;
                        }
                    }


                    // airtime
                    $mtn_vtu = 0;
                    $mtn_vtu_d = 0;
                    $mtn_sns = 0;
                    $mtn_sns_d = 0;

                    $airtel_vtu = 0;
                    $airtel_vtu_d = 0;
                    $airtel_sns = 0;
                    $airtel_sns_d = 0;

                    $glo_vtu = 0;
                    $glo_vtu_d = 0;
                    $glo_sns = 0;
                    $glo_sns_d = 0;

                    $mobile_vtu = 0;
                    $mobile_vtu_d = 0;
                    $mobile_sns = 0;
                    $mobile_sns_d = 0;
                    foreach ($airtime_trans as $airtime) {
                        if ($airtime->network == 'MTN' and $airtime->network_type == 'VTU') {
                            $mtn_vtu += $airtime->amount;
                            $mtn_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == 'MTN' and $airtime->network_type == 'SNS') {
                            $mtn_sns += $airtime->amount;
                            $mtn_sns_d += $airtime->discount;
                        }

                        if ($airtime->network == 'AIRTEL' and $airtime->network_type == 'VTU') {
                            $airtel_vtu += $airtime->amount;
                            $airtel_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == 'AIRTEL' and $airtime->network_type == 'SNS') {
                            $airtel_sns += $airtime->amount;
                            $airtel_sns_d += $airtime->discount;
                        }

                        if ($airtime->network == 'GLO' and $airtime->network_type == 'VTU') {
                            $glo_vtu += $airtime->amount;
                            $glo_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == 'GLO' and $airtime->network_type == 'SNS') {
                            $glo_sns += $airtime->amount;
                            $glo_sns_d += $airtime->discount;
                        }

                        if ($airtime->network == '9MOBILE' and $airtime->network_type == 'VTU') {
                            $mobile_vtu += $airtime->amount;
                            $mobile_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == '9MOBILE' and $airtime->network_type == 'SNS') {
                            $mobile_sns += $airtime->amount;
                            $mobile_sns_d += $airtime->discount;
                        }
                    }
                    // cable
                    $dstv = 0;
                    $dstv_c = 0;
                    $gotv = 0;
                    $gotv_c = 0;
                    $startime = 0;
                    $startime_c = 0;
                    foreach ($cable_trans as $cable) {
                        if ($cable->cable_name == 'DSTV') {
                            $dstv += $cable->amount;
                            $dstv_c += $cable->charges;
                        }
                        if ($cable->cable_name == 'GOTV') {
                            $gotv += $cable->amount;
                            $gotv_c += $cable->charges;
                        }
                        if ($cable->cable_name == 'STARTIME') {
                            $startime += $cable->amount;
                            $startime_c += $cable->charges;
                        }
                    }
                    // exam
                    $waec = 0;
                    $waec_q = 0;
                    $neco = 0;
                    $neco_q = 0;
                    $nabteb = 0;
                    $nabteb_q = 0;
                    foreach ($exam_trans as $exam) {
                        if ($exam->exam_name == 'WAEC') {
                            $waec += $exam->amount;
                            $waec_q += $exam->quantity;
                        }
                        if ($exam->exam_name == 'NECO') {
                            $neco += $exam->amount;
                            $neco_q += $exam->quantity;
                        }
                        if ($exam->exam_name == 'NABTEB') {
                            $nabteb += $exam->amount;
                            $nabteb_q += $exam->quantity;
                        }
                    }
                    // bulksms
                    $bulksms = 0;
                    foreach ($bulksms_trans as $bulk) {
                        $bulksms += $bulk->amount;
                    }
                    // bill
                    $bill = 0;
                    foreach ($bill_trans as $d) {
                        $bill += $d->amount;
                    }
                    // airtime 2 cash
                    $cash = 0;
                    $cash_pay = 0;
                    foreach ($cash_trans as $d) {
                        $cash += $d->amount;
                        $cash_pay += $d->amount_credit;
                    }
                    // deposit
                    $deposit_amount = 0;
                    $deposit_charges = 0;
                    foreach ($deposit_trans as $deposit) {
                        $deposit_amount += $deposit->amount;
                        $deposit_charges += $deposit->fee;
                    }
                    $transfer_total = 0;
                    $transfer_charges = 0;
                    foreach ($transfer_trans as $trans) {
                        $transfer_total += $trans->amount;
                        $transfer_charges += $trans->fee;
                    }

                    $money_spent = 0;
                    $card_creation_count = 0;
                    $card_creation_amount = 0;
                    $card_funding_amount = 0;

                    foreach ($spend_trans as $spend) {
                        $money_spent += $spend->amount;
                        // Check if role property exists before accessing
                        if (isset($spend->role)) {
                            if ($spend->role == 'card_creation') {
                                $card_creation_count++;
                                $card_creation_amount += $spend->amount;
                            }
                            if ($spend->role == 'card_funding') {
                                $card_funding_amount += $spend->amount;
                            }
                        }
                    }

                    // External Card Usage (from card_transactions table)
                    $card_usage_volume = 0;
                    if (isset($card_ext_trans)) {
                        foreach ($card_ext_trans as $card_tx) {
                            // Convert to NGN if USD
                            $tx_amount = $card_tx->amount;
                            if (isset($card_tx->currency) && $card_tx->currency == 'USD') {
                                $tx_amount = $tx_amount * $ngn_rate;
                            } else {
                                // Fallback: Check card_type if currency is missing in transaction
                                if (isset($card_tx->card_type) && $card_tx->card_type == 'USD') {
                                    $tx_amount = $tx_amount * $ngn_rate;
                                }
                            }
                            $card_usage_volume += $tx_amount;
                        }
                    }

                    $charity_donation_amount = 0;
                    if (isset($charity_donations)) {
                        foreach ($charity_donations as $donation) {
                            $charity_donation_amount += $donation->amount;
                        }
                    }
                    $charity_withdrawal_amount = 0;
                    if (isset($charity_withdrawals)) {
                        foreach ($charity_withdrawals as $withdrawal) {
                            $charity_withdrawal_amount += $withdrawal->amount;
                        }
                    }

                    $habukhan_in = $deposit_amount + $charity_donation_amount;
                    $money_spent = $money_spent + $transfer_total + $transfer_charges + $charity_withdrawal_amount;
                    $habukhan_out = $money_spent;
                    $total_m = $habukhan_in + $habukhan_out;
                    if ($total_m != 0) {
                        $habukhan_in_trans = ($habukhan_in / $total_m) * 100;
                        $habukhan_out_trans = ($habukhan_out / $total_m) * 100;
                    } else {
                        $habukhan_in_trans = 0;
                        $habukhan_out_trans = 0;
                    }

                    $calculate_mtn_cg = '0GB';
                    $calculate_mtn_g = '0GB';
                    $calculate_mtn_sme = '0GB';

                    $calculate_airtel_cg = '0GB';
                    $calculate_airtel_g = '0GB';
                    $calculate_airtel_sme = '0GB';

                    $calculate_glo_cg = '0GB';
                    $calculate_glo_g = '0GB';
                    $calculate_glo_sme = '0GB';

                    $calculate_mobile_cg = '0GB';
                    $calculate_mobile_g = '0GB';
                    $calculate_mobile_sme = '0GB';

                    $calculate_mtn_sme2 = '0GB';
                    $calculate_mtn_datashare = '0GB';
                    $calculate_airtel_sme2 = '0GB';
                    $calculate_airtel_datashare = '0GB';
                    $calculate_glo_sme2 = '0GB';
                    $calculate_glo_datashare = '0GB';
                    $calculate_mobile_sme2 = '0GB';
                    $calculate_mobile_datashare = '0GB';

                    if ($mtn_cg >= 1024) {
                        $calculate_mtn_cg = number_format($mtn_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_cg = number_format($mtn_cg, 3) . 'GB';
                    }
                    if ($mtn_g >= 1024) {
                        $calculate_mtn_g = number_format($mtn_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_g = number_format($mtn_g, 3) . 'GB';
                    }
                    if ($mtn_sme >= 1024) {
                        $calculate_mtn_sme = number_format($mtn_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_sme = number_format($mtn_sme, 3) . 'GB';
                    }

                    if ($mtn_sme2 >= 1024) {
                        $calculate_mtn_sme2 = number_format($mtn_sme2 / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_sme2 = number_format($mtn_sme2, 3) . 'GB';
                    }
                    if ($mtn_datashare >= 1024) {
                        $calculate_mtn_datashare = number_format($mtn_datashare / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_datashare = number_format($mtn_datashare, 3) . 'GB';
                    }

                    if ($glo_cg >= 1024) {
                        $calculate_glo_cg = number_format($glo_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_cg = number_format($glo_cg, 3) . 'GB';
                    }
                    if ($glo_g >= 1024) {
                        $calculate_glo_g = number_format($glo_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_g = number_format($glo_g, 3) . 'GB';
                    }
                    if ($glo_sme >= 1024) {
                        $calculate_glo_sme = number_format($glo_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_sme = number_format($glo_sme, 3) . 'GB';
                    }

                    if ($glo_sme2 >= 1024) {
                        $calculate_glo_sme2 = number_format($glo_sme2 / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_sme2 = number_format($glo_sme2, 3) . 'GB';
                    }
                    if ($glo_datashare >= 1024) {
                        $calculate_glo_datashare = number_format($glo_datashare / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_datashare = number_format($glo_datashare, 3) . 'GB';
                    }


                    if ($airtel_cg >= 1024) {
                        $calculate_airtel_cg = number_format($airtel_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_cg = number_format($airtel_cg, 3) . 'GB';
                    }
                    if ($airtel_g >= 1024) {
                        $calculate_airtel_g = number_format($airtel_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_g = number_format($airtel_g, 3) . 'GB';
                    }
                    if ($airtel_sme >= 1024) {
                        $calculate_airtel_sme = number_format($airtel_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_sme = number_format($airtel_sme, 3) . 'GB';
                    }

                    if ($airtel_sme2 >= 1024) {
                        $calculate_airtel_sme2 = number_format($airtel_sme2 / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_sme2 = number_format($airtel_sme2, 3) . 'GB';
                    }
                    if ($airtel_datashare >= 1024) {
                        $calculate_airtel_datashare = number_format($airtel_datashare / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_datashare = number_format($airtel_datashare, 3) . 'GB';
                    }

                    if ($mobile_cg >= 1024) {
                        $calculate_mobile_cg = number_format($mobile_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_cg = number_format($mobile_cg, 3) . 'GB';
                    }
                    if ($mobile_g >= 1024) {
                        $calculate_mobile_g = number_format($mobile_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_g = number_format($mobile_g, 3) . 'GB';
                    }
                    if ($mobile_sme >= 1024) {
                        $calculate_mobile_sme = number_format($mobile_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_sme = number_format($mobile_sme, 3) . 'GB';
                    }

                    if ($mobile_sme2 >= 1024) {
                        $calculate_mobile_sme2 = number_format($mobile_sme2 / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_sme2 = number_format($mobile_sme2, 3) . 'GB';
                    }
                    if ($mobile_datashare >= 1024) {
                        $calculate_mobile_datashare = number_format($mobile_datashare / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_datashare = number_format($mobile_datashare, 3) . 'GB';
                    }

                    return response()->json([
                        'status' => 'success',
                        'charity_donations' => number_format($charity_donation_amount, 2),
                        'charity_withdrawals' => number_format($charity_withdrawal_amount, 2),
                        // data
                        'mtn_cg' => $calculate_mtn_cg,
                        'mtn_cg_bal' => number_format($mtn_cg_bal, 2),
                        'mtn_sme_bal' => number_format($mtn_sme_bal, 2),
                        'mtn_g_bal' => number_format($mtn_g_bal, 2),
                        'mtn_sme' => $calculate_mtn_sme,
                        'mtn_g' => $calculate_mtn_g,

                        'airtel_cg' => $calculate_airtel_cg,
                        'airtel_cg_bal' => number_format($airtel_cg_bal, 2),
                        'airtel_sme_bal' => number_format($airtel_sme_bal, 2),
                        'airtel_g_bal' => number_format($airtel_g_bal, 2),
                        'airtel_sme' => $calculate_airtel_sme,
                        'airtel_g' => $calculate_airtel_g,

                        'glo_cg' => $calculate_glo_cg,
                        'glo_cg_bal' => number_format($glo_cg_bal, 2),
                        'glo_sme_bal' => number_format($glo_sme_bal, 2),
                        'glo_g_bal' => number_format($glo_g_bal, 2),
                        'glo_sme' => $calculate_glo_sme,
                        'glo_g' => $calculate_glo_g,

                        'mobile_cg' => $calculate_mobile_cg,
                        'mobile_cg_bal' => number_format($mobile_cg_bal, 2),
                        'mobile_sme_bal' => number_format($mobile_sme_bal, 2),
                        'mobile_g_bal' => number_format($mobile_g_bal, 2),
                        'mobile_sme' => $calculate_mobile_sme,
                        'mobile_g' => $calculate_mobile_g,

                        'mtn_sme2' => $calculate_mtn_sme2,
                        'mtn_sme2_bal' => number_format($mtn_sme2_bal, 2),
                        'mtn_datashare' => $calculate_mtn_datashare,

                        // Virtual Cards
                        'card_creation_count' => number_format($card_creation_count),
                        'card_creation_amount' => number_format($card_creation_amount, 2),
                        'card_funding_amount' => number_format($card_funding_amount, 2),
                        'card_usage_volume' => number_format($card_usage_volume, 2),


                        'airtel_sme2' => $calculate_airtel_sme2,
                        'airtel_sme2_bal' => number_format($airtel_sme2_bal, 2),
                        'airtel_datashare' => $calculate_airtel_datashare,
                        'airtel_datashare_bal' => number_format($airtel_datashare_bal, 2),

                        'glo_sme2' => $calculate_glo_sme2,
                        'glo_sme2_bal' => number_format($glo_sme2_bal, 2),
                        'glo_datashare' => $calculate_glo_datashare,
                        'glo_datashare_bal' => number_format($glo_datashare_bal, 2),

                        'mobile_sme2' => $calculate_mobile_sme2,
                        'mobile_sme2_bal' => number_format($mobile_sme2_bal, 2),
                        'mobile_datashare' => $calculate_mobile_datashare,
                        'mobile_datashare_bal' => number_format($mobile_datashare_bal, 2),

                        // airtime
                        'mtn_vtu' => number_format($mtn_vtu, 2),
                        'mtn_vtu_d' => number_format($mtn_vtu_d, 2),
                        'mtn_sns' => number_format($mtn_sns, 2),
                        'mtn_sns_d' => number_format($mtn_sns_d, 2),

                        'airtel_vtu' => number_format($airtel_vtu, 2),
                        'airtel_vtu_d' => number_format($airtel_vtu_d, 2),
                        'airtel_sns' => number_format($airtel_sns, 2),
                        'airtel_sns_d' => number_format($airtel_sns_d, 2),

                        'glo_vtu' => number_format($glo_vtu, 2),
                        'glo_vtu_d' => number_format($glo_vtu_d, 2),
                        'glo_sns' => number_format($glo_sns, 2),
                        'glo_sns_d' => number_format($glo_sns_d, 2),

                        'mobile_vtu' => number_format($mobile_vtu, 2),
                        'mobile_vtu_d' => number_format($mobile_vtu_d, 2),
                        'mobile_sns' => number_format($mobile_sns, 2),
                        'mobile_sns_d' => number_format($mobile_sns_d, 2),

                        // cable
                        'dstv' => number_format($dstv, 2),
                        'dstv_c' => number_format($dstv_c, 2),
                        'gotv' => number_format($gotv, 2),
                        'gotv_c' => number_format($gotv_c, 2),
                        'startime' => number_format($startime, 2),
                        'startime_c' => number_format($startime_c, 2),

                        // exam
                        'waec' => number_format($waec, 2),
                        'waec_q' => number_format($waec_q),
                        'neco' => number_format($neco, 2),
                        'neco_q' => number_format($neco_q),
                        'nabteb' => number_format($nabteb, 2),
                        'nabteb_q' => number_format($nabteb_q),

                        // bulksms
                        'bulksms' => number_format($bulksms, 2),

                        // bill
                        'bill' => number_format($bill, 2),

                        // airtime 2 cash
                        'cash_amount' => number_format($cash, 2),
                        'cash_pay' => number_format($cash_pay, 2),

                        // deposit
                        'deposit_amount' => number_format($deposit_amount, 2),
                        'deposit_charges' => number_format($deposit_charges, 2),
                        'deposit_trans' => number_format($habukhan_in_trans, 1),
                        'spend_trans' => number_format($habukhan_out_trans, 1),
                        'spend_amount' => number_format($money_spent, 2),
                        'transfer_amount' => number_format($transfer_total, 2),
                        'transfer_charges' => number_format($transfer_charges, 2)
                    ]);
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

    public function User(Request $request, $id = null)
    {
        // Prioritize Authorization header to support tokens ensuring special chars (like |) don't break logic via URL
        $headerToken = $request->header('Authorization');
        if (!empty($headerToken) && $headerToken !== 'Bearer null') {
            $token = $headerToken;
        } else {
            $token = $id ?? $request->id ?? $request->route('id');
        }
        if (empty($token)) {
            $authHeader = $request->header('Authorization');
            if (strpos($authHeader, 'Token ') === 0) {
                $token = substr($authHeader, 6);
            } elseif (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
            }
        }

        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (true) {
            if (!empty($token)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifyapptoken($token)]);
                if ($check_user->count() == 1) {
                    $adex_username = $check_user->first();
                    $real_username = $adex_username->username;
                    // all here
                    if ($request->status == 'TODAY') {
                        $data_trans = DB::table('data')->select('network', 'network_type', 'plan_name', 'amount')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $airtime_trans = DB::table('airtime')->select('network', 'network_type', 'amount', 'discount')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cable_trans = DB::table('cable')->select('cable_name', 'amount', 'charges')->whereDate('date', Carbon::now("Africa/Lagos"))->where(['status' => 1, 'username' => $real_username])->get();
                        $exam_trans = DB::table('exam')->select('exam_name', 'amount', 'quantity')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bulksms_trans = DB::table('bulksms')->select('amount')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $deposit_trans = DB::table('transactions')->select('amount', 'fee as charges')->whereDate('created_at', Carbon::now("Africa/Lagos"))->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->select('amount')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->whereDate('habukhan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cash_trans = DB::table('cash')->select('amount', 'amount_credit')->whereDate('plan_date', Carbon::now("Africa/Lagos"))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bill_trans = DB::table('bill')->select('amount')->whereDate('date', Carbon::now("Africa/Lagos"))->where(['status' => 1, 'username' => $real_username])->get();
                        $transfer_trans = DB::table('transactions')->select('amount', 'fee as charge')->whereDate('created_at', Carbon::now("Africa/Lagos"))->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $referral_trans = DB::table('message')->select('amount')->where('message', 'like', 'Referral Earning%')->whereDate('habukhan_date', Carbon::now("Africa/Lagos"))->where(['username' => $real_username])->get();
                        $charity_donations = DB::table('donations')->select('amount')->whereDate('created_at', Carbon::now("Africa/Lagos"))->where(['user_id' => $adex_username->id, 'status' => 'confirmed'])->get();
                        $charity_withdrawals = collect([]); // Users don't have withdrawals usually
                    } else if ($request->status == '7DAYS') {
                        $data_trans = DB::table('data')->select('network', 'network_type', 'plan_name', 'amount')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $airtime_trans = DB::table('airtime')->select('network', 'network_type', 'amount', 'discount')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cable_trans = DB::table('cable')->select('cable_name', 'amount', 'charges')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $exam_trans = DB::table('exam')->select('exam_name', 'amount', 'quantity')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bulksms_trans = DB::table('bulksms')->select('amount')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $deposit_trans = DB::table('transactions')->select('amount', 'fee as charges')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->select('amount')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->whereDate('habukhan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cash_trans = DB::table('cash')->select('amount', 'amount_credit')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bill_trans = DB::table('bill')->select('amount')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $transfer_trans = DB::table('transactions')->select('amount', 'fee as charge')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $referral_trans = DB::table('message')->select('amount')->where('message', 'like', 'Referral Earning%')->whereDate('habukhan_date', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['username' => $real_username])->get();
                        $charity_donations = DB::table('donations')->select('amount')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(7))->where(['user_id' => $adex_username->id, 'status' => 'confirmed'])->get();
                        $charity_withdrawals = collect([]);
                    } else if ($request->status == '30DAYS') {
                        $data_trans = DB::table('data')->select('network', 'network_type', 'plan_name', 'amount')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $airtime_trans = DB::table('airtime')->select('network', 'network_type', 'amount', 'discount')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cable_trans = DB::table('cable')->select('cable_name', 'amount', 'charges')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $exam_trans = DB::table('exam')->select('exam_name', 'amount', 'quantity')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bulksms_trans = DB::table('bulksms')->select('amount')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $deposit_trans = DB::table('transactions')->select('amount', 'fee as charges')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->select('amount')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->whereDate('habukhan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cash_trans = DB::table('cash')->select('amount', 'amount_credit')->whereDate('plan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bill_trans = DB::table('bill')->select('amount')->whereDate('date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $transfer_trans = DB::table('transactions')->select('amount', 'fee as charge')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $referral_trans = DB::table('message')->select('amount')->where('message', 'like', 'Referral Earning%')->whereDate('habukhan_date', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['username' => $real_username])->get();
                        $charity_donations = DB::table('donations')->select('amount')->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays(30))->where(['user_id' => $adex_username->id, 'status' => 'confirmed'])->get();
                        $charity_withdrawals = collect([]);
                    } else if ($request->status == 'ALL TIME') {
                        $data_trans = DB::table('data')->select('network', 'network_type', 'plan_name', 'amount')->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $airtime_trans = DB::table('airtime')->select('network', 'network_type', 'amount', 'discount')->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cable_trans = DB::table('cable')->select('cable_name', 'amount', 'charges')->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $exam_trans = DB::table('exam')->select('exam_name', 'amount', 'quantity')->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bulksms_trans = DB::table('bulksms')->select('amount')->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $deposit_trans = DB::table('transactions')->select('amount', 'fee as charges')->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'credit', 'category' => 'funding'])->get();
                        $spend_trans = DB::table('message')->select('amount')->where(function ($query) {
                            $query->where('role', '!=', 'credit');
                            $query->where('role', '!=', 'transfer');
                            $query->where('plan_status', '!=', 2);
                        })->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $cash_trans = DB::table('cash')->select('amount', 'amount_credit')->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $bill_trans = DB::table('bill')->select('amount')->where(['plan_status' => 1, 'username' => $real_username])->get();
                        $transfer_trans = DB::table('transactions')->select('amount', 'fee as charge')->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'debit', 'category' => 'transfer_out'])->get();
                        $referral_trans = DB::table('message')->select('amount')->where('message', 'like', 'Referral Earning%')->where(['username' => $real_username])->get();
                        $charity_donations = DB::table('donations')->select('amount')->where(['user_id' => $adex_username->id, 'status' => 'confirmed'])->get();
                        $charity_withdrawals = collect([]);
                    } else {
                        if ((isset($request->from)) and isset($request->to)) {
                            if ((!empty($request->from)) and !empty($request->to)) {
                                $start_date = Carbon::parse($request->from . ' 00:00:00')->toDateTimeString();
                                $end_date = Carbon::parse($request->to . ' 23:59:59')->toDateTimeString();
                                $data_trans = DB::table('data')->select('network', 'network_type', 'plan_name', 'amount')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $airtime_trans = DB::table('airtime')->select('network', 'network_type', 'amount', 'discount')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $cable_trans = DB::table('cable')->select('cable_name', 'amount', 'charges')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $exam_trans = DB::table('exam')->select('exam_name', 'amount', 'quantity')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $bulksms_trans = DB::table('bulksms')->select('amount')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $deposit_trans = DB::table('transactions')->select('amount', 'fee as charges')->whereBetween('created_at', [$start_date, $end_date])->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'credit', 'category' => 'funding'])->get();
                                $spend_trans = DB::table('message')->select('amount')->where(function ($query) {
                                    $query->where('role', '!=', 'credit');
                                    $query->where('role', '!=', 'transfer');
                                    $query->where('plan_status', '!=', 2);
                                })->whereBetween('habukhan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $cash_trans = DB::table('cash')->select('amount', 'amount_credit')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $bill_trans = DB::table('bill')->select('amount')->whereBetween('plan_date', [$start_date, $end_date])->where(['plan_status' => 1, 'username' => $real_username])->get();
                                $transfer_trans = DB::table('transactions')->select('amount', 'fee as charge')->whereBetween('created_at', [$start_date, $end_date])->where(['status' => 'success', 'user_id' => $adex_username->id, 'type' => 'debit', 'category' => 'transfer_out'])->get();
                                $referral_trans = DB::table('message')->select('amount')->where('message', 'like', 'Referral Earning%')->whereBetween('habukhan_date', [$start_date, $end_date])->where(['username' => $real_username])->get();
                                $charity_donations = DB::table('donations')->select('amount')->whereBetween('created_at', [$start_date, $end_date])->where(['user_id' => $adex_username->id, 'status' => 'confirmed'])->get();
                                $charity_withdrawals = collect([]);
                            } else {

                                return response()->json([
                                    'message' => 'start date and end date required'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'message' => 'start date and end date required'
                            ])->setStatusCode(403);
                        }
                    }
                    // FOR DATA
                    $mtn_g = 0;
                    $mtn_sme = 0;
                    $mtn_cg = 0;
                    $mtn_g_bal = 0;
                    $mtn_cg_bal = 0;
                    $mtn_sme_bal = 0;

                    $airtel_g = 0;
                    $airtel_sme = 0;
                    $airtel_cg = 0;
                    $airtel_g_bal = 0;
                    $airtel_cg_bal = 0;
                    $airtel_sme_bal = 0;

                    $glo_g = 0;
                    $glo_sme = 0;
                    $glo_cg = 0;
                    $glo_g_bal = 0;
                    $glo_cg_bal = 0;
                    $glo_sme_bal = 0;

                    $mobile_g = 0;
                    $mobile_sme = 0;
                    $mobile_cg = 0;
                    $mobile_g_bal = 0;
                    $mobile_cg_bal = 0;
                    $mobile_sme_bal = 0;

                    $mtn_sme2 = 0;
                    $mtn_sme2_bal = 0;
                    $mtn_datashare = 0;
                    $mtn_datashare_bal = 0;

                    $airtel_sme2 = 0;
                    $airtel_sme2_bal = 0;
                    $airtel_datashare = 0;
                    $airtel_datashare_bal = 0;

                    $glo_sme2 = 0;
                    $glo_sme2_bal = 0;
                    $glo_datashare = 0;
                    $glo_datashare_bal = 0;

                    $mobile_sme2 = 0;
                    $mobile_sme2_bal = 0;
                    $mobile_datashare = 0;
                    $mobile_datashare_bal = 0;
                    foreach ($data_trans as $data) {
                        if ($data->network == 'MTN' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_g += $gb;
                            $mtn_g_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_sme += $gb;
                            $mtn_sme_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_cg += $gb;
                            $mtn_cg_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_g += $gb;
                            $airtel_g_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_sme += $gb;
                            $airtel_sme_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_cg += $gb;
                            $airtel_cg_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_g += $gb;
                            $glo_g_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_sme += $gb;
                            $glo_sme_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_cg += $gb;
                            $glo_cg_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_g += $gb;
                            $mobile_g_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'SME') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_sme += $gb;
                            $mobile_sme_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'COOPERATE GIFTING') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_cg += $gb;
                            $mobile_cg_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_sme2 += $gb;
                            $mtn_sme2_bal += $data->amount;
                        } else if ($data->network == 'MTN' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mtn_datashare += $gb;
                            $mtn_datashare_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_sme2 += $gb;
                            $airtel_sme2_bal += $data->amount;
                        } else if ($data->network == 'AIRTEL' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $airtel_datashare += $gb;
                            $airtel_datashare_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_sme2 += $gb;
                            $glo_sme2_bal += $data->amount;
                        } else if ($data->network == 'GLO' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $glo_datashare += $gb;
                            $glo_datashare_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'SME 2') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_sme2 += $gb;
                            $mobile_sme2_bal += $data->amount;
                        } else if ($data->network == '9MOBILE' and $data->network_type == 'DATASHARE') {
                            $plans = $data->plan_name;
                            $check_gb = substr($plans, -2);
                            if ($check_gb == 'MB') {
                                $mb = rtrim($plans, "MB");
                                $gb = $mb / 1024;
                            } elseif ($check_gb == 'GB') {
                                $gb = rtrim($plans, "GB");
                            } elseif ($check_gb == 'TB') {
                                $tb = rtrim($plans, 'TB');
                                $gb = ceil($tb * 1024);
                            }
                            $mobile_datashare += $gb;
                            $mobile_datashare_bal += $data->amount;
                        }
                    }

                    // airtime
                    $mtn_vtu = 0;
                    $mtn_vtu_d = 0;
                    $mtn_sns = 0;
                    $mtn_sns_d = 0;

                    $airtel_vtu = 0;
                    $airtel_vtu_d = 0;
                    $airtel_sns = 0;
                    $airtel_sns_d = 0;

                    $glo_vtu = 0;
                    $glo_vtu_d = 0;
                    $glo_sns = 0;
                    $glo_sns_d = 0;

                    $mobile_vtu = 0;
                    $mobile_vtu_d = 0;
                    $mobile_sns = 0;
                    $mobile_sns_d = 0;
                    foreach ($airtime_trans as $airtime) {
                        if ($airtime->network == 'MTN' and $airtime->network_type == 'VTU') {
                            $mtn_vtu += $airtime->amount;
                            $mtn_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == 'MTN' and $airtime->network_type == 'SNS') {
                            $mtn_sns += $airtime->amount;
                            $mtn_sns_d += $airtime->discount;
                        }

                        if ($airtime->network == 'AIRTEL' and $airtime->network_type == 'VTU') {
                            $airtel_vtu += $airtime->amount;
                            $airtel_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == 'AIRTEL' and $airtime->network_type == 'SNS') {
                            $airtel_sns += $airtime->amount;
                            $airtel_sns_d += $airtime->discount;
                        }

                        if ($airtime->network == 'GLO' and $airtime->network_type == 'VTU') {
                            $glo_vtu += $airtime->amount;
                            $glo_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == 'GLO' and $airtime->network_type == 'SNS') {
                            $glo_sns += $airtime->amount;
                            $glo_sns_d += $airtime->discount;
                        }

                        if ($airtime->network == '9MOBILE' and $airtime->network_type == 'VTU') {
                            $mobile_vtu += $airtime->amount;
                            $mobile_vtu_d += $airtime->discount;
                        }
                        if ($airtime->network == '9MOBILE' and $airtime->network_type == 'SNS') {
                            $mobile_sns += $airtime->amount;
                            $mobile_sns_d += $airtime->discount;
                        }
                    }
                    // cable
                    $dstv = 0;
                    $dstv_c = 0;
                    $gotv = 0;
                    $gotv_c = 0;
                    $startime = 0;
                    $startime_c = 0;
                    foreach ($cable_trans as $cable) {
                        if ($cable->cable_name == 'DSTV') {
                            $dstv += $cable->amount;
                            $dstv_c += $cable->charges;
                        }
                        if ($cable->cable_name == 'GOTV') {
                            $gotv += $cable->amount;
                            $gotv_c += $cable->charges;
                        }
                        if ($cable->cable_name == 'STARTIME') {
                            $startime += $cable->amount;
                            $startime_c += $cable->charges;
                        }
                    }
                    // exam
                    $waec = 0;
                    $waec_q = 0;
                    $neco = 0;
                    $neco_q = 0;
                    $nabteb = 0;
                    $nabteb_q = 0;
                    foreach ($exam_trans as $exam) {
                        if ($exam->exam_name == 'WAEC') {
                            $waec += $exam->amount;
                            $waec_q += $exam->quantity;
                        }
                        if ($exam->exam_name == 'NECO') {
                            $neco += $exam->amount;
                            $neco_q += $exam->quantity;
                        }
                        if ($exam->exam_name == 'NABTEB') {
                            $nabteb += $exam->amount;
                            $nabteb_q += $exam->quantity;
                        }
                    }
                    // bulksms
                    $bulksms = 0;
                    foreach ($bulksms_trans as $bulk) {
                        $bulksms += $bulk->amount;
                    }
                    // bill
                    $bill = 0;
                    foreach ($bill_trans as $d) {
                        $bill += $d->amount;
                    }
                    // airtime 2 cash
                    $cash = 0;
                    $cash_pay = 0;
                    foreach ($cash_trans as $d) {
                        $cash += $d->amount;
                        $cash_pay += $d->amount_credit;
                    }
                    // deposit
                    $deposit_amount = 0;
                    $deposit_charges = 0;
                    foreach ($deposit_trans as $deposit) {
                        $deposit_amount += $deposit->amount;
                        $deposit_charges += $deposit->charges;
                    }
                    $money_spent = 0;
                    foreach ($spend_trans as $spend) {
                        $money_spent += $spend->amount;
                    }
                    $transfer_total = 0;
                    $transfer_charges = 0;
                    foreach ($transfer_trans as $trans) {
                        $transfer_total += $trans->amount;
                        $transfer_charges += $trans->charge;
                    }
                    $habukhan_in = $deposit_amount;
                    $money_spent = $money_spent + $transfer_total + $transfer_charges;
                    $habukhan_out = $money_spent;
                    $total_m = $habukhan_in + $habukhan_out;
                    if ($total_m != 0) {
                        $habukhan_in_trans = ($habukhan_in / $total_m) * 100;
                        $habukhan_out_trans = ($habukhan_out / $total_m) * 100;
                    } else {
                        $habukhan_in_trans = 0;
                        $habukhan_out_trans = 0;
                    }

                    $calculate_mtn_cg = '0GB';
                    $calculate_mtn_g = '0GB';
                    $calculate_mtn_sme = '0GB';

                    $calculate_airtel_cg = '0GB';
                    $calculate_airtel_g = '0GB';
                    $calculate_airtel_sme = '0GB';

                    $calculate_glo_cg = '0GB';
                    $calculate_glo_g = '0GB';
                    $calculate_glo_sme = '0GB';

                    $calculate_mobile_cg = '0GB';
                    $calculate_mobile_g = '0GB';
                    $calculate_mobile_sme = '0GB';

                    if ($mtn_cg >= 1024) {
                        $calculate_mtn_cg = number_format($mtn_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_cg = number_format($mtn_cg, 3) . 'GB';
                    }
                    if ($mtn_g >= 1024) {
                        $calculate_mtn_g = number_format($mtn_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_g = number_format($mtn_g, 3) . 'GB';
                    }
                    if ($mtn_sme >= 1024) {
                        $calculate_mtn_sme = number_format($mtn_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_mtn_sme = number_format($mtn_sme, 3) . 'GB';
                    }

                    if ($glo_cg >= 1024) {
                        $calculate_glo_cg = number_format($glo_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_cg = number_format($glo_cg, 3) . 'GB';
                    }
                    if ($glo_g >= 1024) {
                        $calculate_glo_g = number_format($glo_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_g = number_format($glo_g, 3) . 'GB';
                    }
                    if ($glo_sme >= 1024) {
                        $calculate_glo_sme = number_format($glo_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_glo_sme = number_format($glo_sme, 3) . 'GB';
                    }


                    if ($airtel_cg >= 1024) {
                        $calculate_airtel_cg = number_format($airtel_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_cg = number_format($airtel_cg, 3) . 'GB';
                    }
                    if ($airtel_g >= 1024) {
                        $calculate_airtel_g = number_format($airtel_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_g = number_format($airtel_g, 3) . 'GB';
                    }
                    if ($airtel_sme >= 1024) {
                        $calculate_airtel_sme = number_format($airtel_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_airtel_sme = number_format($airtel_sme, 3) . 'GB';
                    }

                    if ($mobile_cg >= 1024) {
                        $calculate_mobile_cg = number_format($mobile_cg / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_cg = number_format($mobile_cg, 3) . 'GB';
                    }
                    if ($mobile_g >= 1024) {
                        $calculate_mobile_g = number_format($mobile_g / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_g = number_format($mobile_g, 3) . 'GB';
                    }
                    if ($mobile_sme >= 1024) {
                        $calculate_mobile_sme = number_format($mobile_sme / 1024, 3) . 'TB';
                    } else {
                        $calculate_mobile_sme = number_format($mobile_sme, 3) . 'GB';
                    }


                    $airtime_data_total = $mtn_cg_bal + $mtn_sme_bal + $mtn_g_bal + $airtel_cg_bal + $airtel_sme_bal + $airtel_g_bal + $glo_cg_bal + $glo_sme_bal + $glo_g_bal + $mobile_cg_bal + $mobile_sme_bal + $mobile_g_bal + $mtn_sme2_bal + $mtn_datashare_bal + $airtel_sme2_bal + $airtel_datashare_bal + $glo_sme2_bal + $glo_datashare_bal + $mobile_sme2_bal + $mobile_datashare_bal + $mtn_vtu + $mtn_sns + $airtel_vtu + $airtel_sns + $glo_vtu + $glo_sns + $mobile_vtu + $mobile_sns;
                    $bills_utilities_total = $dstv + $gotv + $startime + $bill;
                    $transfers_total = $transfer_total + $transfer_charges;
                    $education_total = $waec + $neco + $nabteb;
                    $specifically_tracked = $airtime_data_total + $bills_utilities_total + $transfers_total + $education_total + $bulksms + $cash_pay;
                    $others_total = $bulksms + $cash_pay + max(0, ($money_spent - $transfers_total - ($specifically_tracked - $transfers_total - $bulksms - $cash_pay)));

                    $charity_donation_amount = 0;
                    if (isset($charity_donations)) {
                        foreach ($charity_donations as $donation) {
                            $charity_donation_amount += $donation->amount;
                        }
                    }
                    $charity_withdrawal_amount = 0;
                    if (isset($charity_withdrawals)) {
                        foreach ($charity_withdrawals as $withdrawal) {
                            $charity_withdrawal_amount += $withdrawal->amount;
                        }
                    }

                    $referral_total = 0;
                    foreach ($referral_trans as $ref) {
                        $referral_total += $ref->amount;
                    }

                    return response()->json([
                        'status' => 'success',
                        'charity_donations' => number_format($charity_donation_amount, 2),
                        'charity_withdrawals' => number_format($charity_withdrawal_amount, 2),
                        // data
                        'mtn_cg' => $calculate_mtn_cg,
                        'mtn_cg_bal' => number_format($mtn_cg_bal, 2),
                        'mtn_sme_bal' => number_format($mtn_sme_bal, 2),
                        'mtn_g_bal' => number_format($mtn_g_bal, 2),
                        'mtn_sme' => $calculate_mtn_sme,
                        'mtn_g' => $calculate_mtn_g,

                        'airtel_cg' => $calculate_airtel_cg,
                        'airtel_cg_bal' => number_format($airtel_cg_bal, 2),
                        'airtel_sme_bal' => number_format($airtel_sme_bal, 2),
                        'airtel_g_bal' => number_format($airtel_g_bal, 2),
                        'airtel_sme' => $calculate_airtel_sme,
                        'airtel_g' => $calculate_airtel_g,

                        'glo_cg' => $calculate_glo_cg,
                        'glo_cg_bal' => number_format($glo_cg_bal, 2),
                        'glo_sme_bal' => number_format($glo_sme_bal, 2),
                        'glo_g_bal' => number_format($glo_g_bal, 2),
                        'glo_sme' => $calculate_glo_sme,
                        'glo_g' => $calculate_glo_g,

                        'mobile_cg' => $calculate_mobile_cg,
                        'mobile_cg_bal' => number_format($mobile_cg_bal, 2),
                        'mobile_sme_bal' => number_format($mobile_sme_bal, 2),
                        'mobile_g_bal' => number_format($mobile_g_bal, 2),
                        'mobile_sme' => $calculate_mobile_sme,
                        'mobile_g' => $calculate_mobile_g,
                        // airtime
                        'mtn_vtu' => number_format($mtn_vtu, 2),
                        'mtn_vtu_d' => number_format($mtn_vtu_d, 2),
                        'mtn_sns' => number_format($mtn_sns, 2),
                        'mtn_sns_d' => number_format($mtn_sns_d, 2),

                        'airtel_vtu' => number_format($airtel_vtu, 2),
                        'airtel_vtu_d' => number_format($airtel_vtu_d, 2),
                        'airtel_sns' => number_format($airtel_sns, 2),
                        'airtel_sns_d' => number_format($airtel_sns_d, 2),

                        'glo_vtu' => number_format($glo_vtu, 2),
                        'glo_vtu_d' => number_format($glo_vtu_d, 2),
                        'glo_sns' => number_format($glo_sns, 2),
                        'glo_sns_d' => number_format($glo_sns_d, 2),

                        'mobile_vtu' => number_format($mobile_vtu, 2),
                        'mobile_vtu_d' => number_format($mobile_vtu_d, 2),
                        'mobile_sns' => number_format($mobile_sns, 2),
                        'mobile_sns_d' => number_format($mobile_sns_d, 2),

                        // cable
                        'dstv' => number_format($dstv, 2),
                        'dstv_c' => number_format($dstv_c, 2),
                        'gotv' => number_format($gotv, 2),
                        'gotv_c' => number_format($gotv_c, 2),
                        'startime' => number_format($startime, 2),
                        'startime_c' => number_format($startime_c, 2),

                        // exam
                        'waec' => number_format($waec, 2),
                        'waec_q' => number_format($waec_q),
                        'neco' => number_format($neco, 2),
                        'neco_q' => number_format($neco_q),
                        'nabteb' => number_format($nabteb, 2),
                        'nabteb_q' => number_format($nabteb_q),

                        // bulksms
                        'bulksms' => number_format($bulksms, 2),

                        // bill
                        'bill' => number_format($bill, 2),

                        // airtime 2 cash
                        'cash_amount' => number_format($cash, 2),
                        'cash_pay' => number_format($cash_pay, 2),

                        // deposit
                        'deposit_amount' => number_format($deposit_amount, 2),
                        'deposit_charges' => number_format($deposit_charges, 2),
                        'deposit_trans' => number_format($habukhan_in_trans, 1),
                        'spend_trans' => number_format($habukhan_out_trans, 1),
                        'spend_amount' => number_format($money_spent, 2),
                        'transfer_amount' => number_format($transfer_total, 2),
                        'transfer_charges' => number_format($transfer_charges, 2),
                        'referral_earnings' => number_format($referral_total, 2),
                        'categories' => [
                            ['name' => 'Airtime & Data', 'amount' => round($airtime_data_total, 2), 'icon' => 'phone_android', 'color' => '0xFF3B82F6'],
                            ['name' => 'Bills & Utilities', 'amount' => round($bills_utilities_total, 2), 'icon' => 'dynamic_feed', 'color' => '0xFF10B981'],
                            ['name' => 'Transfers', 'amount' => round($transfers_total, 2), 'icon' => 'swap_horiz', 'color' => '0xFFF59E0B'],
                            ['name' => 'Education', 'amount' => round($education_total, 2), 'icon' => 'school', 'color' => '0xFF8B5CF6'],
                            ['name' => 'Referrals', 'amount' => round($referral_total, 2), 'icon' => 'people', 'color' => '0xFFEC4899'],
                            ['name' => 'Others', 'amount' => round($others_total, 2), 'icon' => 'more_horiz', 'color' => '0xFF6B7280'],
                        ]
                    ]);
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
}
