<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncBanksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banks:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all Nigerian banks from PalmPay API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🏦 Starting Bank Sync from PalmPay...');

        try {
            // Sync from PalmPay ONLY (our default partner)
            $transferService = new \App\Services\PalmPay\TransferService();
            $banks = $transferService->getBankList();

            if (empty($banks)) {
                $this->error('❌ No banks returned from PalmPay API');
                return 1;
            }

            $this->info('📥 Received ' . count($banks) . ' banks from PalmPay');

            $synced = 0;
            $updated = 0;

            foreach ($banks as $bank) {
                $bankCode = $bank['bankCode'] ?? null;
                $bankName = $bank['bankName'] ?? null;

                if (!$bankCode || !$bankName) {
                    continue;
                }

                $existing = DB::table('banks')->where('code', $bankCode)->first();

                if ($existing) {
                    DB::table('banks')
                        ->where('code', $bankCode)
                        ->update([
                            'name' => $bankName,
                            'active' => true,
                            'updated_at' => now()
                        ]);
                    $updated++;
                } else {
                    DB::table('banks')->insert([
                        'name' => $bankName,
                        'code' => $bankCode,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $synced++;
                }
            }

            $this->info("✅ Synced $synced new banks");
            $this->info("✅ Updated $updated existing banks");
            $this->info('🎉 Bank Sync Completed Successfully!');

            Log::info('Bank Sync Completed', [
                'synced' => $synced,
                'updated' => $updated,
                'total' => count($banks)
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Bank Sync Failed: ' . $e->getMessage());
            Log::error('Bank Sync Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}
