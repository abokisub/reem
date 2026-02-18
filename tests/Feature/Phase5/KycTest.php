<?php

namespace Tests\Feature\Phase5;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Models\CompanyKycApproval;
use App\Models\CompanyKycHistory;
use App\Services\KYC\KycService;
use App\Services\KYC\EaseIdClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KycTest extends TestCase
{
    use DatabaseTransactions;

    protected $company;
    protected $user;
    protected $admin;
    protected $kycService;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = Str::random(8);

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company KYC ' . $suffix,
            'email' => 'kyc-test-' . $suffix . '@test.com',
            'kyc_status' => 'pending',
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'active_company_id' => $this->company->id,
            'email' => 'user-kyc-' . $suffix . '@test.com',
            'username' => 'kyc_user_' . $suffix,
            'type' => 'user',
        ]);

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin-kyc-' . $suffix . '@test.com',
            'username' => 'kyc_admin_' . $suffix,
            'type' => 'admin',
        ]);

        // Mock EaseIdClient
        $mockEaseIdClient = $this->createMock(EaseIdClient::class);
        $this->kycService = new KycService($mockEaseIdClient);
    }

    /** @test */
    public function test_submit_kyc_section_creates_approval_record()
    {
        $result = $this->kycService->submitKycSection(
            $this->company->id,
            'business_info',
            ['company_name' => 'Test Company']
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('KYC section submitted successfully', $result['message']);

        // Verify approval record created
        $approval = CompanyKycApproval::where('company_id', $this->company->id)
            ->where('section', 'business_info')
            ->first();

        $this->assertNotNull($approval);
        $this->assertEquals('pending', $approval->status);

        // Verify history logged
        $history = CompanyKycHistory::where('company_id', $this->company->id)
            ->where('section', 'business_info')
            ->where('action', 'submitted')
            ->first();

        $this->assertNotNull($history);
    }

    /** @test */
    public function test_submit_kyc_updates_company_status_to_under_review()
    {
        $this->assertEquals('pending', $this->company->fresh()->kyc_status);

        $this->kycService->submitKycSection(
            $this->company->id,
            'business_info',
            ['company_name' => 'Test Company']
        );

        $this->assertEquals('under_review', $this->company->fresh()->kyc_status);
    }

    /** @test */
    public function test_approve_section_updates_status()
    {
        // Submit section first
        $this->kycService->submitKycSection(
            $this->company->id,
            'business_info',
            ['company_name' => 'Test Company']
        );

        // Approve section
        $result = $this->kycService->approveSection(
            $this->company->id,
            'business_info',
            $this->admin->id,
            'Looks good'
        );

        $this->assertTrue($result['success']);

        // Verify approval status
        $approval = CompanyKycApproval::where('company_id', $this->company->id)
            ->where('section', 'business_info')
            ->first();

        $this->assertEquals('approved', $approval->status);
        $this->assertEquals($this->admin->id, $approval->reviewed_by);
        $this->assertNotNull($approval->reviewed_at);

        // Verify history
        $history = CompanyKycHistory::where('company_id', $this->company->id)
            ->where('section', 'business_info')
            ->where('action', 'approved')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals($this->admin->id, $history->admin_id);
    }

    /** @test */
    public function test_reject_section_updates_status_and_company()
    {
        // Submit section first
        $this->kycService->submitKycSection(
            $this->company->id,
            'business_info',
            ['company_name' => 'Test Company']
        );

        // Reject section
        $result = $this->kycService->rejectSection(
            $this->company->id,
            'business_info',
            $this->admin->id,
            'Missing documents'
        );

        $this->assertTrue($result['success']);

        // Verify approval status
        $approval = CompanyKycApproval::where('company_id', $this->company->id)
            ->where('section', 'business_info')
            ->first();

        $this->assertEquals('rejected', $approval->status);
        $this->assertEquals('Missing documents', $approval->rejection_reason);

        // Verify company status updated to rejected
        $this->assertEquals('rejected', $this->company->fresh()->kyc_status);
        $this->assertEquals('Missing documents', $this->company->fresh()->kyc_rejection_reason);
    }

    /** @test */
    public function test_all_sections_approved_activates_company()
    {
        $sections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];

        // Submit all sections
        foreach ($sections as $section) {
            $this->kycService->submitKycSection(
                $this->company->id,
                $section,
                ['data' => 'test']
            );
        }

        // Approve all sections except last one
        foreach (array_slice($sections, 0, 4) as $section) {
            $this->kycService->approveSection(
                $this->company->id,
                $section,
                $this->admin->id
            );
        }

        // Company should still be under_review
        $this->assertNotEquals('verified', $this->company->fresh()->kyc_status);

        // Approve last section
        $this->kycService->approveSection(
            $this->company->id,
            'board_members',
            $this->admin->id
        );

        // Company should now be approved
        $this->assertEquals('verified', $this->company->fresh()->kyc_status);
        $this->assertEquals($this->admin->id, $this->company->fresh()->kyc_reviewed_by);
        $this->assertNotNull($this->company->fresh()->kyc_reviewed_at);
    }

    /** @test */
    public function test_get_kyc_status_returns_complete_information()
    {
        // Submit a section
        $this->kycService->submitKycSection(
            $this->company->id,
            'business_info',
            ['company_name' => 'Test Company']
        );

        $status = $this->kycService->getKycStatus($this->company->id);

        $this->assertEquals('under_review', $status['overall_status']);
        $this->assertArrayHasKey('sections', $status);
        $this->assertArrayHasKey('history', $status);
    }

    /** @test */
    public function test_bvn_verification_with_mocked_easeid()
    {
        // Mock successful BVN verification
        $mockEaseIdClient = $this->createMock(EaseIdClient::class);
        $mockEaseIdClient->method('verifyBVN')
            ->willReturn([
                'success' => true,
                'message' => 'BVN verified',
                'data' => [
                    'bvn' => '12345678901',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'gender' => 'Male',
                    'birthday' => '1990-01-01',
                ],
            ]);

        $kycService = new KycService($mockEaseIdClient);
        $result = $kycService->verifyBVN('12345678901');

        $this->assertTrue($result['success']);
        $this->assertEquals('BVN verified successfully', $result['message']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('John', $result['data']['firstName']);
    }

    /** @test */
    public function test_bvn_verification_handles_failure()
    {
        // Mock failed BVN verification
        $mockEaseIdClient = $this->createMock(EaseIdClient::class);
        $mockEaseIdClient->method('verifyBVN')
            ->willReturn([
                'success' => false,
                'message' => 'Invalid BVN',
                'data' => null,
            ]);

        $kycService = new KycService($mockEaseIdClient);
        $result = $kycService->verifyBVN('00000000000');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid BVN', $result['message']);
        $this->assertNull($result['data']);
    }

    /** @test */
    public function test_nin_verification_with_mocked_easeid()
    {
        // Mock successful NIN verification
        $mockEaseIdClient = $this->createMock(EaseIdClient::class);
        $mockEaseIdClient->method('verifyNIN')
            ->willReturn([
                'success' => true,
                'message' => 'NIN verified',
                'data' => [
                    'nin' => '12345678901',
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'gender' => 'Female',
                    'birthday' => '1992-05-15',
                ],
            ]);

        $kycService = new KycService($mockEaseIdClient);
        $result = $kycService->verifyNIN('12345678901');

        $this->assertTrue($result['success']);
        $this->assertEquals('NIN verified successfully', $result['message']);
        $this->assertEquals('Jane', $result['data']['firstName']);
    }

    /** @test */
    public function test_invalid_section_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid KYC section');

        $this->kycService->submitKycSection(
            $this->company->id,
            'invalid_section',
            ['data' => 'test']
        );
    }

    /** @test */
    public function test_resubmit_section_updates_existing_approval()
    {
        // Submit section
        $this->kycService->submitKycSection(
            $this->company->id,
            'business_info',
            ['company_name' => 'Test Company']
        );

        // Reject it
        $this->kycService->rejectSection(
            $this->company->id,
            'business_info',
            $this->admin->id,
            'Incomplete'
        );

        $approval = CompanyKycApproval::where('company_id', $this->company->id)
            ->where('section', 'business_info')
            ->first();

        $this->assertEquals('rejected', $approval->status);

        // Resubmit
        $this->kycService->submitKycSection(
            $this->company->id,
            'business_info',
            ['company_name' => 'Updated Company']
        );

        $approval = $approval->fresh();
        $this->assertEquals('pending', $approval->status);
        $this->assertNull($approval->rejection_reason);
    }
}
