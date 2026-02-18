<?php

namespace Tests\Feature\Phase1;

use Tests\TestCase;
use App\Services\PalmPay\VirtualAccountService;
use App\Services\PalmPay\PalmPayClient;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class VirtualAccountTest extends TestCase
{
    // use RefreshDatabase; // Caution with existing DB

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_virtual_account_creation_phase_1()
    {
        // 1. Create a User (company owner) with Customer ID (auto-generated)
        $user = new User();
        $user->first_name = 'Test';
        $user->last_name = 'Owner';
        $user->name = 'Test Owner';
        $user->username = 'owner_' . time();
        $user->phone = '080' . rand(10000000, 99999999);
        $user->email = 'owner_' . time() . '@test.com';
        $user->password = bcrypt('password');
        $user->save();

        $this->assertNotNull($user->customer_id, 'Customer ID should be generated');

        // 2. Create a Company (required for VA creation)
        $company = new Company();
        $company->user_id = $user->id;
        $company->name = 'Test Company';
        $company->email = $user->email;
        $company->phone = $user->phone; // Ensure required fields are set
        $company->business_registration_number = 'RC123456'; // Required for aggregator mode fallback
        $company->save();

        // 3. Mock PalmPay Client
        $mockClient = Mockery::mock(PalmPayClient::class);
        $mockClient->shouldReceive('post')
            ->once()
            ->with('/api/v2/virtual/account/label/create', Mockery::any())
            ->andReturn([
                'respCode' => '00000000',
                'respMsg' => 'Success',
                'data' => [
                    'virtualAccountNo' => '1234567890',
                    'virtualAccountName' => 'Test Customer',
                    'status' => 'active',
                    'orderNo' => 'ORD-' . time(),
                ]
            ]);

        // 4. Inject Mock into Service
        $service = new VirtualAccountService($mockClient);

        // 5. Call Service to Create VA
        $customerData = [
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '08012345678',
            'bvn' => '12345678901', // Valid length
        ];

        $va = $service->createVirtualAccount($company->id, $user->id, $customerData);

        // 6. Verify Result
        $this->assertEquals('1234567890', $va->palmpay_account_number);
        $this->assertEquals('active', $va->status);

        // Clean up
        $va->delete();
        $company->delete();
        $user->delete();
    }
}
