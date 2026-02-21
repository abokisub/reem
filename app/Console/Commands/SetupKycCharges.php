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
                'display_name' => 'Enhanced BVN Verification',
                'charge_value' => 100.00,
            ],
            [
                'service_name' => 'enhanced_nin',
                'display_name' => 'Enhanced NIN Verification',
                'charge_value' => 100.00,
            ],
            [
                'service_name' => 'basic_bvn',
                'display_name' => 'Basic BVN Verification',
                'charge_value' => 50.00,
            ],
            [
                'service_name' => 'basic_nin',
                'display_name' => 'Basic NIN Verification',
                'charge_value' => 50.00,
            ],
            [
                'service_name' => 'bank_account_verification',
                'display_name' => 'Bank Account Verification',
                'charge_value' => 50.00,
            ],
            [
                'service_name' => 'face_recognition',
                'display_name' => 'Face Recognition',
                'charge_value' => 50.00,
            ],
            [
                'service_name' => 'liveness_detection',
                'display_name' => 'Liveness Detection',
                'charge_value' => 100.00,
            ],
            [
                'service_name' => 'blacklist_check',
                'display_name' => 'Blacklist Check',
                'charge_value' => 50.00,
            ],
            [
                'service_name' => 'credit_score',
                'display_name' => 'Credit Score Query',
                'charge_value' => 100.00,
            ],
            [
                'service_name' => 'loan_features',
                'display_name' => 'Loan Features Query',
                'charge_value' => 50.00,
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
                    'display_name' => $charge['display_name'],
                    'charge_type' => 'FLAT',
                    'charge_value' => $charge['charge_value'],
                    'is_active' => true,
                    'sort_order' => 0,
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
