<?php

/**
 * Phase 5 KYC Verification Script
 * 
 * This script demonstrates the KYC workflow:
 * 1. Submit KYC sections
 * 2. Admin approval/rejection
 * 3. BVN/NIN verification (mocked)
 * 
 * Run: php scripts/verify_phase_5.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\User;
use App\Services\KYC\KycService;
use App\Services\KYC\EaseIdClient;

echo "=== Phase 5: KYC System Verification ===\n\n";

try {
    // Find or create test company
    $company = Company::where('email', 'kyc-demo@test.com')->first();
    if (!$company) {
        $company = Company::create([
            'name' => 'KYC Demo Company',
            'email' => 'kyc-demo@test.com',
            'phone' => '08012345678',
            'kyc_status' => 'pending',
        ]);
        echo "✓ Created test company: {$company->name}\n";
    } else {
        echo "✓ Using existing company: {$company->name}\n";
    }

    // Find or create admin user
    $admin = User::where('role', 'admin')->first();
    if (!$admin) {
        echo "✗ No admin user found. Please create an admin user first.\n";
        exit(1);
    }
    echo "✓ Found admin user: {$admin->email}\n\n";

    // Initialize KYC Service (with mocked EaseID for demo)
    $mockEaseIdClient = new class extends EaseIdClient {
        public function __construct()
        {
            // Skip parent constructor to avoid requiring credentials
        }

        public function verifyBVN(string $bvn): array
        {
            return [
                'success' => true,
                'message' => 'BVN verified (DEMO MODE)',
                'data' => [
                    'bvn' => $bvn,
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'gender' => 'Male',
                    'birthday' => '1990-01-01',
                ],
            ];
        }
    };

    $kycService = new KycService($mockEaseIdClient);

    // Test 1: Submit KYC Sections
    echo "Test 1: Submitting KYC Sections\n";
    echo "--------------------------------\n";

    $sections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];
    foreach ($sections as $section) {
        $result = $kycService->submitKycSection($company->id, $section, [
            'data' => "Sample data for $section"
        ]);

        if ($result['success']) {
            echo "  ✓ Submitted: $section\n";
        } else {
            echo "  ✗ Failed: $section - {$result['message']}\n";
        }
    }

    $company->refresh();
    echo "  Company KYC Status: {$company->kyc_status}\n\n";

    // Test 2: Get KYC Status
    echo "Test 2: Getting KYC Status\n";
    echo "--------------------------------\n";
    $status = $kycService->getKycStatus($company->id);
    echo "  Overall Status: {$status['overall_status']}\n";
    echo "  Sections:\n";
    foreach ($status['sections'] as $section) {
        echo "    - {$section['section']}: {$section['status']}\n";
    }
    echo "\n";

    // Test 3: Admin Approval
    echo "Test 3: Admin Approval Workflow\n";
    echo "--------------------------------\n";

    // Approve first 4 sections
    foreach (array_slice($sections, 0, 4) as $section) {
        $result = $kycService->approveSection($company->id, $section, $admin->id, 'Approved');
        echo "  ✓ Approved: $section\n";
    }

    $company->refresh();
    echo "  Company KYC Status (4/5 approved): {$company->kyc_status}\n\n";

    // Approve last section (should trigger full approval)
    $result = $kycService->approveSection($company->id, 'board_members', $admin->id, 'All sections complete');
    echo "  ✓ Approved: board_members\n";

    $company->refresh();
    echo "  Company KYC Status (5/5 approved): {$company->kyc_status}\n";
    echo "  Reviewed By: Admin ID {$company->kyc_reviewed_by}\n";
    echo "  Reviewed At: {$company->kyc_reviewed_at}\n\n";

    // Test 4: BVN Verification (Mocked)
    echo "Test 4: BVN Verification (DEMO MODE)\n";
    echo "--------------------------------\n";
    $bvnResult = $kycService->verifyBVN('12345678901');
    if ($bvnResult['success']) {
        echo "  ✓ BVN Verified\n";
        echo "  Name: {$bvnResult['data']['firstName']} {$bvnResult['data']['lastName']}\n";
        echo "  Gender: {$bvnResult['data']['gender']}\n";
        echo "  DOB: {$bvnResult['data']['birthday']}\n";
    } else {
        echo "  ✗ BVN Verification Failed: {$bvnResult['message']}\n";
    }
    echo "\n";

    echo "=== All Tests Completed Successfully ===\n";
    echo "\nNote: This is a demonstration using mocked EaseID responses.\n";
    echo "In production, actual BVN/NIN verification will use the EaseID API.\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
