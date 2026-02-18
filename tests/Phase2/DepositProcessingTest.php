<?php

namespace Tests\Phase2;

use Tests\TestCase;
use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\VirtualAccount;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Phase 2: Deposit Processing & Webhook Confirmation
 * 
 * Tests:
 * - Webhook signature validation
 * - Deposit processing
 * - Wallet credit
 * - Ledger entry creation
 * - Outgoing webhook notification
 */
class DepositProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $virtualAccount;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'status' => 'active',
            'is_active' => true,
            'webhook_url' => 'https://merchant.example.com/webhook',
        ]);

        $this->wallet = CompanyWallet::factory()->create([
            'company_id' => $this->company->id,
            'balance' => 0,
        ]);

        $this->virtualAccount = VirtualAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_validates_webhook_signature()
    {
        $payload = [
            'event' => 'payment.success',
            'data' => [
                'account_number' => $this->virtualAccount->account_number,
                'amount' => 10000,
                'reference' => 'TEST_REF_001',
            ],
        ];

        $invalidSignature = 'invalid_signature';

        $response = $this->postJson('/api/webhooks/palmpay', $payload, [
            'X-PalmPay-Signature' => $invalidSignature,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_processes_deposit_and_credits_wallet()
    {
        $amount = 10000;

        // Simulate PalmPay webhook
        $payload = [
            'event' => 'payment.success',
            'data' => [
                'account_number' => $this->virtualAccount->account_number,
                'amount' => $amount * 100, // PalmPay sends in kobo
                'reference' => 'TEST_REF_001',
                'transaction_time' => now()->toIso8601String(),
            ],
        ];

        // Generate valid signature
        $signature = $this->generateWebhookSignature($payload);

        $response = $this->postJson('/api/webhooks/palmpay', $payload, [
            'X-PalmPay-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Verify wallet credited
        $this->wallet->refresh();
        $this->assertEquals($amount, $this->wallet->balance);

        // Verify transaction created
        $this->assertDatabaseHas('transactions', [
            'company_id' => $this->company->id,
            'type' => 'credit',
            'amount' => $amount,
            'status' => 'success',
        ]);
    }

    /** @test */
    public function it_creates_ledger_entries_for_deposit()
    {
        $amount = 10000;

        $payload = [
            'event' => 'payment.success',
            'data' => [
                'account_number' => $this->virtualAccount->account_number,
                'amount' => $amount * 100,
                'reference' => 'TEST_REF_002',
            ],
        ];

        $signature = $this->generateWebhookSignature($payload);

        $this->postJson('/api/webhooks/palmpay', $payload, [
            'X-PalmPay-Signature' => $signature,
        ]);

        // Verify ledger entries (double-entry)
        $this->assertDatabaseHas('ledger_entries', [
            'amount' => $amount,
        ]);

        // Should have both debit and credit entries
        $entries = \App\Models\LedgerEntry::where('amount', $amount)->get();
        $this->assertCount(1, $entries); // One entry with debit and credit accounts
    }

    /** @test */
    public function it_prevents_duplicate_deposit_processing()
    {
        $payload = [
            'event' => 'payment.success',
            'data' => [
                'account_number' => $this->virtualAccount->account_number,
                'amount' => 10000 * 100,
                'reference' => 'TEST_REF_003',
            ],
        ];

        $signature = $this->generateWebhookSignature($payload);

        // First request
        $this->postJson('/api/webhooks/palmpay', $payload, [
            'X-PalmPay-Signature' => $signature,
        ]);

        // Duplicate request
        $response = $this->postJson('/api/webhooks/palmpay', $payload, [
            'X-PalmPay-Signature' => $signature,
        ]);

        $response->assertStatus(200); // Should be idempotent

        // Verify only one transaction created
        $count = Transaction::where('external_reference', 'TEST_REF_003')->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Generate webhook signature for testing
     */
    private function generateWebhookSignature(array $payload): string
    {
        $secret = $this->company->webhook_secret;
        return 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
    }
}
