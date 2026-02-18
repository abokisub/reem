<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Virtual Accounts - Company-level only (not end-users)
        if (!Schema::hasTable('virtual_accounts')) {
            Schema::create('virtual_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('account_number')->unique();
                $table->string('account_name');
                $table->string('bank_name');
                $table->string('bank_code');
                $table->string('provider')->default('palmpay'); // palmpay, monnify, etc.
                $table->string('provider_reference')->unique();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index(['company_id', 'is_active']);
                $table->index('provider_reference');
            });
        }

        // End User Transactions - Lightweight tracking
        if (!Schema::hasTable('end_user_transactions')) {
            Schema::create('end_user_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('virtual_account_id');
                $table->string('transaction_reference')->unique();
                $table->string('customer_reference'); // Company's customer ID
                $table->string('customer_name')->nullable();
                $table->string('customer_email')->nullable();
                $table->decimal('amount', 15, 2);
                $table->decimal('fee', 10, 2)->default(0);
                $table->decimal('net_amount', 15, 2);
                $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
                $table->string('payment_method')->nullable();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('virtual_account_id')->references('id')->on('virtual_accounts')->onDelete('cascade');

                // Indexes for performance
                $table->index(['company_id', 'status', 'created_at']);
                $table->index(['customer_reference', 'company_id']);
                $table->index('transaction_reference');
                $table->index('created_at'); // For partitioning
            });
        }

        // Webhook Logs - Track all incoming webhooks
        if (!Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('provider'); // palmpay, monnify, etc.
                $table->string('event_type');
                $table->json('payload');
                $table->string('signature')->nullable();
                $table->boolean('verified')->default(false);
                $table->boolean('processed')->default(false);
                $table->text('processing_error')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['provider', 'processed', 'created_at']);
                $table->index('event_type');
            });
        }

        // Settlement Records - Track payouts to companies
        if (!Schema::hasTable('settlements')) {
            Schema::create('settlements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('settlement_reference')->unique();
                $table->decimal('amount', 15, 2);
                $table->decimal('fee', 10, 2)->default(0);
                $table->decimal('net_amount', 15, 2);
                $table->integer('transaction_count');
                $table->date('settlement_date');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->string('bank_account_number')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->string('bank_code')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index(['company_id', 'status', 'settlement_date']);
                $table->index('settlement_reference');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('end_user_transactions');
        Schema::dropIfExists('virtual_accounts');
    }
};
