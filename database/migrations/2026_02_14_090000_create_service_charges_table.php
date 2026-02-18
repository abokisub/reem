<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_charges', function (Blueprint $table) {
            $table->id();
            $table->string('service_category'); // 'kyc', 'payment', 'payout', 'vending'
            $table->string('service_name'); // 'enhanced_bvn', 'palmpay_va', 'pay_with_transfer'
            $table->string('display_name'); // 'Enhanced BVN Verification'
            $table->enum('charge_type', ['FLAT', 'PERCENT'])->default('FLAT');
            $table->decimal('charge_value', 20, 2)->default(0);
            $table->decimal('charge_cap', 20, 2)->nullable(); // Max charge for percentage
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index(['service_category', 'service_name']);
            $table->index('is_active');
        });

        // Seed initial data
        $serviceCharges = [
            // KYC Services (10 services)
            [
                'service_category' => 'kyc',
                'service_name' => 'enhanced_bvn',
                'display_name' => 'Enhanced BVN Verification',
                'charge_type' => 'FLAT',
                'charge_value' => 100.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'enhanced_nin',
                'display_name' => 'Enhanced NIN Verification',
                'charge_type' => 'FLAT',
                'charge_value' => 100.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'basic_bvn',
                'display_name' => 'Basic BVN Verification',
                'charge_type' => 'FLAT',
                'charge_value' => 50.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'basic_nin',
                'display_name' => 'Basic NIN Verification',
                'charge_type' => 'FLAT',
                'charge_value' => 50.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'liveness_detection',
                'display_name' => 'Liveness Detection',
                'charge_type' => 'FLAT',
                'charge_value' => 150.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'face_comparison',
                'display_name' => 'Face Comparison',
                'charge_type' => 'FLAT',
                'charge_value' => 80.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'bank_account_verification',
                'display_name' => 'BVN and Bank Account Verification',
                'charge_type' => 'FLAT',
                'charge_value' => 120.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'credit_score',
                'display_name' => 'Credit Score Services',
                'charge_type' => 'FLAT',
                'charge_value' => 200.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'loan_feature',
                'display_name' => 'Loan Feature',
                'charge_type' => 'PERCENT',
                'charge_value' => 2.50,
                'charge_cap' => 5000.00,
                'is_active' => true,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_category' => 'kyc',
                'service_name' => 'blacklist',
                'display_name' => 'Blacklist Check',
                'charge_type' => 'FLAT',
                'charge_value' => 50.00,
                'charge_cap' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Payment Methods
            [
                'service_category' => 'payment',
                'service_name' => 'palmpay_va',
                'display_name' => 'PalmPay Virtual Account',
                'charge_type' => 'PERCENT',
                'charge_value' => 0.50,
                'charge_cap' => 500.00,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('service_charges')->insert($serviceCharges);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_charges');
    }
};
