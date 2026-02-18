<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\LedgerAccount;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    protected $ledger;

    public function __construct(LedgerService $ledger)
    {
        $this->ledger = $ledger;
    }

    /**
     * Process daily settlements for all active companies.
     */
    public function processSettlements(string $date)
    {
        $companies = Company::where('status', 'active')->get();
        $results = [];

        foreach ($companies as $company) {
            $results[$company->name] = $this->settleCompany($company, $date);
        }

        return $results;
    }

    protected function settleCompany(Company $company, string $date)
    {
        // 1. Calculate net balance to settle (Cleared funds)
        $wallet = $this->ledger->getOrCreateAccount($company->name . ' Wallet', 'company_wallet', $company->id);

        if ($wallet->balance <= 0) {
            return ['status' => 'skipped', 'reason' => 'No balance'];
        }

        $amountToSettle = $wallet->balance;

        // --- NEW: Multi-tenant Payout Charges ---
        $settings = \Illuminate\Support\Facades\DB::table('settings')->where('company_id', $company->id)->first();
        if (!$settings) {
            $settings = \Illuminate\Support\Facades\DB::table('settings')->where('company_id', 1)->first();
        }

        $type = $settings->payout_bank_charge_type ?? 'FLAT';
        $val = $settings->payout_bank_charge_value ?? 0;
        $cap = $settings->payout_bank_charge_cap ?? 0;

        $charge = 0;
        if ($type == 'PERCENTAGE') {
            $charge = ($amountToSettle / 100) * $val;
            if ($cap > 0 && $charge > $cap) {
                $charge = $cap;
            }
        } else {
            $charge = $val;
        }

        $netAmount = $amountToSettle - $charge;
        if ($netAmount < 0)
            $netAmount = 0;

        $settlementRef = 'PWV_SET_' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));

        try {
            // 2. Debit Company Wallet -> Settlement Account & Revenue Account
            $settlementAccount = $this->ledger->getOrCreateAccount('Settlement Clearing', 'settlement');
            $revenueAccount = $this->ledger->getOrCreateAccount('System Revenue', 'revenue');

            \Illuminate\Support\Facades\DB::transaction(function () use ($settlementRef, $wallet, $settlementAccount, $revenueAccount, $netAmount, $charge, $date) {
                if ($netAmount > 0) {
                    $this->ledger->recordEntry($settlementRef, $wallet->id, $settlementAccount->id, $netAmount, "Settlement for {$date}");
                }
                if ($charge > 0) {
                    $this->ledger->recordEntry($settlementRef . '_FEE', $wallet->id, $revenueAccount->id, $charge, "Settlement Fee for {$date}");
                }
            });

            return [
                'status' => 'success',
                'amount' => $netAmount,
                'charge' => $charge,
                'reference' => $settlementRef
            ];

        } catch (\Exception $e) {
            Log::error('Settlement Failed', ['company' => $company->name, 'error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
