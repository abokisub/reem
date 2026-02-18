<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('palmpay_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // e.g., 'payment.success', 'transfer.completed'
            $table->string('palmpay_reference')->nullable();
            $table->json('payload'); // Full webhook payload
            $table->string('signature')->nullable();
            $table->boolean('verified')->default(false);
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('event_type');
            $table->index('palmpay_reference');
            $table->index('processed');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('palmpay_webhooks');
    }
};