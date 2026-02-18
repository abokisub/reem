<?php

namespace Tests\Feature\Phase3;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\Transaction;
use App\Services\TransferService;
use App\Services\LedgerService;
use App\Services\FeeService;
use App\Services\Banking\BankingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;

class TransferTest extends TestCase
{
    use DatabaseTransactions;

    protected $transferService;
    protected $ledgerService;
    protected $feeService;
    protected $bankingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = new LedgerService(); // Real Ledger for DB/Logic test
        $this->feeService = new FeeService(); // Real Fee service

        // Mock BankingService for external calls
        $this->bankingService = Mockery::mock(BankingService::class);

        $this->transferService = new TransferService(
            $this->ledgerService,
            $this->feeService,
            $this->bankingService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_internal_transfer_success()
    {
        $timestamp = time() . rand(100, 999);

        // 1. Setup Sender
        $sender = User::forceCreate([
            'name' => 'Sender',
            'email' => "sender{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'sender_' . $timestamp . '_' . Str::random(4),
            'type' => 'user',
            'phone' => '080' . rand(10000000, 99999999),
        ]);
        $senderCompany = Company::create([
            'user_id' => $sender->id,
            'name' => 'Sender Co',
            'email' => "sender{$timestamp}@test.com"
        ]);
        $senderWallet = CompanyWallet::create(['company_id' => $senderCompany->id, 'currency' => 'NGN', 'balance' => 10000]);

        // 2. Setup Receiver
        $receiver = User::forceCreate([
            'name' => 'Receiver',
            'email' => "receiver{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'receiver_' . $timestamp . '_' . Str::random(4),
            'type' => 'user',
            'phone' => '080' . rand(10000000, 99999999),
        ]);
        $receiverCompany = Company::create([
            'user_id' => $receiver->id,
            'name' => 'Receiver Co',
            'email' => "receiver{$timestamp}@test.com"
        ]);
        $receiverWallet = CompanyWallet::create(['company_id' => $receiverCompany->id, 'currency' => 'NGN', 'balance' => 0]);

        // 3. Execute Transfer
        $result = $this->transferService->processInternalTransfer($senderWallet, $receiverWallet, 5000, "Test Transfer");

        // 4. Assertions
        $this->assertEquals('success', $result['status']);

        // Check Balances
        $senderWallet->refresh();
        $receiverWallet->refresh();
        $this->assertEquals(5000, $senderWallet->balance);
        $this->assertEquals(5000, $receiverWallet->balance);

        // Check Transaction Record
        $this->assertDatabaseHas('transactions', [
            'type' => 'transfer',
            'company_id' => $senderCompany->id,
            'amount' => 5000,
            'status' => 'success'
        ]);
    }

    public function test_internal_transfer_insufficient_funds()
    {
        $timestamp = time() . rand(100, 999);

        $sender = User::forceCreate([
            'name' => 'Poor Sender',
            'email' => "poor{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'poor_' . time(),
            'phone' => '080' . rand(10000000, 99999999),
        ]);
        $senderCompany = Company::create([
            'user_id' => $sender->id,
            'name' => 'Poor Co',
            'email' => "poor{$timestamp}@test.com"
        ]);
        $senderWallet = CompanyWallet::create(['company_id' => $senderCompany->id, 'currency' => 'NGN', 'balance' => 100]);

        $receiver = User::forceCreate([
            'name' => 'Rich Receiver',
            'email' => "rich{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'rich_' . time(),
            'phone' => '080' . rand(10000000, 99999999),
        ]);
        $receiverCompany = Company::create([
            'user_id' => $receiver->id,
            'name' => 'Rich Co',
            'email' => "rich{$timestamp}@test.com"
        ]);
        $receiverWallet = CompanyWallet::create(['company_id' => $receiverCompany->id, 'currency' => 'NGN', 'balance' => 0]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient specific funds");

        $this->transferService->processInternalTransfer($senderWallet, $receiverWallet, 5000);
    }

    public function test_external_transfer_fee_deduction()
    {
        $timestamp = time() . rand(100, 999);

        $sender = User::forceCreate([
            'name' => 'External Sender',
            'email' => "extsender{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'ext_' . time(),
            'phone' => '080' . rand(10000000, 99999999),
        ]);
        $senderCompany = Company::create([
            'user_id' => $sender->id,
            'name' => 'Ext Co',
            'email' => "extsender{$timestamp}@test.com"
        ]);
        $senderWallet = CompanyWallet::create(['company_id' => $senderCompany->id, 'currency' => 'NGN', 'balance' => 10000]);

        // Fees: Default 1.5% -> 5000 * 0.015 = 75. Net deduction = 5075.
        // But FeeService defaults to 1.5% if no setting. Let's assume default.

        // Mock Banking Service response
        $this->bankingService->shouldReceive('transfer')->andReturn(['status' => 'success']);

        $result = $this->transferService->processExternalTransfer($senderWallet, [
            'account_number' => '1234567890',
            'bank_code' => '044',
            'account_name' => 'Test Bank User'
        ], 5000);

        $this->assertEquals('success', $result['status']);

        $senderWallet->refresh();
        // 10000 - 5000 - 75 = 4925
        $this->assertEquals(4925, $senderWallet->balance);

        $this->assertDatabaseHas('transactions', [
            'type' => 'debit',
            'category' => 'transfer_out',
            'amount' => 5000,
            'fee' => 75,
            'total_amount' => 5075
        ]);
    }
}
