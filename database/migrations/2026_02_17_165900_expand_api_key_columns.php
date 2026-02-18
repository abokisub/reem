<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ExpandApiKeyColumns extends Migration
{
    /**
     * Run the migrations.
     * Expand API key columns to accommodate encrypted values
     */
    public function up()
    {
        // Use raw SQL to modify columns without doctrine/dbal
        DB::statement('ALTER TABLE companies MODIFY COLUMN api_key TEXT NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN api_secret_key TEXT NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN secret_key TEXT NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN test_api_key TEXT NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN test_secret_key TEXT NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN webhook_secret TEXT NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN test_webhook_secret TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('ALTER TABLE companies MODIFY COLUMN api_key VARCHAR(255) NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN api_secret_key VARCHAR(255) NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN secret_key VARCHAR(255) NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN test_api_key VARCHAR(255) NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN test_secret_key VARCHAR(255) NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN webhook_secret VARCHAR(255) NULL');
        DB::statement('ALTER TABLE companies MODIFY COLUMN test_webhook_secret VARCHAR(255) NULL');
    }
}
