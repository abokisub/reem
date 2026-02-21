<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupKycCharges extends Command
{
    protected $signature = 'kyc:setup-charges {--force : Overwrite existing charges}';
    protected $description = 'Setup KYC service charges in the database';

    public function handle()
    {
        $this->info('========================================');
        $this->info('  KYC CHARGES SETUP');
        $this->info('========================================');
        $this->newLine();

        // Check if service_charges table exists
        if (!DB::getSchemaBuilder()->hasTable('service_charges')) {
            $this->error('❌ service_charges table does not exist!');
            $this->warn('Run: php artisan migrate');
            return 1;
        }

        // Define KYC charges
        $kycCharges = [
            [
                'service_name' => 'enhanced_bvn',
                'charge_value' => 100.00,
                'description' => 'Enhanced BVN Verification (Full Details)'
            ],
            [
                'service_name' => 'enhanced_nin',
                'charge_value' => 100.00,
                'description' => 'Enhanced NIN Verification (Full Details)'
            ],
            [
                'service_name' => 'basic_bvn',
                'charge_value' => 50.00,
                'description' => 'Basic BVN Verification (Name Match Only)'
            ],
            [
                'service_name' => 'basic_nin',
                'charge_value' => 50.00,
                'description' => 'Basic NIN Verification (Name Match Only)'
            ],
            [
                'service_name' => 'bank_account_verification',
                'charge_value' => 50.00,
                'description' => 'Bank Account Verification'
            ],
            [
                'service_name' => 'face_recognition',
                'charge_value' => 50.00,
                'description' => 'Face Recognition (Compare Two Faces)'
            ],
            [
                'service_name' => 'liveness_detection',
                'charge_value' => 100.00,
                'description' => 'Liveness Detection (Anti-Spoofing)'
            ],
            [
                'service_name' => 'blacklist_check',
                'charge_value' => 50.00,
                'description' => 'Credit Blacklist Check'
            ],
            [
                'service_name' => 'credit_score',
                'charge_value' => 100.00,
                'description' => 'Credit Score Query'
            ],
            [
                'service_name' => 'loan_features',
                'charge_value' => 50.00,
                'description' => 'Loan Features Query'
            ],
        ];

        $force = $this->option('force');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($kycCharges as $charge) {
            // Check if charge already exists
            $existing = DB::table('service_charges')
                ->where('company_id', 1)
                ->where('service_category', 'kyc')
                ->where('service_name', $charge['service_name'])
                ->first();

            if ($existing) {
                if ($force) {
                    DB::table('service_charges')
                        ->where('id', $existing->id)
                        ->update([
                            'charge_value' => $charge['charge_value'],
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);
                    $this->line("  ✅ Updated: {$charge['service_name']} → ₦{$charge['charge_value']}");
                    $updated++;
                } else {
                    $this->line("  ⏭️  Skipped: {$charge['service_name']} (already exists)");
                    $skipped++;
                }
            } else {
                DB::table('service_charges')->insert([
                    'company_id' => 1, // Global default
                    'service_category' => 'kyc',
                    'service_name' => $charge['service_name'],
                    'charge_type' => 'flat',
                    'charge_value' => $charge['charge_value'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line("  ✅ Created: {$charge['service_name']} → ₦{$charge['charge_value']}");
                $created++;
            }
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('  SUMMARY');
        $this->info('========================================');
        $this->newLine();

        if ($created > 0) {
            $this->info("✅ Created: $created charges");
        }
        if ($updated > 0) {
            $this->info("✅ Updated: $updated charges");
        }
        if ($skipped > 0) {
            $this->line("⏭️  Skipped: $skipped charges (use --force to overwrite)");
        }

        $this->newLine();
        $this->info('KYC charges setup complete!');
        $this->newLine();

        // Show next steps
        $this->info('NEXT STEPS:');
        $this->line('1. Test configuration: php artisan kyc:test-charges');
        $this->line('2. Clear caches: php artisan config:clear && php artisan cache:clear');
        $this->line('3. View charges: php artisan tinker');
        $this->line('   >>> DB::table("service_charges")->where("service_category", "kyc")->get();');
        $this->newLine();

        return 0;
    }
}
