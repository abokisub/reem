<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create transaction_status_logs table
     * 
     * This table maintains an audit trail of all transaction status changes,
     * recording the old status, new status, source of the change, and metadata.
     * 
     * This is critical for:
     * - Debugging status reconciliation issues
     * - Compliance and audit requirements
     * - Understanding transaction lifecycle
     * - Detecting status conflicts between system and providers
     */
    public function up(): void
    {
        Schema::create('transaction_status_logs', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to transactions table
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->onDelete('cascade');
            
            // Status change tracking
            $table->string('old_status', 50);
            $table->string('new_status', 50);
            
            // Source of the status change
            // Values: 'webhook', 'manual', 'scheduled_reconciliation', 'api', 'system'
            $table->string('source', 50);
            
            // Additional metadata about the change
            // Can include: webhook payload, admin user ID, reconciliation details, etc.
            $table->json('metadata')->nullable();
            
            // When the status change occurred
            $table->timestamp('changed_at');
            
            // Standard timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index('transaction_id');
            $table->index('changed_at');
            $table->index(['transaction_id', 'changed_at']);
            $table->index('source');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_status_logs');
    }
};
