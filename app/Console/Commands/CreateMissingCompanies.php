<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateMissingCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'companies:create-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create companies for users who don\'t have one';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Finding users without companies...');
        
        $users = User::whereDoesntHave('company')->get();
        
        if ($users->isEmpty()) {
            $this->info('All users already have companies!');
            return 0;
        }
        
        $this->info("Found {$users->count()} users without companies.");
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        $created = 0;
        $failed = 0;
        
        foreach ($users as $user) {
            try {
                DB::beginTransaction();
                
                // Generate API keys
                $keys = Company::generateApiKeys();
                $testKeys = Company::generateApiKeys('test_');
                
                // Create company
                $company = Company::create([
                    'user_id' => $user->id,
                    'name' => $user->business_name ?? $user->name . "'s Business",
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'business_type' => 'individual',
                    'business_category' => 'general',
                    'status' => 'pending',
                    'kyc_status' => 'unverified',
                    'api_public_key' => $keys['api_public_key'],
                    'api_secret_key' => $keys['api_secret_key'],
                    'test_public_key' => $testKeys['api_public_key'],
                    'test_secret_key' => $testKeys['api_secret_key'],
                ]);
                
                // Create company wallet
                DB::table('company_wallets')->insert([
                    'company_id' => $company->id,
                    'currency' => 'NGN',
                    'balance' => 0,
                    'ledger_balance' => 0,
                    'pending_balance' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Set as active company
                $user->update(['active_company_id' => $company->id]);
                
                DB::commit();
                $created++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("\nFailed to create company for user {$user->id}: " . $e->getMessage());
                $failed++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("Successfully created {$created} companies.");
        
        if ($failed > 0) {
            $this->warn("Failed to create {$failed} companies.");
        }
        
        return 0;
    }
}
