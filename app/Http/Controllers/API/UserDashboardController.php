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

        $filter = $request->query('filter', 'All Time');
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

        // Check if user is admin - if so, show system-wide data
        $isAdmin = strtoupper($user->type) === 'ADMIN';
        $companyId = $isAdmin ? null : $user->active_company_id;

        // For admin: Get total system wallet balance from company_wallets table
        if ($isAdmin) {
            $totalSystemBalance = (float) DB::table('company_wallets')->sum('balance');
            $totalCompanies = DB::table('companies')->where('status', 'active')->count();
            $totalVirtualAccounts = DB::table('virtual_accounts')->where('status', 'active')->count();
            $registeredBusinesses = DB::table('companies')->count();
            $pendingActivations = DB::table('companies')->where('status', 'pending')->count();
            $totalUsers = DB::table('company_users')->count();
        } else {
            // For regular companies: Get their wallet balance
            $companyWallet = DB::table('company_wallets')
                ->where('company_id', $user->active_company_id)
                ->where('currency', 'NGN')
                ->first();
            $totalSystemBalance = $companyWallet ? (float) $companyWallet->balance : 0;
            $totalCompanies = 1; // Just their company
            $totalVirtualAccounts = DB::table('virtual_accounts')
                ->where('company_id', $user->active_company_id)
                ->where('status', 'active')
                ->count();
        }

        // 1. Total Revenue, Daily Revenue (for charts), and Status Distribution
        // Always calculate TODAY's stats separately for admin dashboard cards
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $todayRevenueQuery = DB::table('transactions')
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit')
            ->whereIn('status', ['success', 'successful'])
            ->whereBetween('created_at', [$todayStart, $todayEnd]);
        if (!$isAdmin) {
            $todayRevenueQuery->where('company_id', $user->active_company_id);
        }
        $todayRevenue = (float) $todayRevenueQuery->sum('amount');

        $todayTransactionsQuery = DB::table('transactions')
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit')
            ->whereBetween('created_at', [$todayStart, $todayEnd]);
        if (!$isAdmin) {
            $todayTransactionsQuery->where('company_id', $user->active_company_id);
        }
        $todayTransactions = $todayTransactionsQuery->count();

        // Filtered revenue (based on selected filter) — use separate queries to avoid clone issues
        $filteredRevenueQuery = DB::table('transactions')
            ->where('type', 'credit')
            ->whereIn('status', ['success', 'successful']);
        if (!$isAdmin) {
            $filteredRevenueQuery->where('company_id', $user->active_company_id);
        }
        if ($startDate && $endDate) {
            $filteredRevenueQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $filteredRevenue = (float) $filteredRevenueQuery->sum('amount');

        // FOR ADMIN: Top card shows lifetime revenue regardless of filter
        if ($isAdmin) {
            $topRevenue = (float) DB::table('transactions')
                ->where('type', 'credit')
                ->whereIn('status', ['success', 'successful'])
                ->sum('amount');
        } else {
            $topRevenue = $filteredRevenue;
        }

        // Status Distribution (Deposits)
        $statusDistribution = DB::table('transactions')
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit');

        // Only filter by company if not admin
        if (!$isAdmin) {
            $statusDistribution->where('company_id', $user->active_company_id);
        }

        if ($startDate && $endDate) {
            $statusDistribution->whereBetween('created_at', [$startDate, $endDate]);
        }
        $statusStats = $statusDistribution->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $successfulCount = ($statusStats['success'] ?? 0) + ($statusStats['successful'] ?? 0);
        $pendingCount = ($statusStats['pending'] ?? 0) + ($statusStats['PENDING'] ?? 0);
        $failedCount = ($statusStats['failed'] ?? 0) + ($statusStats['FAILED'] ?? 0);

        // Revenue Chart (Daily)
        $revenueChart = [];
        if ($startDate && $endDate) {
            $dailyRevenueQuery = DB::table('transactions')
                ->where('type', 'credit')
                ->whereIn('status', ['success', 'successful'])
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Only filter by company if not admin
            if (!$isAdmin) {
                $dailyRevenueQuery->where('company_id', $user->active_company_id);
            }

            $dailyRevenue = $dailyRevenueQuery
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
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit');

        // Only filter by company if not admin
        if (!$isAdmin) {
            $transactionsQuery->where('company_id', $user->active_company_id);
        }

        if ($startDate && $endDate) {
            $transactionsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalTransactions = $transactionsQuery->count();

        // Transaction Chart (Daily)
        $transactionChart = [];
        if ($startDate && $endDate) {
            $dailyTransactionsQuery = DB::table('transactions')
                ->where('category', 'virtual_account_credit')
                ->where('type', 'credit')
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Only filter by company if not admin
            if (!$isAdmin) {
                $dailyTransactionsQuery->where('company_id', $user->active_company_id);
            }

            $dailyTransactions = $dailyTransactionsQuery
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
            $settlementQuery = DB::table('settlement_queue')
                ->where('status', 'pending');

            // Only filter by company if not admin
            if (!$isAdmin) {
                $settlementQuery->where('company_id', $user->active_company_id);
            }

            $pendingSettlement = (float) $settlementQuery->sum('amount');
        }

        // 4. Growth Stats (Compare with previous period)
        $revenueGrowth = 0;
        if ($startDate && $endDate) {
            $daysDiff = $startDate->diffInDays($endDate) + 1;
            $prevStartDate = $startDate->copy()->subDays($daysDiff);
            $prevEndDate = $startDate->copy()->subSecond();

            $prevRevenueQuery = DB::table('transactions')
                ->where('category', 'virtual_account_credit')
                ->where('type', 'credit')
                ->whereIn('status', ['success', 'successful'])
                ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

            // Only filter by company if not admin
            if (!$isAdmin) {
                $prevRevenueQuery->where('company_id', $user->active_company_id);
            }

            $prevRevenue = $prevRevenueQuery->sum('amount');

            if ($prevRevenue > 0) {
                $revenueGrowth = (($filteredRevenue - $prevRevenue) / $prevRevenue) * 100;
            } else {
                $revenueGrowth = $filteredRevenue > 0 ? 100 : 0;
            }
        }

        // 5. Network Balance Analytics (Data Sales by Network and Plan Type)
        $networkBalances = $this->getNetworkBalances($companyId, $startDate, $endDate);

        // 6. Service Analytics
        $serviceAnalytics = $this->getServiceAnalytics($companyId, $startDate, $endDate);

        // 7. Customer Analytics
        $customerStats = $this->getCustomerStats($companyId, $startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'filter' => $filter,
            'is_admin' => $isAdmin,
            'data' => [
                'total_revenue' => $topRevenue,
                'filtered_revenue' => $filteredRevenue,
                'today_revenue' => $todayRevenue,
                'total_transactions' => $totalTransactions,
                'today_transactions' => $todayTransactions,
                'pending_settlement' => $pendingSettlement,
                'system_wallet_balance' => $totalSystemBalance,
                'total_companies' => $totalCompanies,
                'total_virtual_accounts' => $totalVirtualAccounts,
                'registered_businesses' => $isAdmin ? $registeredBusinesses : 1,
                'pending_activations' => $isAdmin ? $pendingActivations : 0,
                'all_users' => $isAdmin ? $totalUsers : 0,
                'revenue_chart' => $revenueChart,
                'transaction_chart' => $transactionChart,
                'status_distribution' => [
                    ['label' => 'Successful', 'value' => (int) $successfulCount],
                    ['label' => 'Pending', 'value' => (int) $pendingCount],
                    ['label' => 'Failed', 'value' => (int) $failedCount],
                ],
                'revenue_growth' => round($revenueGrowth, 2),
                'network_balances' => $networkBalances,
                'service_analytics' => $serviceAnalytics,
                'customer_stats' => $customerStats,
                'kyc_analytics' => $this->getKycAnalytics($companyId, $startDate, $endDate),
                'lifetime_kyc' => $isAdmin ? $this->getKycAnalytics(null, null, null) : null,
                'profit_loss' => $this->getProfitLossAnalytics($companyId, $startDate, $endDate),
                'lifetime_profit' => $isAdmin ? $this->getProfitLossAnalytics(null, null, null) : null,
                'lifetime_status_distribution' => $isAdmin ? $this->getLifetimeStatusDistribution() : null,
            ]
        ]);
    }

    private function getLifetimeStatusDistribution()
    {
        $stats = DB::table('transactions')
            ->where('category', 'virtual_account_credit')
            ->where('type', 'credit')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $successfulCount = ($stats['success'] ?? 0) + ($stats['successful'] ?? 0);
        $pendingCount = ($stats['pending'] ?? 0) + ($stats['PENDING'] ?? 0);
        $failedCount = ($stats['failed'] ?? 0) + ($stats['FAILED'] ?? 0);

        return [
            ['label' => 'Successful', 'value' => (int) $successfulCount],
            ['label' => 'Pending', 'value' => (int) $pendingCount],
            ['label' => 'Failed', 'value' => (int) $failedCount],
        ];
    }

    private function getNetworkBalances($companyId, $startDate, $endDate)
    {
        $query = DB::table('transactions')
            ->where('category', 'data')
            ->whereIn('status', ['success', 'successful']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $dataTransactions = $query->get();

        $balances = [
            // MTN Plans
            'mtn_sme' => ['amount' => 0, 'volume' => 0],
            'mtn_sme2' => ['amount' => 0, 'volume' => 0],
            'mtn_datashare' => ['amount' => 0, 'volume' => 0],
            'mtn_cg' => ['amount' => 0, 'volume' => 0],
            'mtn_gifting' => ['amount' => 0, 'volume' => 0],

            // Airtel Plans
            'airtel_sme' => ['amount' => 0, 'volume' => 0],
            'airtel_sme2' => ['amount' => 0, 'volume' => 0],
            'airtel_datashare' => ['amount' => 0, 'volume' => 0],
            'airtel_cg' => ['amount' => 0, 'volume' => 0],
            'airtel_gifting' => ['amount' => 0, 'volume' => 0],

            // GLO Plans
            'glo_sme' => ['amount' => 0, 'volume' => 0],
            'glo_sme2' => ['amount' => 0, 'volume' => 0],
            'glo_datashare' => ['amount' => 0, 'volume' => 0],
            'glo_cg' => ['amount' => 0, 'volume' => 0],
            'glo_gifting' => ['amount' => 0, 'volume' => 0],

            // 9Mobile Plans
            'mobile_sme' => ['amount' => 0, 'volume' => 0],
            'mobile_sme2' => ['amount' => 0, 'volume' => 0],
            'mobile_datashare' => ['amount' => 0, 'volume' => 0],
            'mobile_cg' => ['amount' => 0, 'volume' => 0],
            'mobile_gifting' => ['amount' => 0, 'volume' => 0],
        ];

        foreach ($dataTransactions as $transaction) {
            $description = strtolower($transaction->description ?? '');
            $amount = $transaction->amount ?? 0;

            // Extract data volume from description
            preg_match('/(\d+(?:\.\d+)?)\s*(gb|mb)/i', $description, $matches);
            $volume = 0;
            if (!empty($matches)) {
                $size = floatval($matches[1]);
                $unit = strtolower($matches[2]);
                $volume = ($unit === 'gb') ? $size : ($size / 1024);
            }

            // Categorize by network and plan type
            if (strpos($description, 'mtn') !== false) {
                if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                    $balances['mtn_sme2']['amount'] += $amount;
                    $balances['mtn_sme2']['volume'] += $volume;
                } elseif (strpos($description, 'sme') !== false) {
                    $balances['mtn_sme']['amount'] += $amount;
                    $balances['mtn_sme']['volume'] += $volume;
                } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                    $balances['mtn_datashare']['amount'] += $amount;
                    $balances['mtn_datashare']['volume'] += $volume;
                } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                    $balances['mtn_cg']['amount'] += $amount;
                    $balances['mtn_cg']['volume'] += $volume;
                } elseif (strpos($description, 'gift') !== false) {
                    $balances['mtn_gifting']['amount'] += $amount;
                    $balances['mtn_gifting']['volume'] += $volume;
                }
            } elseif (strpos($description, 'airtel') !== false) {
                if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                    $balances['airtel_sme2']['amount'] += $amount;
                    $balances['airtel_sme2']['volume'] += $volume;
                } elseif (strpos($description, 'sme') !== false) {
                    $balances['airtel_sme']['amount'] += $amount;
                    $balances['airtel_sme']['volume'] += $volume;
                } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                    $balances['airtel_datashare']['amount'] += $amount;
                    $balances['airtel_datashare']['volume'] += $volume;
                } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                    $balances['airtel_cg']['amount'] += $amount;
                    $balances['airtel_cg']['volume'] += $volume;
                } elseif (strpos($description, 'gift') !== false) {
                    $balances['airtel_gifting']['amount'] += $amount;
                    $balances['airtel_gifting']['volume'] += $volume;
                }
            } elseif (strpos($description, 'glo') !== false) {
                if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                    $balances['glo_sme2']['amount'] += $amount;
                    $balances['glo_sme2']['volume'] += $volume;
                } elseif (strpos($description, 'sme') !== false) {
                    $balances['glo_sme']['amount'] += $amount;
                    $balances['glo_sme']['volume'] += $volume;
                } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                    $balances['glo_datashare']['amount'] += $amount;
                    $balances['glo_datashare']['volume'] += $volume;
                } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                    $balances['glo_cg']['amount'] += $amount;
                    $balances['glo_cg']['volume'] += $volume;
                } elseif (strpos($description, 'gift') !== false) {
                    $balances['glo_gifting']['amount'] += $amount;
                    $balances['glo_gifting']['volume'] += $volume;
                }
            } elseif (strpos($description, '9mobile') !== false || strpos($description, 'etisalat') !== false) {
                if (strpos($description, 'sme2') !== false || strpos($description, 'sme 2') !== false) {
                    $balances['mobile_sme2']['amount'] += $amount;
                    $balances['mobile_sme2']['volume'] += $volume;
                } elseif (strpos($description, 'sme') !== false) {
                    $balances['mobile_sme']['amount'] += $amount;
                    $balances['mobile_sme']['volume'] += $volume;
                } elseif (strpos($description, 'datashare') !== false || strpos($description, 'data share') !== false) {
                    $balances['mobile_datashare']['amount'] += $amount;
                    $balances['mobile_datashare']['volume'] += $volume;
                } elseif (strpos($description, 'corporate') !== false || strpos($description, 'cg') !== false) {
                    $balances['mobile_cg']['amount'] += $amount;
                    $balances['mobile_cg']['volume'] += $volume;
                } elseif (strpos($description, 'gift') !== false) {
                    $balances['mobile_gifting']['amount'] += $amount;
                    $balances['mobile_gifting']['volume'] += $volume;
                }
            }
        }

        return $balances;
    }

    private function getServiceAnalytics($companyId, $startDate, $endDate)
    {
        $query = DB::table('transactions')
            ->whereIn('status', ['success', 'successful']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $services = $query->select('category', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        $analytics = [];
        foreach ($services as $service) {
            $analytics[$service->category] = [
                'count' => $service->count,
                'amount' => $service->total
            ];
        }

        return $analytics;
    }

    private function getCustomerStats($companyId, $startDate, $endDate)
    {
        // Total customers
        $totalCustomersQuery = DB::table('virtual_accounts');
        if ($companyId) {
            $totalCustomersQuery->where('company_id', $companyId);
        }
        $totalCustomers = $totalCustomersQuery->count();

        // Active customers (made transactions in period)
        $activeCustomersQuery = DB::table('transactions')
            ->whereIn('status', ['success', 'successful']);
        if ($companyId) {
            $activeCustomersQuery->where('company_id', $companyId);
        }

        if ($startDate && $endDate) {
            $activeCustomersQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $activeCustomers = $activeCustomersQuery->distinct('user_id')->count();

        // Customer balance (aggregated if admin, company's wallet if not)
        $customerBalanceQuery = DB::table('company_wallets')
            ->where('currency', 'NGN');
        if ($companyId) {
            $customerBalanceQuery->where('company_id', $companyId);
        }
        $customerBalance = (float) $customerBalanceQuery->sum('balance');

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'customer_balance' => $customerBalance
        ];
    }

    private function getKycAnalytics($companyId, $startDate, $endDate)
    {
        // Build BVN and NIN queries separately to avoid clone/reuse issues
        $bvnQuery = DB::table('transactions')
            ->whereIn('status', ['success', 'successful'])
            ->where('description', 'LIKE', '%BVN%')
            ->where(function ($q) {
                $q->where('category', 'kyc_charge')
                    ->orWhere('transaction_type', 'kyc_charge')
                    ->orWhere(function ($q2) {
                        $q2->where('category', 'other')
                            ->where('transaction_type', 'kyc_charge');
                    });
            });

        if ($companyId) {
            $bvnQuery->where('company_id', $companyId);
        }
        if ($startDate && $endDate) {
            $bvnQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $bvnTotal = (float) (clone $bvnQuery)->sum('amount');
        $bvnCharges = (float) (clone $bvnQuery)->sum('fee');
        $bvnCount = (int) (clone $bvnQuery)->count();

        $ninQuery = DB::table('transactions')
            ->whereIn('status', ['success', 'successful'])
            ->where('description', 'LIKE', '%NIN%')
            ->where(function ($q) {
                $q->where('category', 'kyc_charge')
                    ->orWhere('transaction_type', 'kyc_charge')
                    ->orWhere(function ($q2) {
                        $q2->where('category', 'other')
                            ->where('transaction_type', 'kyc_charge');
                    });
            });

        if ($companyId) {
            $ninQuery->where('company_id', $companyId);
        }
        if ($startDate && $endDate) {
            $ninQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $ninTotal = (float) (clone $ninQuery)->sum('amount');
        $ninCharges = (float) (clone $ninQuery)->sum('fee');
        $ninCount = (int) (clone $ninQuery)->count();

        return [
            'bvn_total' => $bvnTotal,
            'bvn_charges' => $bvnCharges,
            'bvn_count' => $bvnCount,
            'nin_total' => $ninTotal,
            'nin_charges' => $ninCharges,
            'nin_count' => $ninCount,
            'total_kyc_amount' => $bvnTotal + $ninTotal,
            'total_kyc_charges' => $bvnCharges + $ninCharges,
            'total_kyc_count' => $bvnCount + $ninCount,
        ];
    }

    private function getProfitLossAnalytics($companyId, $startDate, $endDate)
    {
        $transactionQuery = DB::table('transactions')
            ->whereIn('status', ['success', 'successful']);

        if ($companyId) {
            $transactionQuery->where('company_id', $companyId);
        }

        if ($startDate && $endDate) {
            $transactionQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Total Revenue (Credits - money coming in)
        $totalRevenue = $transactionQuery->clone()
            ->where('type', 'credit')
            ->sum('amount');

        // Total Costs (Debits - money going out + fees)
        $totalDebits = $transactionQuery->clone()
            ->where('type', 'debit')
            ->sum('amount');

        $totalFees = $transactionQuery->clone()
            ->sum('fee');

        $totalCosts = $totalDebits + $totalFees;

        // Net Profit/Loss
        $netProfit = $totalRevenue - $totalCosts;

        // Profit Margin
        $profitMargin = $totalRevenue > 0 ? (($netProfit / $totalRevenue) * 100) : 0;

        // Transaction counts
        $totalTransactions = $transactionQuery->clone()->count();
        $creditTransactions = $transactionQuery->clone()->where('type', 'credit')->count();
        $debitTransactions = $transactionQuery->clone()->where('type', 'debit')->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_costs' => $totalCosts,
            'total_debits' => $totalDebits,
            'total_fees' => $totalFees,
            'net_profit' => $netProfit,
            'profit_margin' => round($profitMargin, 2),
            'total_transactions' => $totalTransactions,
            'credit_transactions' => $creditTransactions,
            'debit_transactions' => $debitTransactions,
            'is_profitable' => $netProfit >= 0,
        ];
    }
}
