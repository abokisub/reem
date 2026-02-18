<?php

namespace Tests\Feature\Phase1;

use Tests\TestCase;
use App\Models\User;
use App\Services\PhaseGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class PhaseGateTest extends TestCase
{
    // use RefreshDatabase; // Use with caution on existing DB

    public function test_customer_id_generation()
    {
        $user = new User();
        $user->name = 'Test User';
        $user->username = 'test_' . time();
        $user->phone = '080' . rand(10000000, 99999999);
        $user->email = 'test' . time() . '@example.com';
        $user->password = bcrypt('password');
        $user->save();

        $this->assertNotNull($user->customer_id);
        $this->assertStringStartsWith('CUST-', $user->customer_id);
    }

    public function test_phase_1_access()
    {
        // Should be active by default in dev or if config is set
        $this->assertTrue(PhaseGate::isPhaseActive(PhaseGate::PHASE_1_VIRTUAL_ACCOUNTS));
    }
}
