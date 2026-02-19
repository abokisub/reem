<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('provider_reference')->nullable()->after('palmpay_reference');
            $table->string('provider')->nullable()->after('provider_reference');
            $table->enum('reconciliation_status', ['pending', 'reconciled', 'mismatched'])->default('pending')->after('status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_status');
            
            // Add index for provider_reference
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['provider_reference']);
            $table->dropColumn(['provider_reference', 'provider', 'reconciliation_status', 'reconciled_at']);
        });
    }
};
