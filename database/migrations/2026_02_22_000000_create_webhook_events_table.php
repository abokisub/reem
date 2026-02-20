<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->unsignedBigInteger('company_id');
            $table->string('provider_name', 100);
            $table->string('endpoint_url', 500)->nullable();
            $table->string('event_type', 100);
            $table->json('payload');
            $table->enum('status', ['pending', 'delivered', 'failed', 'duplicate'])->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('transaction_id');
            $table->index('company_id');
            $table->index('direction');
            $table->index('status');
            $table->index('next_retry_at');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('set null');
                  
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
