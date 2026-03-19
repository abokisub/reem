<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWalletBalance extends Command
{
    protected $signature = 'fix:wallet {company_id : Company ID to fix}';
    protected $description = 'Recalculate and fix a company wallet balance based on actual transaction history';

    public function handle()
    {
        $companyId = $this->argument('company_id');

        $company = DB::table('companies')->where('id', $companyId)->first();
        if (!$company) {
            $this->error("Company not found: {$companyId}");
            return 1;
        }

        $wallet = DB::table('company_wallets')->where('company_id', $companyId)->first();
        if (!$wallet) {
            $this->error("No wallet found for company: {$company->name}");
            return 1;
        }

        $this->info("\n=== WALLET FIX: {$company->name} (ID: {$companyId}) ===\n");

        // Calculate what the balance SHOULD be from transaction history
        // Credits: successful credit transactions (net of fees)
        $netCredits = (float) DB::table('transactions')
            ->where('company_id', $companyId)
            ->where('type', 'credit')
            ->where('status', 'success')
            ->sum(DB::raw('amount - COALESCE(fee, 0)'));

        // Debits: only SUCCESSFUL debit transactions (not failed, not pending)
        $netDebits = (float) DB::table('transactions')
            ->where('company_id', $companyId)
            ->where('type', 'debit')
            ->where('status', 'success')
            ->sum(DB::raw('amount + COALESCE(fee, 0)'));

        $calculatedBalance = $netCredits - $netDebits;
        $currentBalance = (float) $wallet->balance;
        $difference = $currentBalance - $calculatedBalance;

        $this->table(['Metric', 'Amount'], [
            ['Net Credits (deposits - deposit fees)', '₦' . number_format($netCredits, 2)],
            ['Net Debits (withdrawals + withdrawal fees)', '₦' . number_format($netDebits, 2)],
            ['Calculated Correct Balance', '₦' . number_format($calculatedBalance, 2)],
            ['Current Wallet Balance', '₦' . number_format($currentBalance, 2)],
            ['Difference (overage)', '₦' . number_format($difference, 2)],
        ]);

        if (abs($difference) < 1) {
            $this->info("\n✅ Balance is already correct. No fix needed.");
            return 0;
        }

        if ($difference > 0) {
            $this->error("\n⚠️  Wallet has ₦" . number_format($difference, 2) . " MORE than it should.");
            $this->warn("This is likely from the double-refund bug on failed transfers.");
        } else {
            $this->error("\n⚠️  Wallet has ₦" . number_format(abs($difference), 2) . " LESS than it should.");
        }

        if (!$this->confirm("Do you want to correct the balance to ₦" . number_format($calculatedBalance, 2) . "?")) {
            $this->info("Aborted.");
            return 0;
        }

        // Fix the balance
        DB::table('company_wallets')
            ->where('company_id', $companyId)
            ->update([
                'balance' => $calculatedBalance,
                'ledger_balance' => $calculatedBalance,
                'updated_at' => now()
            ]);

        // Log the correction as a transaction for audit trail
        DB::table('transactions')->insert([
            'transaction_id' => 'txn_correction_' . uniqid(),
            'company_id' => $companyId,
            'type' => $difference > 0 ? 'debit' : 'credit',
            'category' => 'wallet_correction',
            'transaction_type' => 'manual_adjustment',
            'amount' => abs($difference),
            'fee' => 0,
            'total_amount' => abs($difference),
            'net_amount' => abs($difference),
            'currency' => 'NGN',
            'status' => 'success',
            'reference' => 'CORRECTION_' . strtoupper(uniqid()),
            'description' => 'Wallet balance correction - fixing double-refund bug. Old balance: ₦' . number_format($currentBalance, 2) . ', Corrected to: ₦' . number_format($calculatedBalance, 2),
            'balance_before' => $currentBalance,
            'balance_after' => $calculatedBalance,
            'settlement_status' => 'not_applicable',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->info("\n✅ Balance corrected from ₦" . number_format($currentBalance, 2) . " to ₦" . number_format($calculatedBalance, 2));
        $this->info("A correction transaction has been recorded for audit trail.\n");

        return 0;
    }
}
