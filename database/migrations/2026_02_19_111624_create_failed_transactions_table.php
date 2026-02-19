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
        Schema::create('failed_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique()->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('type'); // deposit, withdrawal, etc.
            $table->decimal('amount', 20, 2);
            $table->json('payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('status')->default('pending')->index(); // pending, resolved, ignored
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_transactions');
    }
};
