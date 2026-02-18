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
        // Only create if doesn't exist
        if (!Schema::hasTable('settlement_queue')) {
            Schema::create('settlement_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->timestamp('transaction_date')->nullable();
                $table->timestamp('scheduled_settlement_date')->nullable();
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_queue');
    }
};
