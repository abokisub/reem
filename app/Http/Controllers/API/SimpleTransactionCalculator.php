<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SimpleTransactionCalculator extends Controller
{
    public function verifyapptoken($key)
    {
        if (empty($key)) {
            return null;
        }

        // Strip Bearer prefix if present
        if (str_starts_with($key, 'Bearer ')) {
            $key = substr($key, 7);
        }

        $originalKey = $key; // Preserve original key

        $id = null;

        // 1. Check for Sanctum Token (ID|SECRET)
        if (strpos($key, '|') !== false) {
            $parts = explode('|', $key, 2);
            $tokenId = $parts[0];
            $tokenPlainText = $parts[1];

            // Safety: Handle URL encoded pipes if they sneak in
            if (strpos($tokenId, '%7C') !== false) {
                $parts = explode('%7C', $key, 2);
                $tokenId = $parts[0];
                $tokenPlainText = $parts[1];
            }

            $sanctumToken = DB::table('personal_access_tokens')
                ->where('id', $tokenId)
                ->first();

            if ($sanctumToken && hash_equals($sanctumToken->token, hash('sha256', $tokenPlainText))) {
                $id = $sanctumToken->tokenable_id;
            }

            // 1.5 Fallback for Legacy ID|KEY format
            if (!$id) {
                $key = $tokenPlainText; // Use only the secret part for legacy check
            }
        }

        // 2. Fallback to Legacy Columns
        if (!$id) {
            $check = DB::table('users')->where(function ($query) use ($key, $originalKey) {
                $query->where('app_key', $key)
                    ->orWhere('habukhan_key', $key)
                    ->orWhere('api_key', $key)
                    ->orWhere('habukhan_key', $originalKey)
                    ->orWhere('app_key', $originalKey)
                    ->orWhere('api_key', $originalKey);
            })->first();

            if ($check) {
                $id = $check->id;
            }
        }

        return $id;
    }

    public function Admin(Request $request, $id = null)
    {
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

                    // Get date range based on status
                    $dateCondition = $this->getDateCondition($request);

                    // Get all transactions based on date range
                    $transactions = DB::table('transactions')
                        ->when($dateCondition['type'] === 'today', function ($query) {
                            return $query->whereDate('created_at', Carbon::now("Africa/Lagos"));
                        })
                        ->when($dateCondition['type'] === 'days', function ($query) use ($dateCondition) {
                            return $query->whereDate('created_at', '>', Carbon::now("Africa/Lagos")->subDays($dateCondition['days']));
                        })
                        ->when($dateCondition['type'] === 'all', function ($query) {
                            return $query;
                        })
                        ->when($dateCondition['type'] === 'custom', function ($query) use ($dateCondition) {
                            return $query->whereBetween('created_at', [$dateCondition['start'], $dateCondition['end']]);
                        })
                        ->where('status', 'success')
                        ->get();

                    // Calculate metrics
                    $metrics = $this->calculateMetrics($transactions);

                    return response()->json([
                        'status' => 'success',
                        'period' => $request->status ?? 'TODAY',
                        
                        // Service totals
                        'total_data_amount' => number_format($metrics['data_amount'], 2),
                        'total_airtime_amount' => number_format($metrics['airtime_amount'], 2),
                        'total_cable_amount' => number_format($metrics['cable_amount'], 2),
                        'total_bill_amount' => number_format($metrics['bill_amount'], 2),
                        'total_education_amount' => number_format($metrics['education_amount'], 2),
                        'total_bulksms_amount' => number_format($metrics['bulksms_amount'], 2),
                        'total_airtime_to_cash_amount' => number_format($metrics['airtime_to_cash_amount'], 2),
                        'total_transfer_amount' => number_format($metrics['transfer_amount'], 2),
                        
                        // Legacy fields for frontend compatibility - using actual transaction data
                        'mtn_sme' => number_format($metrics['mtn_sme_gb'], 2) . 'GB',
                        'mtn_sme_bal' => number_format($metrics['mtn_sme_amount'], 2),
                        'mtn_sme2' => number_format($metrics['mtn_sme2_gb'], 2) . 'GB',
                        'mtn_sme2_bal' => number_format($metrics['mtn_sme2_amount'], 2),
                        'mtn_datashare' => number_format($metrics['mtn_datashare_gb'], 2) . 'GB',
                        'mtn_datashare_bal' => number_format($metrics['mtn_datashare_amount'], 2),
                        'mtn_cg' => number_format($metrics['mtn_cg_gb'], 2) . 'GB',
                        'mtn_cg_bal' => number_format($metrics['mtn_cg_amount'], 2),
                        'mtn_g' => number_format($metrics['mtn_g_gb'], 2) . 'GB',
                        'mtn_g_bal' => number_format($metrics['mtn_g_amount'], 2),
                        
                        'airtel_sme' => number_format($metrics['airtel_sme_gb'], 2) . 'GB',
                        'airtel_sme_bal' => number_format($metrics['airtel_sme_amount'], 2),
                        'airtel_sme2' => number_format($metrics['airtel_sme2_gb'], 2) . 'GB',
                        'airtel_sme2_bal' => number_format($metrics['airtel_sme2_amount'], 2),
                        'airtel_datashare' => number_format($metrics['airtel_datashare_gb'], 2) . 'GB',
                        'airtel_datashare_bal' => number_format($metrics['airtel_datashare_amount'], 2),
                        'airtel_cg' => number_format($metrics['airtel_cg_gb'], 2) . 'GB',
                        'airtel_cg_bal' => number_format($metrics['airtel_cg_amount'], 2),
                        'airtel_g' => number_format($metrics['airtel_g_gb'], 2) . 'GB',
                        'airtel_g_bal' => number_format($metrics['airtel_g_amount'], 2),
                        
                        'glo_sme' => number_format($metrics['glo_sme_gb'], 2) . 'GB',
                        'glo_sme_bal' => number_format($metrics['glo_sme_amount'], 2),
                        'glo_sme2' => number_format($metrics['glo_sme2_gb'], 2) . 'GB',
                        'glo_sme2_bal' => number_format($metrics['glo_sme2_amount'], 2),
                        'glo_datashare' => number_format($metrics['glo_datashare_gb'], 2) . 'GB',
                        'glo_datashare_bal' => number_format($metrics['glo_datashare_amount'], 2),
                        'glo_cg' => number_format($metrics['glo_cg_gb'], 2) . 'GB',
                        'glo_cg_bal' => number_format($metrics['glo_cg_amount'], 2),
                        'glo_g' => number_format($metrics['glo_g_gb'], 2) . 'GB',
                        'glo_g_bal' => number_format($metrics['glo_g_amount'], 2),
                        
                        'mobile_sme' => number_format($metrics['mobile_sme_gb'], 2) . 'GB',
                        'mobile_sme_bal' => number_format($metrics['mobile_sme_amount'], 2),
                        'mobile_sme2' => number_format($metrics['mobile_sme2_gb'], 2) . 'GB',
                        'mobile_sme2_bal' => number_format($metrics['mobile_sme2_amount'], 2),
                        'mobile_datashare' => number_format($metrics['mobile_datashare_gb'], 2) . 'GB',
                        'mobile_datashare_bal' => number_format($metrics['mobile_datashare_amount'], 2),
                        'mobile_cg' => number_format($metrics['mobile_cg_gb'], 2) . 'GB',
                        'mobile_cg_bal' => number_format($metrics['mobile_cg_amount'], 2),
                        'mobile_g' => number_format($metrics['mobile_g_gb'], 2) . 'GB',
                        'mobile_g_bal' => number_format($metrics['mobile_g_amount'], 2),
                        
                        // Airtime - using actual transaction data
                        'mtn_vtu' => number_format($metrics['mtn_vtu_amount'], 2),
                        'mtn_vtu_d' => number_format($metrics['mtn_vtu_discount'], 2),
                        'mtn_sns' => number_format($metrics['mtn_sns_amount'], 2),
                        'mtn_sns_d' => number_format($metrics['mtn_sns_discount'], 2),
                        
                        'airtel_vtu' => number_format($metrics['airtel_vtu_amount'], 2),
                        'airtel_vtu_d' => number_format($metrics['airtel_vtu_discount'], 2),
                        'airtel_sns' => number_format($metrics['airtel_sns_amount'], 2),
                        'airtel_sns_d' => number_format($metrics['airtel_sns_discount'], 2),
                        
                        'glo_vtu' => number_format($metrics['glo_vtu_amount'], 2),
                        'glo_vtu_d' => number_format($metrics['glo_vtu_discount'], 2),
                        'glo_sns' => number_format($metrics['glo_sns_amount'], 2),
                        'glo_sns_d' => number_format($metrics['glo_sns_discount'], 2),
                        
                        'mobile_vtu' => number_format($metrics['mobile_vtu_amount'], 2),
                        'mobile_vtu_d' => number_format($metrics['mobile_vtu_discount'], 2),
                        'mobile_sns' => number_format($metrics['mobile_sns_amount'], 2),
                        'mobile_sns_d' => number_format($metrics['mobile_sns_discount'], 2),
                        
                        // Cable - using actual transaction data
                        'dstv' => number_format($metrics['dstv_amount'], 2),
                        'dstv_c' => number_format($metrics['dstv_charges'], 2),
                        'gotv' => number_format($metrics['gotv_amount'], 2),
                        'gotv_c' => number_format($metrics['gotv_charges'], 2),
                        'startime' => number_format($metrics['startime_amount'], 2),
                        'startime_c' => number_format($metrics['startime_charges'], 2),
                        
                        // Education - using actual transaction data
                        'waec' => number_format($metrics['waec_amount'], 2),
                        'waec_q' => number_format($metrics['waec_count'], 0),
                        'neco' => number_format($metrics['neco_amount'], 2),
                        'neco_q' => number_format($metrics['neco_count'], 0),
                        'nabteb' => number_format($metrics['nabteb_amount'], 2),
                        'nabteb_q' => number_format($metrics['nabteb_count'], 0),
                        
                        // Other services - using actual transaction data
                        'bulksms' => number_format($metrics['bulksms_amount'], 2),
                        'bill' => number_format($metrics['bill_amount'], 2),
                        'cash_amount' => number_format($metrics['airtime_to_cash_amount'], 2),
                        'cash_pay' => number_format($metrics['airtime_to_cash_payout'], 2),
                        
                        // Financial metrics
                        'deposit_amount' => number_format($metrics['deposit_amount'], 2),
                        'deposit_charges' => number_format($metrics['deposit_charges'], 2),
                        'spend_amount' => number_format($metrics['spend_amount'], 2),
                        'transfer_amount' => number_format($metrics['transfer_amount'], 2),
                        'transfer_charges' => number_format($metrics['transfer_charges'], 2),
                        
                        // Additional services
                        'recharge_card_total' => number_format($metrics['recharge_card_amount'], 2),
                        'recharge_card_charges' => number_format($metrics['recharge_card_charges'], 2),
                        'data_card_total' => number_format($metrics['data_card_amount'], 2),
                        'data_card_charges' => number_format($metrics['data_card_charges'], 2),
                        
                        // KYC Services
                        'bvn_total' => number_format($metrics['bvn_amount'], 2),
                        'bvn_charges' => number_format($metrics['bvn_charges'], 2),
                        'nin_total' => number_format($metrics['nin_amount'], 2),
                        'nin_charges' => number_format($metrics['nin_charges'], 2),
                        
                        // Profit/Loss Analysis
                        'total_revenue' => number_format($metrics['total_revenue'], 2),
                        'total_costs' => number_format($metrics['total_costs'], 2),
                        'net_profit' => number_format($metrics['net_profit'], 2),
                        'profit_margin' => number_format($metrics['profit_margin'], 2),
                        
                        'deposit_trans' => number_format($metrics['deposit_percentage'], 1),
                        'spend_trans' => number_format($metrics['spend_percentage'], 1),
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    private function getDateCondition($request)
    {
        switch ($request->status) {
            case 'TODAY':
                return ['type' => 'today'];
            case '7DAYS':
                return ['type' => 'days', 'days' => 7];
            case '30DAYS':
                return ['type' => 'days', 'days' => 30];
            case 'ALL TIME':
                return ['type' => 'all'];
            case 'CUSTOM':
                if (!empty($request->from) && !empty($request->to)) {
                    return [
                        'type' => 'custom',
                        'start' => Carbon::parse($request->from . ' 00:00:00')->toDateTimeString(),
                        'end' => Carbon::parse($request->to . ' 23:59:59')->toDateTimeString()
                    ];
                }
                return ['type' => 'today'];
            default:
                return ['type' => 'today'];
        }
    }

    private function calculateMetrics($transactions)
    {
        $metrics = [
            // Service totals
            'data_amount' => 0,
            'airtime_amount' => 0,
            'cable_amount' => 0,
            'bill_amount' => 0,
            'education_amount' => 0,
            'education_count' => 0,
            'bulksms_amount' => 0,
            'airtime_to_cash_amount' => 0,
            'airtime_to_cash_payout' => 0,
            'transfer_amount' => 0,
            'transfer_charges' => 0,
            'deposit_amount' => 0,
            'deposit_charges' => 0,
            'spend_amount' => 0,
            'recharge_card_amount' => 0,
            'recharge_card_charges' => 0,
            'data_card_amount' => 0,
            'data_card_charges' => 0,
            'bvn_amount' => 0,
            'bvn_charges' => 0,
            'nin_amount' => 0,
            'nin_charges' => 0,
            
            // Data plan breakdowns by network and type
            'mtn_sme_gb' => 0, 'mtn_sme_amount' => 0,
            'mtn_sme2_gb' => 0, 'mtn_sme2_amount' => 0,
            'mtn_datashare_gb' => 0, 'mtn_datashare_amount' => 0,
            'mtn_cg_gb' => 0, 'mtn_cg_amount' => 0,
            'mtn_g_gb' => 0, 'mtn_g_amount' => 0,
            
            'airtel_sme_gb' => 0, 'airtel_sme_amount' => 0,
            'airtel_sme2_gb' => 0, 'airtel_sme2_amount' => 0,
            'airtel_datashare_gb' => 0, 'airtel_datashare_amount' => 0,
            'airtel_cg_gb' => 0, 'airtel_cg_amount' => 0,
            'airtel_g_gb' => 0, 'airtel_g_amount' => 0,
            
            'glo_sme_gb' => 0, 'glo_sme_amount' => 0,
            'glo_sme2_gb' => 0, 'glo_sme2_amount' => 0,
            'glo_datashare_gb' => 0, 'glo_datashare_amount' => 0,
            'glo_cg_gb' => 0, 'glo_cg_amount' => 0,
            'glo_g_gb' => 0, 'glo_g_amount' => 0,
            
            'mobile_sme_gb' => 0, 'mobile_sme_amount' => 0,
            'mobile_sme2_gb' => 0, 'mobile_sme2_amount' => 0,
            'mobile_datashare_gb' => 0, 'mobile_datashare_amount' => 0,
            'mobile_cg_gb' => 0, 'mobile_cg_amount' => 0,
            'mobile_g_gb' => 0, 'mobile_g_amount' => 0,
            
            // Airtime breakdowns by network and type
            'mtn_vtu_amount' => 0, 'mtn_vtu_discount' => 0,
            'mtn_sns_amount' => 0, 'mtn_sns_discount' => 0,
            'airtel_vtu_amount' => 0, 'airtel_vtu_discount' => 0,
            'airtel_sns_amount' => 0, 'airtel_sns_discount' => 0,
            'glo_vtu_amount' => 0, 'glo_vtu_discount' => 0,
            'glo_sns_amount' => 0, 'glo_sns_discount' => 0,
            'mobile_vtu_amount' => 0, 'mobile_vtu_discount' => 0,
            'mobile_sns_amount' => 0, 'mobile_sns_discount' => 0,
            
            // Cable breakdowns
            'dstv_amount' => 0, 'dstv_charges' => 0,
            'gotv_amount' => 0, 'gotv_charges' => 0,
            'startime_amount' => 0, 'startime_charges' => 0,
            
            // Education breakdowns
            'waec_amount' => 0, 'waec_count' => 0,
            'neco_amount' => 0, 'neco_count' => 0,
            'nabteb_amount' => 0, 'nabteb_count' => 0,
        ];

        foreach ($transactions as $transaction) {
            $amount = $transaction->amount ?? 0;
            $fee = $transaction->fee ?? 0;
            $description = strtolower($transaction->description ?? '');
            $category = strtolower($transaction->category ?? '');
            
            // Parse transaction details from description or metadata
            $this->parseTransactionDetails($transaction, $metrics, $amount, $fee);
            
            // General category totals
            switch ($category) {
                case 'data':
                    $metrics['data_amount'] += $amount;
                    break;
                case 'airtime':
                    $metrics['airtime_amount'] += $amount;
                    break;
                case 'cable':
                    $metrics['cable_amount'] += $amount;
                    break;
                case 'bill':
                case 'electricity':
                    $metrics['bill_amount'] += $amount;
                    break;
                case 'education':
                    $metrics['education_amount'] += $amount;
                    $metrics['education_count']++;
                    break;
                case 'bulksms':
                    $metrics['bulksms_amount'] += $amount;
                    break;
                case 'airtime_to_cash':
                    $metrics['airtime_to_cash_amount'] += $amount;
                    // Calculate payout (typically 85-90% of amount)
                    $metrics['airtime_to_cash_payout'] += $amount * 0.87;
                    break;
                case 'transfer_out':
                    $metrics['transfer_amount'] += $amount;
                    $metrics['transfer_charges'] += $fee;
                    break;
                case 'funding':
                    if ($transaction->type === 'credit') {
                        $metrics['deposit_amount'] += $amount;
                        $metrics['deposit_charges'] += $fee;
                    }
                    break;
                case 'recharge_card':
                    $metrics['recharge_card_amount'] += $amount;
                    $metrics['recharge_card_charges'] += $fee;
                    break;
                case 'data_card':
                    $metrics['data_card_amount'] += $amount;
                    $metrics['data_card_charges'] += $fee;
                    break;
                case 'bvn':
                case 'kyc_bvn':
                    $metrics['bvn_amount'] += $amount;
                    $metrics['bvn_charges'] += $fee;
                    break;
                case 'nin':
                case 'kyc_nin':
                    $metrics['nin_amount'] += $amount;
                    $metrics['nin_charges'] += $fee;
                    break;
            }

            if ($transaction->type === 'debit') {
                $metrics['spend_amount'] += $amount;
            }
        }

        // Calculate profit/loss
        $metrics['total_revenue'] = $metrics['deposit_amount'];
        $metrics['total_costs'] = $metrics['spend_amount'] + $metrics['transfer_charges'];
        $metrics['net_profit'] = $metrics['total_revenue'] - $metrics['total_costs'];
        $metrics['profit_margin'] = $metrics['total_revenue'] > 0 ? 
            (($metrics['net_profit'] / $metrics['total_revenue']) * 100) : 0;

        // Calculate percentages
        $total_flow = $metrics['total_revenue'] + $metrics['total_costs'];
        $metrics['deposit_percentage'] = $total_flow > 0 ? 
            (($metrics['total_revenue'] / $total_flow) * 100) : 0;
        $metrics['spend_percentage'] = $total_flow > 0 ? 
            (($metrics['total_costs'] / $total_flow) * 100) : 0;

        return $metrics;
    }

    private function parseTransactionDetails($transaction, &$metrics, $amount, $fee)
    {
        $description = strtolower($transaction->description ?? '');
        $category = strtolower($transaction->category ?? '');
        
        // Parse data transactions
        if ($category === 'data') {
            $this->parseDataTransaction($description, $metrics, $amount);
        }
        
        // Parse airtime transactions
        if ($category === 'airtime') {
            $this->parseAirtimeTransaction($description, $metrics, $amount, $fee);
        }
        
        // Parse cable transactions
        if ($category === 'cable') {
            $this->parseCableTransaction($description, $metrics, $amount, $fee);
        }
        
        // Parse education transactions
        if ($category === 'education') {
            $this->parseEducationTransaction($description, $metrics, $amount);
        }
    }

    private function parseDataTransaction($description, &$metrics, $amount)
    {
        // Extract data size from description (e.g., "1GB", "500MB", "2.5GB")
        preg_match('/(\d+(?:\.\d+)?)\s*(gb|mb)/i', $description, $matches);
        $dataSize = 0;
        if (!empty($matches)) {
            $size = floatval($matches[1]);
            $unit = strtolower($matches[2]);
            $dataSize = ($unit === 'gb') ? $size : ($size / 1024); // Convert MB to GB
        }
        
        // Determine network and plan type from description
        if (strpos($description, 'mtn') !== false) {
            if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                $metrics['mtn_sme2_gb'] += $dataSize;
                $metrics['mtn_sme2_amount'] += $amount;
            } elseif (strpos($description, 'sme') !== false) {
                $metrics['mtn_sme_gb'] += $dataSize;
                $metrics['mtn_sme_amount'] += $amount;
            } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                $metrics['mtn_datashare_gb'] += $dataSize;
                $metrics['mtn_datashare_amount'] += $amount;
            } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                $metrics['mtn_cg_gb'] += $dataSize;
                $metrics['mtn_cg_amount'] += $amount;
            } elseif (strpos($description, 'gift') !== false) {
                $metrics['mtn_g_gb'] += $dataSize;
                $metrics['mtn_g_amount'] += $amount;
            } else {
                // Default to SME for MTN
                $metrics['mtn_sme_gb'] += $dataSize;
                $metrics['mtn_sme_amount'] += $amount;
            }
        } elseif (strpos($description, 'airtel') !== false) {
            if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                $metrics['airtel_sme2_gb'] += $dataSize;
                $metrics['airtel_sme2_amount'] += $amount;
            } elseif (strpos($description, 'sme') !== false) {
                $metrics['airtel_sme_gb'] += $dataSize;
                $metrics['airtel_sme_amount'] += $amount;
            } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                $metrics['airtel_datashare_gb'] += $dataSize;
                $metrics['airtel_datashare_amount'] += $amount;
            } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                $metrics['airtel_cg_gb'] += $dataSize;
                $metrics['airtel_cg_amount'] += $amount;
            } elseif (strpos($description, 'gift') !== false) {
                $metrics['airtel_g_gb'] += $dataSize;
                $metrics['airtel_g_amount'] += $amount;
            } else {
                $metrics['airtel_sme_gb'] += $dataSize;
                $metrics['airtel_sme_amount'] += $amount;
            }
        } elseif (strpos($description, 'glo') !== false) {
            if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                $metrics['glo_sme2_gb'] += $dataSize;
                $metrics['glo_sme2_amount'] += $amount;
            } elseif (strpos($description, 'sme') !== false) {
                $metrics['glo_sme_gb'] += $dataSize;
                $metrics['glo_sme_amount'] += $amount;
            } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                $metrics['glo_datashare_gb'] += $dataSize;
                $metrics['glo_datashare_amount'] += $amount;
            } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                $metrics['glo_cg_gb'] += $dataSize;
                $metrics['glo_cg_amount'] += $amount;
            } elseif (strpos($description, 'gift') !== false) {
                $metrics['glo_g_gb'] += $dataSize;
                $metrics['glo_g_amount'] += $amount;
            } else {
                $metrics['glo_sme_gb'] += $dataSize;
                $metrics['glo_sme_amount'] += $amount;
            }
        } elseif (strpos($description, '9mobile') !== false || strpos($description, 'etisalat') !== false) {
            if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                $metrics['mobile_sme2_gb'] += $dataSize;
                $metrics['mobile_sme2_amount'] += $amount;
            } elseif (strpos($description, 'sme') !== false) {
                $metrics['mobile_sme_gb'] += $dataSize;
                $metrics['mobile_sme_amount'] += $amount;
            } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                $metrics['mobile_datashare_gb'] += $dataSize;
                $metrics['mobile_datashare_amount'] += $amount;
            } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                $metrics['mobile_cg_gb'] += $dataSize;
                $metrics['mobile_cg_amount'] += $amount;
            } elseif (strpos($description, 'gift') !== false) {
                $metrics['mobile_g_gb'] += $dataSize;
                $metrics['mobile_g_amount'] += $amount;
            } else {
                $metrics['mobile_sme_gb'] += $dataSize;
                $metrics['mobile_sme_amount'] += $amount;
            }
        }
    }

    private function parseAirtimeTransaction($description, &$metrics, $amount, $fee)
    {
        $discount = $fee; // Fee represents discount/profit
        
        if (strpos($description, 'mtn') !== false) {
            if (strpos($description, 'sns') !== false || strpos($description, 'share') !== false) {
                $metrics['mtn_sns_amount'] += $amount;
                $metrics['mtn_sns_discount'] += $discount;
            } else {
                $metrics['mtn_vtu_amount'] += $amount;
                $metrics['mtn_vtu_discount'] += $discount;
            }
        } elseif (strpos($description, 'airtel') !== false) {
            if (strpos($description, 'sns') !== false || strpos($description, 'share') !== false) {
                $metrics['airtel_sns_amount'] += $amount;
                $metrics['airtel_sns_discount'] += $discount;
            } else {
                $metrics['airtel_vtu_amount'] += $amount;
                $metrics['airtel_vtu_discount'] += $discount;
            }
        } elseif (strpos($description, 'glo') !== false) {
            if (strpos($description, 'sns') !== false || strpos($description, 'share') !== false) {
                $metrics['glo_sns_amount'] += $amount;
                $metrics['glo_sns_discount'] += $discount;
            } else {
                $metrics['glo_vtu_amount'] += $amount;
                $metrics['glo_vtu_discount'] += $discount;
            }
        } elseif (strpos($description, '9mobile') !== false || strpos($description, 'etisalat') !== false) {
            if (strpos($description, 'sns') !== false || strpos($description, 'share') !== false) {
                $metrics['mobile_sns_amount'] += $amount;
                $metrics['mobile_sns_discount'] += $discount;
            } else {
                $metrics['mobile_vtu_amount'] += $amount;
                $metrics['mobile_vtu_discount'] += $discount;
            }
        }
    }

    private function parseCableTransaction($description, &$metrics, $amount, $fee)
    {
        if (strpos($description, 'dstv') !== false) {
            $metrics['dstv_amount'] += $amount;
            $metrics['dstv_charges'] += $fee;
        } elseif (strpos($description, 'gotv') !== false) {
            $metrics['gotv_amount'] += $amount;
            $metrics['gotv_charges'] += $fee;
        } elseif (strpos($description, 'startime') !== false || strpos($description, 'startimes') !== false) {
            $metrics['startime_amount'] += $amount;
            $metrics['startime_charges'] += $fee;
        }
    }

    private function parseEducationTransaction($description, &$metrics, $amount)
    {
        if (strpos($description, 'waec') !== false) {
            $metrics['waec_amount'] += $amount;
            $metrics['waec_count']++;
        } elseif (strpos($description, 'neco') !== false) {
            $metrics['neco_amount'] += $amount;
            $metrics['neco_count']++;
        } elseif (strpos($description, 'nabteb') !== false) {
            $metrics['nabteb_amount'] += $amount;
            $metrics['nabteb_count']++;
        }
    }
}