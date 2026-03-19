<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditCompanyWallet extends Command
{
    protected $signature = 'audit:wallet {search? : Company name, email, or ID to search for}';
    protected $description = 'Full financial audit of a company wallet - tracks all money in and out';

    public function handle()
    {
        $search = $this->argument('search');

        // If no search provided, list all companies
        if (!$search) {
            $this->info("\n=== ALL COMPANIES ===\n");
            $companies = DB::table('companies')
                ->leftJoin('company_wallets', 'companies.id', '=', 'company_wallets.company_id')
                ->select('companies.id', 'companies.name', 'companies.email', 'companies.status', 'company_wallets.balance', 'company_wallets.ledger_balance', 'company_wallets.pending_balance')
                ->get();

            $this->table(
                ['ID', 'Name', 'Email', 'Status', 'Balance', 'Ledger', 'Pending'],
                $companies->map(fn($c) => [$c->id, $c->name, $c->email, $c->status, number_format($c->balance ?? 0, 2), number_format($c->ledger_balance ?? 0, 2), number_format($c->pending_balance ?? 0, 2)])
            );
            $this->info("\nUsage: php artisan audit:wallet <company_name_or_id>");
            return;
        }

        // Find the company
        $company = DB::table('companies')
            ->where('id', $search)
            ->orWhere('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->first();

        if (!$company) {
            $this->error("Company not found: {$search}");
            $this->info("Listing all companies...\n");
            $this->call('audit:wallet');
            return;
        }

        $this->info("\n" . str_repeat('=', 70));
        $this->info("  FULL WALLET AUDIT: {$company->name} (ID: {$company->id})");
        $this->info("  Email: {$company->email}");
        $this->info(str_repeat('=', 70));

        // ===== 1. CURRENT WALLET STATE =====
        $wallet = DB::table('company_wallets')->where('company_id', $company->id)->first();
        $this->info("\n--- CURRENT WALLET STATE ---");
        if ($wallet) {
            $this->table(['Field', 'Value'], [
                ['Current Balance', '₦' . number_format($wallet->balance, 2)],
                ['Ledger Balance', '₦' . number_format($wallet->ledger_balance, 2)],
                ['Pending Balance', '₦' . number_format($wallet->pending_balance ?? 0, 2)],
                ['Currency', $wallet->currency ?? 'NGN'],
            ]);
        } else {
            $this->warn("  No wallet found for this company!");
        }

        // ===== 2. TOTAL MONEY IN (Credits) =====
        $this->info("\n--- MONEY IN (All Credits) ---");

        // Virtual account deposits
        $vaDeposits = DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('type', 'credit')
            ->where('status', 'success')
            ->selectRaw("
                category,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                SUM(COALESCE(fee, 0)) as total_fees,
                SUM(amount - COALESCE(fee, 0)) as net_amount
            ")
            ->groupBy('category')
            ->get();

        if ($vaDeposits->count() > 0) {
            $this->table(
                ['Category', 'Count', 'Total Amount', 'Fees Charged', 'Net Credited'],
                $vaDeposits->map(fn($d) => [
                    $d->category ?? 'unknown',
                    $d->count,
                    '₦' . number_format($d->total_amount, 2),
                    '₦' . number_format($d->total_fees, 2),
                    '₦' . number_format($d->net_amount, 2),
                ])
            );
            $totalIn = $vaDeposits->sum('total_amount');
            $totalFeesIn = $vaDeposits->sum('total_fees');
            $this->info("  TOTAL MONEY IN: ₦" . number_format($totalIn, 2));
            $this->info("  TOTAL FEES ON DEPOSITS: ₦" . number_format($totalFeesIn, 2));
            $this->info("  NET CREDITED TO WALLET: ₦" . number_format($totalIn - $totalFeesIn, 2));
        } else {
            $totalIn = 0;
            $totalFeesIn = 0;
            $this->warn("  No credit transactions found.");
        }

        // ===== 3. TOTAL MONEY OUT (Debits) =====
        $this->info("\n--- MONEY OUT (All Debits) ---");

        $debits = DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('type', 'debit')
            ->where('status', 'success')
            ->selectRaw("
                category,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                SUM(COALESCE(fee, 0)) as total_fees
            ")
            ->groupBy('category')
            ->get();

        if ($debits->count() > 0) {
            $this->table(
                ['Category', 'Count', 'Total Amount', 'Fees Charged'],
                $debits->map(fn($d) => [
                    $d->category ?? 'unknown',
                    $d->count,
                    '₦' . number_format($d->total_amount, 2),
                    '₦' . number_format($d->total_fees, 2),
                ])
            );
            $totalOut = $debits->sum('total_amount');
            $totalFeesOut = $debits->sum('total_fees');
            $this->info("  TOTAL MONEY OUT: ₦" . number_format($totalOut, 2));
            $this->info("  TOTAL FEES ON DEBITS: ₦" . number_format($totalFeesOut, 2));
        } else {
            $totalOut = 0;
            $totalFeesOut = 0;
            $this->warn("  No debit transactions found.");
        }

        // ===== 4. SETTLEMENTS =====
        $this->info("\n--- SETTLEMENTS ---");
        if (\Schema::hasTable('settlement_queue')) {
            $settlements = DB::table('settlement_queue')
                ->where('company_id', $company->id)
                ->selectRaw("status, COUNT(*) as count, SUM(amount) as total")
                ->groupBy('status')
                ->get();

            if ($settlements->count() > 0) {
                $this->table(
                    ['Status', 'Count', 'Total Amount'],
                    $settlements->map(fn($s) => [$s->status, $s->count, '₦' . number_format($s->total, 2)])
                );
            } else {
                $this->warn("  No settlements found.");
            }
        }

        // ===== 5. PENDING/FAILED TRANSACTIONS =====
        $this->info("\n--- PENDING & FAILED TRANSACTIONS ---");
        $pendingFailed = DB::table('transactions')
            ->where('company_id', $company->id)
            ->whereIn('status', ['pending', 'processing', 'initiated', 'failed'])
            ->selectRaw("status, type, COUNT(*) as count, SUM(amount) as total")
            ->groupBy('status', 'type')
            ->get();

        if ($pendingFailed->count() > 0) {
            $this->table(
                ['Status', 'Type', 'Count', 'Total Amount'],
                $pendingFailed->map(fn($t) => [$t->status, $t->type, $t->count, '₦' . number_format($t->total, 2)])
            );
        } else {
            $this->info("  None found.");
        }

        // ===== 6. MANUAL ADJUSTMENTS =====
        $this->info("\n--- MANUAL ADJUSTMENTS (Admin Credits/Debits) ---");
        $manualAdj = DB::table('transactions')
            ->where('company_id', $company->id)
            ->where(function($q) {
                $q->where('category', 'like', '%manual%')
                  ->orWhere('transaction_type', 'manual_adjustment')
                  ->orWhere('description', 'like', '%manual%')
                  ->orWhere('description', 'like', '%admin%');
            })
            ->select('id', 'type', 'category', 'amount', 'fee', 'status', 'description', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($manualAdj->count() > 0) {
            $this->table(
                ['ID', 'Type', 'Category', 'Amount', 'Fee', 'Status', 'Description', 'Date'],
                $manualAdj->map(fn($t) => [
                    $t->id, $t->type, $t->category ?? '-',
                    '₦' . number_format($t->amount, 2),
                    '₦' . number_format($t->fee ?? 0, 2),
                    $t->status, substr($t->description ?? '-', 0, 30),
                    $t->created_at
                ])
            );
        } else {
            $this->info("  None found.");
        }

        // ===== 7. BALANCE RECONCILIATION =====
        $this->info("\n" . str_repeat('=', 70));
        $this->info("  BALANCE RECONCILIATION");
        $this->info(str_repeat('=', 70));

        $netCredits = (float) DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('type', 'credit')
            ->where('status', 'success')
            ->sum(DB::raw('amount - COALESCE(fee, 0)'));

        $netDebits = (float) DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('type', 'debit')
            ->whereIn('status', ['success', 'successful'])
            ->sum('amount');

        // Include completed settlements as money out (settlements debit wallet but aren't in transactions table)
        $completedSettlements = 0;
        if (\Schema::hasTable('settlement_queue')) {
            $completedSettlements = (float) DB::table('settlement_queue')
                ->where('company_id', $company->id)
                ->where('status', 'completed')
                ->sum('amount');
        }

        $calculatedBalance = $netCredits - $netDebits - $completedSettlements;
        $currentBalance = $wallet ? (float) $wallet->balance : 0;
        $difference = $currentBalance - $calculatedBalance;

        $this->table(['Metric', 'Amount'], [
            ['Total Net Credits (deposits - fees)', '₦' . number_format($netCredits, 2)],
            ['Total Debits (withdrawals/transfers)', '₦' . number_format($netDebits, 2)],
            ['Completed Settlements (paid out)', '₦' . number_format($completedSettlements, 2)],
            ['Calculated Balance (credits - debits - settlements)', '₦' . number_format($calculatedBalance, 2)],
            ['Current Wallet Balance', '₦' . number_format($currentBalance, 2)],
            ['DIFFERENCE (wallet - calculated)', '₦' . number_format($difference, 2)],
        ]);

        if (abs($difference) > 1) {
            $this->error("\n  ⚠️  MISMATCH DETECTED! Difference: ₦" . number_format(abs($difference), 2));
            if ($difference > 0) {
                $this->error("  Wallet has MORE than it should. Possible: manual funding, missing debit records.");
            } else {
                $this->error("  Wallet has LESS than it should. Possible: double debit, missing credit records.");
            }
        } else {
            $this->info("\n  ✅ Balance matches transaction history (within ₦1 tolerance).");
        }

        // ===== 8. LAST 20 TRANSACTIONS =====
        $this->info("\n--- LAST 20 TRANSACTIONS ---");
        $recent = DB::table('transactions')
            ->where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->select('id', 'type', 'category', 'amount', 'fee', 'status', 'balance_before', 'balance_after', 'description', 'created_at')
            ->get();

        if ($recent->count() > 0) {
            $this->table(
                ['ID', 'Type', 'Category', 'Amount', 'Fee', 'Status', 'Bal Before', 'Bal After', 'Description', 'Date'],
                $recent->map(fn($t) => [
                    $t->id, $t->type, substr($t->category ?? '-', 0, 15),
                    '₦' . number_format($t->amount, 2),
                    '₦' . number_format($t->fee ?? 0, 2),
                    $t->status,
                    $t->balance_before ? '₦' . number_format($t->balance_before, 2) : '-',
                    $t->balance_after ? '₦' . number_format($t->balance_after, 2) : '-',
                    substr($t->description ?? '-', 0, 25),
                    $t->created_at
                ])
            );
        }

        // ===== 9. VIRTUAL ACCOUNTS =====
        $this->info("\n--- VIRTUAL ACCOUNTS ---");
        $vas = DB::table('virtual_accounts')
            ->where('company_id', $company->id)
            ->select('id', 'account_number', 'account_name', 'bank_name', 'status', 'created_at')
            ->get();

        if ($vas->count() > 0) {
            $this->table(
                ['ID', 'Account Number', 'Account Name', 'Bank', 'Status', 'Created'],
                $vas->map(fn($v) => [$v->id, $v->account_number, $v->account_name ?? '-', $v->bank_name ?? '-', $v->status, $v->created_at])
            );
        } else {
            $this->warn("  No virtual accounts found.");
        }

        $this->info("\n" . str_repeat('=', 70));
        $this->info("  AUDIT COMPLETE");
        $this->info(str_repeat('=', 70) . "\n");

        return 0;
    }
}
