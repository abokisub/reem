<?php

namespace Tests\Feature\Phase2;

use Tests\TestCase;
use App\Services\PalmPay\WebhookHandler;
use App\Services\PalmPay\PalmPaySignature;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\VirtualAccount;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Mockery;

class WebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_webhook_credit_process()
    {
        // 1. Setup Data
        $userId = DB::table('users')->insertGetId([
            'name' => 'Webhook Test User',
            'username' => 'webhook_' . time() . '_' . Str::random(4),
            'email' => 'webhook_' . time() . '_' . Str::random(4) . '@test.com',
            'phone' => '080' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'type' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
            'customer_id' => 'CUST-' . time(),
        ]);

        $user = User::find($userId);

        $company = new Company();
        $company->user_id = $user->id;
        $company->name = 'Webhook Test Company';
        $company->email = 'webhook_co_' . time() . '@test.com';
        $company->phone = $user->phone;
        $company->save();

        $wallet = new CompanyWallet();
        $wallet->company_id = $company->id;
        $wallet->currency = 'NGN';
        $wallet->setAttribute('balance', '0.00');
        $wallet->save();

        $accountNumber = '99' . rand(10000000, 99999999);
        $va = new VirtualAccount();
        $va->company_id = $company->id;
        $va->user_id = $user->id;
        $va->palmpay_account_number = $accountNumber;
        $va->status = 'active';
        $va->save();

        // 2. Mock Services
        // We Mock Signature to avoid needing real keys
        $mockSignature = Mockery::mock(PalmPaySignature::class);
        $mockSignature->shouldReceive('verifyWebhookSignature')->andReturn(true);

        // Mock LedgerService to avoid DB locking issues in test environment
        $ledgerService = Mockery::mock(LedgerService::class);
        $ledgerService->shouldReceive('getOrCreateAccount')->andReturn((object) ['id' => 1]);
        $ledgerService->shouldReceive('recordEntry')->andReturn(true);

        // 3. Inject into Handler
        // Note: We need to modify WebhookHandler to accept these in constructor or setters 
        // to make this testable without a container overload.
        // For now, we assume we will refactor WebhookHandler to allow injection.
        $handler = new WebhookHandler($mockSignature, $ledgerService);

        // 4. Payload
        $ref = 'REF-' . Str::random(10);
        $payload = [
            'virtualAccountNo' => $accountNumber,
            'orderAmount' => 500000, // 5000.00 NGN
            'orderNo' => $ref,
            'accountReference' => 'VA-REF',
            'senderName' => 'John Doe',
            'narration' => 'Test Deposit'
        ];

        // 5. Execute
        $result = $handler->handle($payload, 'dummy-signature');

        // 6. Assertions
        $this->assertTrue($result['success']);

        // Check Transaction
        $this->assertDatabaseHas('transactions', [
            'palmpay_reference' => $ref,
            'amount' => 5000.00,
            'status' => 'success',
            'type' => 'credit'
        ]);

        // Check Wallet Update
        $wallet->refresh();
        $this->assertEquals(5000.00, $wallet->balance);

        // 7. Check Deduplication
        $resultDuplicate = $handler->handle($payload, 'dummy-signature');
        $this->assertTrue($resultDuplicate['success']);
        $this->assertEquals('Duplicate transaction', $resultDuplicate['message']);

        // Cleanup
        $va->delete();
        $wallet->delete();
        $company->delete();
        $user->delete();
        Transaction::where('palmpay_reference', $ref)->delete();
    }
}
