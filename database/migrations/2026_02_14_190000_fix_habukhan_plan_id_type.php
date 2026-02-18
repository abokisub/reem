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
        Schema::table('data_plan', function (Blueprint $table) {
            // Modify columns to be string (varchar) to allow "1" instead of "1.00"
            // We use change() method which requires doctrine/dbal, but since we might not have it,
            // we can try raw SQL or standard Schema methods if supported.
            // Laravel Schema change() requires doctrine/dbal.
            // If doctrine/dbal is missing, we might need raw SQL. 
            // Given previous migrations used Schema::table, let's try standard approach first, 
            // but for altering types, raw SQL is often safer without dbal.

            $columns = ['habukhan1', 'habukhan2', 'habukhan3', 'habukhan4', 'habukhan5'];
            foreach ($columns as $column) {
                // Using raw SQL for MySQL/MariaDB to avoid dependency issues
                DB::statement("ALTER TABLE data_plan MODIFY {$column} VARCHAR(255) NULL");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_plan', function (Blueprint $table) {
            $columns = ['habukhan1', 'habukhan2', 'habukhan3', 'habukhan4', 'habukhan5'];
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE data_plan MODIFY {$column} DECIMAL(10,2) NULL");
            }
        });
    }
};
