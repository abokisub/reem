<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('currency', 3)->default('NGN');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('ledger_balance', 15, 2)->default(0); // For reconciliation
            $table->decimal('pending_balance', 15, 2)->default(0); // Pending transactions
            $table->timestamps();

            // Unique constraint: one wallet per company per currency
            $table->unique(['company_id', 'currency']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_wallets');
    }
};