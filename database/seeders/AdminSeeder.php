<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if admin already exists
        $existingAdmin = DB::table('users')->where('email', 'admin@pointwave.com')->first();

        if ($existingAdmin) {
            $this->command->info('Admin user already exists!');
            return;
        }

        // Create super admin user
        $userId = DB::table('users')->insertGetId([
            'username' => 'admin',
            'email' => 'admin@pointwave.com',
            'phone' => '08000000000',
            'password' => Hash::make('@Habukhan2025'),
            'name' => 'System Administrator',
            'type' => 'admin',
            'status' => 'active',
            'email_verified' => true,
            'phone_verified' => true,
            'address' => 'System Administrator',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'postal_code' => '100001',
            'balance' => 0,
            'referral_balance' => 0,
            'kyc_status' => 'verified',
            'kyc_tier' => 'tier3',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info('✅ Super Admin created successfully!');
        $this->command->info('Email: admin@pointwave.com');
        $this->command->info('Password: @Habukhan2025');

        // Create company for admin
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'PointWave Admin',
            'email' => 'admin@pointwave.com',
            'phone' => '08000000000',
            'address' => 'Lagos, Nigeria',
            'public_key' => 'pk_live_' . bin2hex(random_bytes(16)),
            'secret_key' => 'sk_live_' . bin2hex(random_bytes(30)),
            'api_key' => 'ak_live_' . bin2hex(random_bytes(16)),
            'webhook_secret' => 'whsec_' . bin2hex(random_bytes(16)),
            'webhook_enabled' => true,
            'status' => 'active',
            'kyc_status' => 'verified',
            'transaction_fee_percentage' => 0,
            'minimum_balance' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create wallet for admin company
        DB::table('company_wallets')->insert([
            'company_id' => $companyId,
            'currency' => 'NGN',
            'balance' => 0,
            'ledger_balance' => 0,
            'pending_balance' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info('✅ Admin company and wallet created!');
    }
}
