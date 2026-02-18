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
        if (!Schema::hasTable('service_beneficiaries')) {
            Schema::create('service_beneficiaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('service_type'); // e.g., 'transfer_external', 'airtime', 'data', etc.
                $table->string('identifier'); // Account number, phone number, etc.
                $table->string('network_or_provider')->nullable(); // Bank name, network provider, etc.
                $table->string('name')->nullable(); // Beneficiary name
                $table->boolean('is_favorite')->default(false);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                // Indexes
                $table->index(['user_id', 'service_type']);
                $table->index('last_used_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_beneficiaries');
    }
};
