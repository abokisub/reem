<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ExpandApiKeyColumnsRaw extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Drop unique indexes first
        try {
            DB::statement('ALTER TABLE companies DROP INDEX companies_api_key_unique');
        } catch (\Exception $e) {
            // Index might not exist
        }

        // Expand columns using raw SQL
        DB::statement('ALTER TABLE companies 
            MODIFY COLUMN api_key TEXT NULL,
            MODIFY COLUMN api_secret_key TEXT NULL,
            MODIFY COLUMN secret_key TEXT NULL,
            MODIFY COLUMN test_api_key TEXT NULL,
            MODIFY COLUMN test_secret_key TEXT NULL,
            MODIFY COLUMN webhook_secret TEXT NULL,
            MODIFY COLUMN test_webhook_secret TEXT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('ALTER TABLE companies 
            MODIFY COLUMN api_key VARCHAR(255) NULL,
            MODIFY COLUMN api_secret_key VARCHAR(255) NULL,
            MODIFY COLUMN secret_key VARCHAR(255) NULL,
            MODIFY COLUMN test_api_key VARCHAR(255) NULL,
            MODIFY COLUMN test_secret_key VARCHAR(255) NULL,
            MODIFY COLUMN webhook_secret VARCHAR(255) NULL,
            MODIFY COLUMN test_webhook_secret VARCHAR(255) NULL
        ');
    }
}
