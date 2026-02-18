<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpandApiKeyColumnsSafe extends Migration
{
    /**
     * Run the migrations.
     * This migration safely expands API key columns without requiring doctrine/dbal
     */
    public function up()
    {
        // First, drop any unique indexes that might exist
        $indexes = DB::select("SHOW INDEX FROM companies WHERE Key_name LIKE '%api_key%'");
        
        foreach ($indexes as $index) {
            try {
                DB::statement("ALTER TABLE companies DROP INDEX {$index->Key_name}");
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        }

        // Now expand the columns one by one
        $columns = [
            'api_key',
            'api_secret_key',
            'secret_key',
            'test_api_key',
            'test_secret_key',
            'webhook_secret',
            'test_webhook_secret'
        ];

        foreach ($columns as $column) {
            try {
                // Check if column exists first
                $exists = DB::select("SHOW COLUMNS FROM companies LIKE '{$column}'");
                
                if (!empty($exists)) {
                    DB::statement("ALTER TABLE companies MODIFY COLUMN {$column} TEXT NULL");
                    echo "✓ Expanded column: {$column}\n";
                }
            } catch (\Exception $e) {
                echo "⚠ Warning for {$column}: " . $e->getMessage() . "\n";
                // Continue with other columns
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $columns = [
            'api_key',
            'api_secret_key',
            'secret_key',
            'test_api_key',
            'test_secret_key',
            'webhook_secret',
            'test_webhook_secret'
        ];

        foreach ($columns as $column) {
            try {
                $exists = DB::select("SHOW COLUMNS FROM companies LIKE '{$column}'");
                
                if (!empty($exists)) {
                    DB::statement("ALTER TABLE companies MODIFY COLUMN {$column} VARCHAR(255) NULL");
                }
            } catch (\Exception $e) {
                // Continue
            }
        }
    }
}
