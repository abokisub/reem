<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // platform_revenue, bank_clearing, settlement_pool
            $table->string('slug')->unique();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('ledger_balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->timestamps();
        });

        // Initialize core system accounts
        DB::table('system_wallets')->insert([
            ['name' => 'Platform Revenue', 'slug' => 'platform_revenue', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PalmPay Clearing', 'slug' => 'bank_clearing', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_wallets');
    }
};
