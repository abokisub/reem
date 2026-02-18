<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_accounts', 'uuid')) {
                $table->string('uuid')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('virtual_accounts', 'company_user_id')) {
                $table->foreignId('company_user_id')->nullable()->after('company_id')->constrained()->onDelete('cascade');
            }
            $table->string('bank_code')->nullable()->after('account_id');
            $table->enum('account_type', ['static', 'dynamic'])->default('static')->after('bank_code');
            $table->decimal('amount', 15, 2)->nullable()->after('account_type');
            $table->string('provider')->default('palmpay')->after('amount');
            $table->string('provider_reference')->nullable()->after('provider');
            $table->string('account_number')->nullable()->after('provider_reference');
            $table->string('bank_name')->nullable()->after('account_number');
            $table->string('account_name')->nullable()->after('bank_name');
            $table->timestamp('expires_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'company_user_id',
                'bank_code',
                'account_type',
                'amount',
                'provider',
                'provider_reference',
                'account_number',
                'bank_name',
                'account_name',
                'expires_at'
            ]);
        });
    }
};
