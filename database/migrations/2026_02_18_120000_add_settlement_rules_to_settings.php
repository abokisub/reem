<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Settlement configuration
            $table->boolean('auto_settlement_enabled')->default(true)->after('default_virtual_account');
            $table->integer('settlement_delay_hours')->default(24)->comment('Hours to delay settlement (1, 7, 24, etc.)');
            $table->boolean('settlement_skip_weekends')->default(true)->comment('Skip weekends for settlement (PalmPay T+1 rule)');
            $table->boolean('settlement_skip_holidays')->default(true)->comment('Skip holidays for settlement');
            $table->time('settlement_time')->default('02:00:00')->comment('Time of day to process settlements (e.g., 2am)');
            $table->decimal('settlement_minimum_amount', 15, 2)->default(100.00)->comment('Minimum amount to trigger settlement');
        });

        Schema::table('companies', function (Blueprint $table) {
            // Per-company settlement overrides
            $table->boolean('custom_settlement_enabled')->default(false)->after('palmpay_bank_code');
            $table->integer('custom_settlement_delay_hours')->nullable()->comment('Custom settlement delay for this company');
            $table->decimal('custom_settlement_minimum', 15, 2)->nullable()->comment('Custom minimum settlement amount');
        });

        // Create settlement queue table
        Schema::create('settlement_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('transaction_date');
            $table->timestamp('scheduled_settlement_date');
            $table->timestamp('actual_settlement_date')->nullable();
            $table->text('settlement_note')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('status');
            $table->index('scheduled_settlement_date');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_queue');
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'custom_settlement_enabled',
                'custom_settlement_delay_hours',
                'custom_settlement_minimum',
            ]);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'auto_settlement_enabled',
                'settlement_delay_hours',
                'settlement_skip_weekends',
                'settlement_skip_holidays',
                'settlement_time',
                'settlement_minimum_amount',
            ]);
        });
    }
};
