<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLedgerBalances extends Command
{
    protected $signature = 'fix:ledger';
    protected $description = 'Fix corrupted ledger_balance and pending_balance columns in company_wallets';

    public function handle()
    {
        $this->info("\n=== FIXING LEDGER & PENDING BALANCES ===\n");

        $wallets = DB::table('company_wallets')
            ->join('companies', 'company_wallets.company_id', '=', 'companies.id')
            ->select('company_wallets.*', 'companies.name')
            ->get();

        $this->table(
            ['Company', 'Balance', 'Ledger (BEFORE)', 'Pending (BEFORE)', 'Action'],
            $wallets->map(function ($w) {
                $needsFix = ($w->ledger_balance != $w->balance) || ($w->pending_balance != 0);
                return [
                    $w->name,
                    '₦' . number_format($w->balance, 2),
                    '₦' . number_format($w->ledger_balance, 2),
                    '₦' . number_format($w->pending_balance, 2),
                    $needsFix ? '⚠️ NEEDS FIX' : '✅ OK'
                ];
            })
        );

        if (!$this->confirm("\nSet ledger_balance = balance and pending_balance = 0 for all wallets?")) {
            $this->info("Aborted.");
            return 0;
        }

        foreach ($wallets as $w) {
            DB::table('company_wallets')
                ->where('id', $w->id)
                ->update([
                    'ledger_balance' => $w->balance,
                    'pending_balance' => 0,
                    'updated_at' => now()
                ]);
        }

        $this->info("\n✅ All wallets fixed. Verifying...\n");

        $after = DB::table('company_wallets')
            ->join('companies', 'company_wallets.company_id', '=', 'companies.id')
            ->select('companies.name', 'company_wallets.balance', 'company_wallets.ledger_balance', 'company_wallets.pending_balance')
            ->get();

        $this->table(
            ['Company', 'Balance', 'Ledger (AFTER)', 'Pending (AFTER)'],
            $after->map(fn($w) => [
                $w->name,
                '₦' . number_format($w->balance, 2),
                '₦' . number_format($w->ledger_balance, 2),
                '₦' . number_format($w->pending_balance, 2),
            ])
        );

        return 0;
    }
}
