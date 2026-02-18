<?php

namespace Tests\Feature;

use App\Services\PalmPay\PalmPaySignature;
use App\Services\PalmPay\VirtualAccountService;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PalmPayAuditTest extends TestCase
{
    /**
     * Verify that the signature generation trims leading/trailing spaces.
     */
    public function test_signature_generation_trims_values()
    {
        // Mock config for fixed keys to ensure deterministic output if needed, 
        // but here we just want to verify the string construction logic.
        $signatureService = new PalmPaySignature();

        $dataWithSpaces = [
            'name' => '  John Doe  ',
            'amount' => ' 1000 ',
            'currency' => 'NGN'
        ];

        // We can't easily check the final signature without the actual private key,
        // but we can check if the internal buildSignatureString (which is private) works.
        // Instead, let's verify that two identical payloads with/without spaces 
        // produce the same signature.

        $dataClean = [
            'name' => 'John Doe',
            'amount' => '1000',
            'currency' => 'NGN'
        ];

        // Set dummy keys for the test
        Config::set('services.palmpay.private_key', "-----BEGIN RSA PRIVATE KEY-----\n...\n-----END RSA PRIVATE KEY-----");

        // Use reflection to test the private method if possible, or just observe behavior
        $method = new \ReflectionMethod(PalmPaySignature::class, 'buildSignatureString');
        $method->setAccessible(true);

        $stringWithSpaces = $method->invoke($signatureService, $dataWithSpaces);
        $stringClean = $method->invoke($signatureService, $dataClean);

        $this->assertEquals($stringClean, $stringWithSpaces, "The signature string should be identical after trimming.");
        $this->assertStringContainsString('amount=1000', $stringWithSpaces);
    }

    /**
     * Verify CAC Prefix enforcement for company identities.
     */
    public function test_cac_prefix_enforcement()
    {
        $vaService = new VirtualAccountService();

        // Mock Company model
        $mockCompany = \Mockery::mock('overload:App\Models\Company');
        $mockCompany->shouldReceive('find')->andReturn((object) ['name' => 'Test Company']);

        // Mock VirtualAccount model (to avoid DB insert)
        $mockVa = \Mockery::mock('overload:App\Models\VirtualAccount');
        $mockVa->shouldReceive('create')->andReturn(new \App\Models\VirtualAccount([
            'account_id' => 'va_123',
            'palmpay_account_number' => '123456',
            'palmpay_account_name' => 'Test',
            'palmpay_bank_name' => 'PalmPay',
            'status' => 'active'
        ]));

        // Mock PalmPayClient
        $mockClient = \Mockery::mock(\App\Services\PalmPay\PalmPayClient::class);
        $mockClient->shouldReceive('post')
            ->with('/api/v2/virtual/account/label/create', \Mockery::on(function ($data) {
                return str_starts_with($data['licenseNumber'], 'RC') && $data['identityType'] === 'company';
            }))
            ->andReturn(['code' => '00000', 'data' => ['virtualAccountNo' => '123456']]);

        // Inject the mock
        $property = new \ReflectionProperty(VirtualAccountService::class, 'client');
        $property->setAccessible(true);
        $property->setValue($vaService, $mockClient);

        // Run the service call
        $vaService->createVirtualAccount(1, 'user123', [
            'name' => 'Test Customer',
            'identity_type' => 'company',
            'license_number' => '12345678' // No RC prefix
        ]);

        $this->assertTrue(true, "Mock passed: licenseNumber was correctly prefixed.");
    }
}
