<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTestModeFields extends Migration
{
    public function up()
    {
        // Add test keys to companies
        Schema::table('companies', function (Blueprint $table) {
            $table->string('test_public_key')->nullable()->unique()->after('api_secret_key');
            $table->string('test_secret_key')->nullable()->unique()->after('test_public_key');
            $table->string('test_api_key')->nullable()->unique()->after('test_secret_key');
            $table->string('test_webhook_url')->nullable()->after('webhook_url');
            $table->string('test_webhook_secret')->nullable()->after('webhook_secret');
        });

        // Add is_test flag to transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('status');
            $table->index('is_test');
        });

        // Add is_test flag to virtual_accounts
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('status');
            $table->index('is_test');
        });

        // Add is_test flag to company_users
        Schema::table('company_users', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('status');
            $table->index('is_test');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['test_public_key', 'test_secret_key', 'test_api_key', 'test_webhook_url', 'test_webhook_secret']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('is_test');
        });

        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropColumn('is_test');
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn('is_test');
        });
    }
}
