<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Reconciliation Reports
        Schema::create('reconciliation_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('provider')->default('palmpay');
            $table->integer('total_provider_count');
            $table->decimal('total_provider_amount', 15, 2);
            $table->integer('matched_count');
            $table->integer('mismatched_count');
            $table->json('discrepancies')->nullable(); // Details of missing/mismatched txns
            $table->enum('status', ['balanced', 'unbalanced', 'pending'])->default('pending');
            $table->timestamps();

            $table->index(['report_date', 'status']);
        });

        // 2. Comprehensive Webhooks Logs (Master Audit Trail)
        // Redefining to be provider-agnostic and robust
        if (!Schema::hasTable('gateway_webhook_logs')) {
            Schema::create('gateway_webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('provider'); // palmpay, monnify
                $table->string('event_type');
                $table->string('provider_reference')->nullable();
                $table->json('payload');
                $table->string('signature')->nullable();
                $table->boolean('verified')->default(false);
                $table->enum('status', ['logged', 'processed', 'failed'])->default('logged');
                $table->text('error_message')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['provider', 'provider_reference']);
                $table->index('status');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_webhook_logs');
        Schema::dropIfExists('reconciliation_reports');
    }
};
