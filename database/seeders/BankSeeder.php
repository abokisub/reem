<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bank;
use App\Services\PalmPay\TransferService;
use Illuminate\Support\Facades\Log;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transferService = new TransferService();

        try {
            // Attempt to get from PalmPay
            $banks = $transferService->getBankList();

            if (!empty($banks)) {
                $this->seedFromData($banks);
                $this->command->info('Successfully seeded ' . count($banks) . ' banks from PalmPay.');
                return;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch banks from PalmPay: ' . $e->getMessage() . '. Using static list.');
        }

        // Fallback: Static list of common Nigerian banks
        $staticBanks = [
            ['bankCode' => '000001', 'bankName' => 'Access Bank'],
            ['bankCode' => '000002', 'bankName' => 'Agricultural and Rural Management Training Institute'],
            ['bankCode' => '000003', 'bankName' => 'Covenant Microfinance Bank'],
            ['bankCode' => '000004', 'bankName' => 'Empire Trust Bank'],
            ['bankCode' => '000005', 'bankName' => 'FBNQuest Merchant Bank'],
            ['bankCode' => '000006', 'bankName' => 'First Bank of Nigeria'],
            ['bankCode' => '000007', 'bankName' => 'First City Monument Bank'],
            ['bankCode' => '000008', 'bankName' => 'Guaranty Trust Bank'],
            ['bankCode' => '000010', 'bankName' => 'Jaiz Bank'],
            ['bankCode' => '000011', 'bankName' => 'Keystone Bank'],
            ['bankCode' => '000012', 'bankName' => 'Stanbic IBTC Bank'],
            ['bankCode' => '000013', 'bankName' => 'Sterling Bank'],
            ['bankCode' => '000014', 'bankName' => 'United Bank for Africa'],
            ['bankCode' => '000015', 'bankName' => 'Union Bank of Nigeria'],
            ['bankCode' => '000016', 'bankName' => 'Wema Bank'],
            ['bankCode' => '000017', 'bankName' => 'Zenith Bank'],
            ['bankCode' => '000018', 'bankName' => 'Unity Bank'],
            ['bankCode' => '000019', 'bankName' => 'Enterprise Bank'],
            ['bankCode' => '000020', 'bankName' => 'Heritage Bank'],
            ['bankCode' => '000021', 'bankName' => 'Standard Chartered Bank'],
            ['bankCode' => '000022', 'bankName' => 'Ecobank Nigeria'],
            ['bankCode' => '000023', 'bankName' => 'Fidelity Bank'],
            ['bankCode' => '000024', 'bankName' => 'Mainstreet Bank'],
            ['bankCode' => '000025', 'bankName' => 'Polaris Bank'],
            ['bankCode' => '000026', 'bankName' => 'Titan Trust Bank'],
            ['bankCode' => '000027', 'bankName' => 'Globus Bank'],
            ['bankCode' => '000028', 'bankName' => 'SunTrust Bank'],
            ['bankCode' => '000029', 'bankName' => 'Providus Bank'],
            ['bankCode' => '000030', 'bankName' => 'Novus Bank'],
            ['bankCode' => '000031', 'bankName' => 'Kuda Microfinance Bank'],
            ['bankCode' => '100001', 'bankName' => 'PalmPay'], // PalmPay itself
        ];

        $this->seedFromData($staticBanks);
        $this->command->info('Successfully seeded ' . count($staticBanks) . ' banks from static list.');
    }

    /**
     * Seed banks from array data
     */
    private function seedFromData(array $banks): void
    {
        foreach ($banks as $bankData) {
            Bank::updateOrCreate(
                ['code' => $bankData['bankCode']],
                [
                    'name' => $bankData['bankName'],
                    'palmpay_code' => $bankData['bankCode'],
                    'active' => true,
                    'supports_transfers' => true,
                    'supports_account_verification' => true,
                ]
            );
        }
    }
}
