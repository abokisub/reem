<?php

namespace Tests\Feature\Phase4;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\Transaction;
use App\Models\Refund;
use App\Services\RefundService;
use App\Services\LedgerService;
use App\Services\PalmPay\RefundService as PalmPayRefundService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;

class RefundTest extends TestCase
{
    use DatabaseTransactions;

    protected $refundService;
    protected $ledgerService;
    protected $palmPayRefundService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = new LedgerService();

        // Mock PalmPay RefundService
        $this->palmPayRefundService = Mockery::mock(PalmPayRefundService::class);

        $this->refundService = new RefundService(
            $this->ledgerService,
            $this->palmPayRefundService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_auto_refund_failed_transaction()
    {
        $timestamp = time() . rand(100, 999);

        // 1. Setup Company and Wallet
        $user = User::forceCreate([
            'name' => 'Test User',
            'email' => "autorefund{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'autorefund_' . $timestamp . '_' . rand(100, 999),
            'type' => 'user',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'Test Company',
            'email' => "autorefund{$timestamp}@test.com",
        ]);

        $wallet = CompanyWallet::create([
            'company_id' => $company->id,
            'currency' => 'NGN',
            'balance' => 5000, // Already deducted
        ]);

        // 2. Create a failed transaction
        $transaction = Transaction::create([
            'transaction_id' => 'TXN-' . uniqid(),
            'reference' => 'REF-' . uniqid(),
            'company_id' => $company->id,
            'type' => 'debit',
            'category' => 'transfer_out',
            'amount' => 5000,
            'fee' => 75,
            'total_amount' => 5075,
            'currency' => 'NGN',
            'status' => 'failed',
            'error_message' => 'Provider error',
        ]);

        // 3. Process auto-refund
        $result = $this->refundService->processAutoRefund($transaction, 'Auto-refund: Transaction failed');

        // 4. Assertions
        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Refund::class, $result['refund']);
        $this->assertEquals('auto', $result['refund']->refund_type);
        $this->assertEquals('completed', $result['refund']->status);

        // Check wallet was credited back
        $wallet->refresh();
        $this->assertEquals(10075, $wallet->balance); // 5000 + 5075 refund

        // Check transaction status updated
        $transaction->refresh();
        $this->assertEquals('reversed', $transaction->status);

        // Check refund record
        $this->assertDatabaseHas('refunds', [
            'company_id' => $company->id,
            'transaction_id' => $transaction->reference,
            'amount' => 5075,
            'refund_type' => 'auto',
            'status' => 'completed',
        ]);
    }

    public function test_auto_refund_prevents_duplicate()
    {
        $timestamp = time() . rand(100, 999);

        $user = User::forceCreate([
            'name' => 'Dup User',
            'email' => "dup{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'dup_' . $timestamp . '_' . rand(100, 999),
            'type' => 'user',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'Dup Company',
            'email' => "dup{$timestamp}@test.com",
        ]);

        $wallet = CompanyWallet::create([
            'company_id' => $company->id,
            'currency' => 'NGN',
            'balance' => 5000,
        ]);

        $transaction = Transaction::create([
            'transaction_id' => 'TXN-' . uniqid(),
            'reference' => 'REF-' . uniqid(),
            'company_id' => $company->id,
            'type' => 'debit',
            'category' => 'transfer_out',
            'amount' => 5000,
            'fee' => 75,
            'total_amount' => 5075,
            'currency' => 'NGN',
            'status' => 'failed',
        ]);

        // First refund
        $this->refundService->processAutoRefund($transaction);

        // Transaction is now 'reversed', trying to refund again should fail
        $transaction->refresh();
        $this->assertEquals('reversed', $transaction->status);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot refund transaction with status: reversed");

        $this->refundService->processAutoRefund($transaction);
    }

