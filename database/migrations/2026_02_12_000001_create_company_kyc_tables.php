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
        // Add KYC columns to companies table
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (!Schema::hasColumn('companies', 'kyc_status')) {
                    $table->enum('kyc_status', ['pending', 'under_review', 'approved', 'rejected', 'suspended'])
                        ->default('pending')
                        ->after('is_active');
                }
                if (!Schema::hasColumn('companies', 'kyc_reviewed_at')) {
                    $table->timestamp('kyc_reviewed_at')->nullable()->after('kyc_status');
                }
                if (!Schema::hasColumn('companies', 'kyc_reviewed_by')) {
                    $table->unsignedBigInteger('kyc_reviewed_by')->nullable()->after('kyc_reviewed_at');
                    $table->foreign('kyc_reviewed_by')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('companies', 'api_credentials_generated')) {
                    $table->boolean('api_credentials_generated')->default(false)->after('secret_key');
                }
            });
        }

        // Company KYC Approvals - Granular section-by-section approval
        Schema::create('company_kyc_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('section', ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['company_id', 'section']);
            $table->index(['company_id', 'status']);
        });

        // Company KYC History - Audit trail
        Schema::create('company_kyc_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('section', ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members']);
            $table->enum('action', ['submitted', 'approved', 'rejected', 'resubmitted']);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'created_at']);
        });

        // Admin Notifications
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['new_kyc_submission', 'kyc_resubmission', 'high_value_transaction', 'fraud_alert', 'system_alert']);
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('read_by')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('read_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['is_read', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index('type');
        });

        // Fraud Detection Rules
        Schema::create('fraud_detection_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('rule_type', ['velocity', 'amount_threshold', 'duplicate', 'blacklist', 'pattern']);
            $table->json('conditions');
            $table->enum('action', ['flag', 'block', 'auto_refund']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'rule_type']);
        });

        // Fraud Alerts
        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('rule_id');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('description');
            $table->enum('action_taken', ['flagged', 'blocked', 'refunded']);
            $table->boolean('resolved')->default(false);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('rule_id')->references('id')->on('fraud_detection_rules')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['resolved', 'created_at']);
            $table->index(['company_id', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_alerts');
        Schema::dropIfExists('fraud_detection_rules');
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('company_kyc_history');
        Schema::dropIfExists('company_kyc_approvals');

        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (Schema::hasColumn('companies', 'kyc_reviewed_by')) {
                    $table->dropForeign(['kyc_reviewed_by']);
                    $table->dropColumn('kyc_reviewed_by');
                }
                if (Schema::hasColumn('companies', 'kyc_reviewed_at')) {
                    $table->dropColumn('kyc_reviewed_at');
                }
                if (Schema::hasColumn('companies', 'kyc_status')) {
                    $table->dropColumn('kyc_status');
                }
                if (Schema::hasColumn('companies', 'api_credentials_generated')) {
                    $table->dropColumn('api_credentials_generated');
                }
            });
        }
    }
};
