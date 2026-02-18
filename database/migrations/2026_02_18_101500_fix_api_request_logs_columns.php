<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop and recreate the table with correct columns
        Schema::dropIfExists('api_request_logs');
        
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->string('method');
            $table->string('path');
            $table->json('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->integer('status_code');
            $table->integer('latency_ms')->nullable();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('path');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