    public function test_manual_refund_success()
    {
        $timestamp = time() . rand(100, 999);

        // 1. Setup
        $admin = User::forceCreate([
            'name' => 'Admin User',
            'email' => "admin{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'admin_' . $timestamp . '_' . rand(100, 999),
            'type' => 'admin',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $user = User::forceCreate([
            'name' => 'Customer',
            'email' => "customer{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'customer_' . $timestamp . '_' . rand(100, 999),
            'type' => 'user',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'Customer Co',
            'email' => "customer{$timestamp}@test.com",
        ]);

        $wallet = CompanyWallet::create([
            'company_id' => $company->id,
            'currency' => 'NGN',
            'balance' => 10000,
        ]);

        // 2. Create a successful deposit transaction
        $transaction = Transaction::create([
            'transaction_id' => 'TXN-' . uniqid(),
            'reference' => 'REF-' . uniqid(),
            'company_id' => $company->id,
            'type' => 'credit',
            'category' => 'virtual_account_credit',
            'amount' => 5000,
            'fee' => 0,
            'total_amount' => 5000,
            'currency' => 'NGN',
            'status' => 'success',
            'palmpay_reference' => 'PP-' . uniqid(),
        ]);

        // Mock PalmPay refund initiation
        $this->palmPayRefundService->shouldReceive('initiateRefund')
            ->once()
            ->andReturn(new Refund([
                'refund_id' => 'REF-' . uniqid(),
                'status' => 'completed'
            ]));

        // 3. Process manual refund
        $result = $this->refundService->processManualRefund(
            $transaction,
            'Customer request',
            $admin->id,
            'Approved by admin after review'
        );

        // 4. Assertions
        $this->assertTrue($result['success']);
        $this->assertEquals('manual', $result['refund']->refund_type);
        $this->assertEquals($admin->id, $result['refund']->initiated_by);
        $this->assertEquals('Approved by admin after review', $result['refund']->admin_notes);

        // Check wallet was debited
        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance); // 10000 - 5000 refund

        // Check refund record
        $this->assertDatabaseHas('refunds', [
            'company_id' => $company->id,
            'transaction_id' => $transaction->reference,
            'amount' => 5000,
            'refund_type' => 'manual',
            'initiated_by' => $admin->id,
            'status' => 'completed',
        ]);
    }

    public function test_manual_refund_insufficient_balance()
    {
        $timestamp = time() . rand(100, 999);

        $admin = User::forceCreate([
            'name' => 'Admin',
            'email' => "admin2{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'admin2_' . $timestamp . '_' . rand(100, 999),
            'type' => 'admin',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $user = User::forceCreate([
            'name' => 'Poor Customer',
            'email' => "poor{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'poor_' . $timestamp . '_' . rand(100, 999),
            'type' => 'user',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'Poor Co',
            'email' => "poor{$timestamp}@test.com",
        ]);

        $wallet = CompanyWallet::create([
            'company_id' => $company->id,
            'currency' => 'NGN',
            'balance' => 100, // Not enough for refund
        ]);

        $transaction = Transaction::create([
            'transaction_id' => 'TXN-' . uniqid(),
            'reference' => 'REF-' . uniqid(),
            'company_id' => $company->id,
            'type' => 'credit',
            'category' => 'virtual_account_credit',
            'amount' => 5000,
            'fee' => 0,
            'total_amount' => 5000,
            'currency' => 'NGN',
            'status' => 'success',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient balance for refund");

        $this->refundService->processManualRefund($transaction, 'Test', $admin->id);
    }

    public function test_manual_refund_prevents_duplicate()
    {
        $timestamp = time() . rand(100, 999);

        $admin = User::forceCreate([
            'name' => 'Admin',
            'email' => "admin3{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'admin3_' . $timestamp . '_' . rand(100, 999),
            'type' => 'admin',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $user = User::forceCreate([
            'name' => 'Customer',
            'email' => "cust{$timestamp}@test.com",
            'password' => bcrypt('password'),
            'username' => 'cust_' . $timestamp . '_' . rand(100, 999),
            'type' => 'user',
            'phone' => '080' . rand(10000000, 99999999),
        ]);

        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'Cust Co',
            'email' => "cust{$timestamp}@test.com",
        ]);

        $wallet = CompanyWallet::create([
            'company_id' => $company->id,
            'currency' => 'NGN',
            'balance' => 20000,
        ]);

        $transaction = Transaction::create([
            'transaction_id' => 'TXN-' . uniqid(),
            'reference' => 'REF-' . uniqid(),
            'company_id' => $company->id,
            'type' => 'credit',
            'category' => 'virtual_account_credit',
            'amount' => 5000,
            'fee' => 0,
            'total_amount' => 5000,
            'currency' => 'NGN',
            'status' => 'success',
            'palmpay_reference' => 'PP-' . uniqid(),
        ]);

        // Mock PalmPay
        $this->palmPayRefundService->shouldReceive('initiateRefund')->andReturn(new Refund([
            'refund_id' => 'REF-' . uniqid(),
            'status' => 'completed'
        ]));

        // First refund
        $this->refundService->processManualRefund($transaction, 'First', $admin->id);

        // Second refund should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Transaction already refunded");

        $this->refundService->processManualRefund($transaction, 'Second', $admin->id);
    }
}
