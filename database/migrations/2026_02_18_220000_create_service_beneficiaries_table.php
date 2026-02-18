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
                $table->string('service_type', 50); // Reduced length for index
                $table->string('identifier', 100); // Reduced length for index
                $table->string('network_or_provider', 100)->nullable();
                $table->string('name')->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                // Indexes with reduced key length
                $table->index(['user_id', 'service_type'], 'idx_user_service');
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
