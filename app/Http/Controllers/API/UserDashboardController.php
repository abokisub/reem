<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    /**
     * Get user dashboard statistics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $filter = $request->query('filter', 'Today');
        $now = \Carbon\Carbon::now("Africa/Lagos");
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();

        // Determine date range based on filter
        switch ($filter) {
            case 'Yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'Last 7 days':
                $startDate = $now->copy()->subDays(7)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'Last 30 days':
                $startDate = $now->copy()->subDays(30)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'All Time':
                $startDate = null;
                $endDate = null;
                break;
            case 'Custom':
                // For now, default to Today if Custom is selected without range (or implement custom logic later)
                // If custom range params are passed, use them.
                if ($request->has(['start_date', 'end_date'])) {
                    $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                }
                break;
            case 'Today':
            default:
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
        }

        // 1. Total Revenue, Daily Revenue (for charts), and Status Distribution
        $revenueQuery = DB::table('transactions')
            ->where('company_id', $user->active_company_id)
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit');

        if ($startDate && $endDate) {
            $revenueQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalRevenue = (float) $revenueQuery->where('status', 'success')->sum('amount');

        // Status Distribution (Deposits)
        $statusDistribution = DB::table('transactions')
            ->where('company_id', $user->active_company_id)
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit');

        if ($startDate && $endDate) {
            $statusDistribution->whereBetween('created_at', [$startDate, $endDate]);
        }
        $statusStats = $statusDistribution->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Revenue Chart (Daily)
        $revenueChart = [];
        if ($startDate && $endDate) {
            $dailyRevenue = DB::table('transactions')
                ->where('company_id', $user->active_company_id)
                ->where('category', 'virtual_account_credit')
                ->where('type', 'credit')
                ->where('status', 'success')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
                ->groupBy('date')
                ->pluck('total', 'date');

            $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->copy()->addDay());
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $revenueChart[] = [
                    'label' => $date->format('d M'),
                    'value' => (float) ($dailyRevenue[$dateString] ?? 0)
                ];
            }
        }

        // 2. Total Transactions and Transaction Analytics
        $transactionsQuery = DB::table('transactions')
            ->where('company_id', $user->active_company_id)
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit');

        if ($startDate && $endDate) {
            $transactionsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalTransactions = $transactionsQuery->count();

        // Transaction Chart (Daily)
        $transactionChart = [];
        if ($startDate && $endDate) {
            $dailyTransactions = DB::table('transactions')
                ->where('company_id', $user->active_company_id)
                ->where('category', 'virtual_account_credit')
                ->where('type', 'credit')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->pluck('count', 'date');

            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $transactionChart[] = [
                    'label' => $date->format('d M'),
                    'value' => (int) ($dailyTransactions[$dateString] ?? 0)
                ];
            }
        }

        // 3. Pending Settlement (from settlement_queue table)
        $pendingSettlement = 0;
        if (\Schema::hasTable('settlement_queue')) {
            $pendingSettlement = (float) DB::table('settlement_queue')
                ->where('company_id', $user->active_company_id)
                ->where('status', 'pending')
                ->sum('amount');
        }

        // 4. Growth Stats (Compare with previous period)
        $revenueGrowth = 0;
        if ($startDate && $endDate) {
            $daysDiff = $startDate->diffInDays($endDate) + 1;
            $prevStartDate = $startDate->copy()->subDays($daysDiff);
            $prevEndDate = $startDate->copy()->subSecond();

            $prevRevenue = DB::table('transactions')
                ->where('company_id', $user->active_company_id)
                ->where('category', 'virtual_account_credit')
                ->where('type', 'credit')
                ->where('status', 'success')
                ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
                ->sum('amount');

            if ($prevRevenue > 0) {
                $revenueGrowth = (($totalRevenue - $prevRevenue) / $prevRevenue) * 100;
            } else {
                $revenueGrowth = $totalRevenue > 0 ? 100 : 0;
            }
        }

        return response()->json([
            'status' => 'success',
            'filter' => $filter,
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'pending_settlement' => $pendingSettlement,
                'revenue_chart' => $revenueChart,
                'transaction_chart' => $transactionChart,
                'status_distribution' => [
                    ['label' => 'Successful', 'value' => (int) ($statusStats['success'] ?? 0)],
                    ['label' => 'Pending', 'value' => (int) ($statusStats['pending'] ?? 0)],
                    ['label' => 'Failed', 'value' => (int) ($statusStats['failed'] ?? 0)],
                ],
                'revenue_growth' => round($revenueGrowth, 2),
            ]
        ]);
    }
}
