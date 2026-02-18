<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // txn_xxx
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Transaction Type
            $table->enum('type', ['credit', 'debit', 'transfer', 'fee', 'refund', 'reversal']);
            $table->enum('category', ['virtual_account_credit', 'transfer_out', 'vtu_purchase', 'fee', 'refund', 'other']);

            // Amount
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // amount + fee
            $table->string('currency', 3)->default('NGN');

            // Status
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'reversed'])->default('pending');

            // References
            $table->string('reference')->unique(); // Our reference
            $table->string('palmpay_reference')->nullable(); // PalmPay's reference
            $table->string('external_reference')->nullable(); // Company's reference

            // Related Entities
            $table->foreignId('virtual_account_id')->nullable()->constrained()->onDelete('set null');
            $table->string('recipient_account_number')->nullable();
            $table->string('recipient_account_name')->nullable();
            $table->string('recipient_bank_code')->nullable();
            $table->string('recipient_bank_name')->nullable();

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();

            // Balances (for audit trail)
            $table->decimal('balance_before', 15, 2)->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();

            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('user_id');
            $table->index('reference');
            $table->index('palmpay_reference');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};