<?php

namespace Tests\Phase1;

use Tests\TestCase;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\VirtualAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Phase 1: Virtual Account Creation & Customer ID Management
 * 
 * Tests:
 * - Customer creation with unique identifiers
 * - Virtual account creation (static and dynamic)
 * - Multi-tenant isolation
 * - Duplicate prevention
 */
class VirtualAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company with API keys
        $this->company = Company::factory()->create([
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->company->api_secret_key,
            'x-api-key' => $this->company->api_key,
            'x-business-id' => $this->company->business_id,
            'Accept' => 'application/json',
        ];
    }

    /** @test */
    public function it_can_create_a_customer_with_unique_uuid()
    {
        // Create temporary test files
        $idCard = \Illuminate\Http\UploadedFile::fake()->image('id_card.jpg');
        $utilityBill = \Illuminate\Http\UploadedFile::fake()->image('utility_bill.jpg');

        $response = $this->postJson('/api/v1/customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone_number' => '08012345678',
            'address' => '123 Test Street',
            'state' => 'Lagos',
            'city' => 'Ikeja',
            'postal_code' => '100001',
            'date_of_birth' => '1990-01-01',
            'id_type' => 'bvn',
            'id_number' => '12345678901',
            'id_card' => $idCard,
            'utility_bill' => $utilityBill,
        ], $this->headers);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'customer_id',
                    'kyc_status',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('company_users', [
            'email' => 'john@example.com',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_customer_creation()
    {
        // Create first customer
        CompanyUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'john@example.com',
        ]);

        // Create temporary test files
        $idCard = \Illuminate\Http\UploadedFile::fake()->image('id_card.jpg');
        $utilityBill = \Illuminate\Http\UploadedFile::fake()->image('utility_bill.jpg');

        // Attempt to create duplicate
        $response = $this->postJson('/api/v1/customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone_number' => '08012345678',
            'address' => '123 Test Street',
            'state' => 'Lagos',
            'city' => 'Ikeja',
            'postal_code' => '100001',
            'date_of_birth' => '1990-01-01',
            'id_type' => 'bvn',
            'id_number' => '12345678901',
            'id_card' => $idCard,
            'utility_bill' => $utilityBill,
        ], $this->headers);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_create_static_virtual_account()
    {
        $customer = CompanyUser::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/v1/virtual-accounts', [
            'customer_id' => $customer->uuid,
            'account_type' => 'static',
            'account_name' => 'John Doe',
        ], $this->headers);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'account_number',
                    'account_name',
                    'bank_name',
                    'account_type',
                ],
            ]);

        $this->assertDatabaseHas('virtual_accounts', [
            'company_user_id' => $customer->id,
            'company_id' => $this->company->id,
            'account_type' => 'static',
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenant_isolation()
    {
        $otherCompany = Company::factory()->create();
        $customer = CompanyUser::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Try to access another company's customer
        $response = $this->postJson('/api/v1/virtual-accounts', [
            'customer_id' => $customer->uuid,
            'account_type' => 'static',
            'account_name' => 'John Doe',
        ], $this->headers);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/customers', [
            'first_name' => 'John',
            // Missing required fields
        ], $this->headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name', 'email', 'phone_number']);
    }
}
