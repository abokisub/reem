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

        if (str_starts_with($key, 'Bearer ')) {
            $key = substr($key, 7);
        }

        $originalKey = $key;
        $id = null;

        if (strpos($key, '|') !== false) {
            $parts = explode('|', $key, 2);
            $tokenId = $parts[0];
            $tokenPlainText = $parts[1];

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

            if (!$id) {
                $key = $tokenPlainText;
            }
        }

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

    private function getTokenUser($request, $id = null)
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

        if (empty($token)) {
            return null;
        }

        $userId = $this->verifyapptoken($token);
        if (!$userId)
            return null;

        return DB::table('users')->where(['status' => 'active', 'id' => $userId])->first();
    }

    public function Admin(Request $request, $id = null)
    {
        $user = $this->getTokenUser($request, $id);

        if (!$user) {
            return response()->json([
                'status' => 403,
                'message' => 'Not Authorised'
            ])->setStatusCode(403);
        }

        return $this->generateCalculatorResponse($request, null);
    }

    public function Company(Request $request, $id = null)
    {
        $user = $this->getTokenUser($request, $id);

        if (!$user || !$user->active_company_id) {
            return response()->json([
                'status' => 403,
                'message' => 'Not Authorised or No Active Company'
            ])->setStatusCode(403);
        }

        return $this->generateCalculatorResponse($request, $user->active_company_id);
    }

    private function generateCalculatorResponse($request, $companyId = null)
    {
        $dateCondition = $this->getDateCondition($request);

        $transactions = DB::table('transactions')
            ->when($companyId, function ($query) use ($companyId) {
                return $query->where('company_id', $companyId);
            })
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

        $metrics = $this->calculateMetrics($transactions);

        return response()->json([
            'status' => 'success',
            'period' => $request->status ?? 'TODAY',

            'deposit_amount' => number_format($metrics['deposit_amount'], 2, '.', ''),
            'deposit_charges' => number_format($metrics['deposit_charges'], 2, '.', ''),
            'spend_amount' => number_format($metrics['spend_amount'], 2, '.', ''),
            'transfer_amount' => number_format($metrics['transfer_amount'], 2, '.', ''),
            'transfer_charges' => number_format($metrics['transfer_charges'], 2, '.', ''),
            'bvn_total' => number_format($metrics['bvn_amount'], 2, '.', ''),
            'bvn_charges' => number_format($metrics['bvn_charges'], 2, '.', ''),
            'nin_total' => number_format($metrics['nin_amount'], 2, '.', ''),
            'nin_charges' => number_format($metrics['nin_charges'], 2, '.', ''),
            'total_revenue' => number_format($metrics['total_revenue'], 2, '.', ''),
            'total_costs' => number_format($metrics['total_costs'], 2, '.', ''),
            'net_profit' => number_format($metrics['net_profit'], 2, '.', ''),
            'profit_margin' => number_format($metrics['profit_margin'], 2, '.', ''),
            'deposit_trans' => number_format($metrics['deposit_percentage'], 1, '.', ''),
            'spend_trans' => number_format($metrics['spend_percentage'], 1, '.', ''),
        ]);
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
            case 'CUSTOM USER': // Frontend passes CUSTOM USER but doesn't implement filter in DB anyways
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
            'transfer_amount' => 0,
            'transfer_charges' => 0,
            'deposit_amount' => 0,
            'deposit_charges' => 0,
            'spend_amount' => 0,
            'bvn_amount' => 0,
            'bvn_charges' => 0,
            'nin_amount' => 0,
            'nin_charges' => 0,
        ];

        foreach ($transactions as $transaction) {
            $amount = $transaction->amount ?? 0;
            $fee = $transaction->fee ?? 0;
            $category = strtolower($transaction->category ?? '');
            $description = strtolower($transaction->description ?? '');

            switch ($category) {
                case 'transfer_out':
                    $metrics['transfer_amount'] += $amount;
                    $metrics['transfer_charges'] += $fee;
                    break;
                case 'virtual_account_credit':
                case 'funding':
                case 'deposit':
                    if ($transaction->type === 'credit') {
                        $metrics['deposit_amount'] += $amount;
                        $metrics['deposit_charges'] += $fee;
                    }
                    break;
                case 'other':
                    if (strpos($description, 'bvn') !== false) {
                        $metrics['bvn_amount'] += $amount;
                        $metrics['bvn_charges'] += $fee;
                    } elseif (strpos($description, 'nin') !== false) {
                        $metrics['nin_amount'] += $amount;
                        $metrics['nin_charges'] += $fee;
                    }
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

        // Platform Revenue is strictly the fees/charges collected from transactions, not the raw liquidity volume
        $metrics['total_revenue'] = $metrics['deposit_charges'] + $metrics['transfer_charges'] + $metrics['bvn_amount'] + $metrics['nin_amount'];

        // Without specific vendor cost data for these features, the gross platform cost is represented as 0
        $metrics['total_costs'] = 0;

        $metrics['net_profit'] = $metrics['total_revenue'] - $metrics['total_costs'];

        $metrics['profit_margin'] = $metrics['total_revenue'] > 0 ? 100 : 0;

        $total_flow = $metrics['total_revenue'] + $metrics['total_costs'];
        $metrics['deposit_percentage'] = $total_flow > 0 ?
            (($metrics['total_revenue'] / $total_flow) * 100) : 0;
        $metrics['spend_percentage'] = $total_flow > 0 ?
            (($metrics['total_costs'] / $total_flow) * 100) : 0;

        return $metrics;
    }
}