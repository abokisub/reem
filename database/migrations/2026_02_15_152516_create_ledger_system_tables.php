<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Ledger Accounts
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // PWV_ACC_xxx
            $table->string('name');
            $table->enum('account_type', ['company_wallet', 'revenue', 'settlement', 'clearing', 'reserve', 'bank_clearing']);
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->timestamps();

            $table->index(['company_id', 'account_type']);
        });

        // 2. Ledger Entries (Double-Entry Trail)
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id'); // Pointwave Transaction Reference
            $table->unsignedBigInteger('debit_account_id');
            $table->unsignedBigInteger('credit_account_id');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('debit_account_id')->references('id')->on('ledger_accounts');
            $table->foreign('credit_account_id')->references('id')->on('ledger_accounts');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};
