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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('refund_id', 100)->unique()->comment('Internal refund identifier');
            $table->string('transaction_id', 100)->nullable()->comment('Original transaction reference');
            $table->string('palmpay_refund_no', 100)->nullable()->comment('PalmPay refund number');
            $table->string('palmpay_order_no', 100)->nullable()->comment('Original PalmPay order number');
            $table->decimal('amount', 15, 2)->comment('Refund amount');
            $table->string('currency', 3)->default('NGN');
            $table->text('reason')->nullable()->comment('Refund reason');
            $table->enum('refund_type', ['auto', 'manual'])->default('manual')->comment('Refund trigger type');
            $table->unsignedBigInteger('initiated_by')->nullable()->comment('User ID who initiated manual refund');
            $table->text('admin_notes')->nullable()->comment('Admin notes for manual refunds');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('error_message')->nullable()->comment('Error details if failed');
            $table->json('metadata')->nullable()->comment('Additional refund metadata');
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('transaction_id');
            $table->index('palmpay_refund_no');
            $table->index('status');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
