<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes settlement_delay_hours from integer to decimal to support fractional hours
     * (e.g., 0.0167 for 1 minute, 0.5 for 30 minutes, 1.5 for 90 minutes)
     */
    public function up(): void
    {
        // Use raw SQL to avoid Doctrine DBAL requirement
        DB::statement('ALTER TABLE settings MODIFY COLUMN settlement_delay_hours DECIMAL(8,4) DEFAULT 24 COMMENT "Hours to delay settlement (supports decimals: 0.0167=1min, 0.5=30min, 1=1h, 24=1day)"');
        
        DB::statement('ALTER TABLE companies MODIFY COLUMN custom_settlement_delay_hours DECIMAL(8,4) NULL COMMENT "Custom settlement delay for this company (supports decimals)"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to avoid Doctrine DBAL requirement
        DB::statement('ALTER TABLE settings MODIFY COLUMN settlement_delay_hours INT DEFAULT 24 COMMENT "Hours to delay settlement (1, 7, 24, etc.)"');
        
        DB::statement('ALTER TABLE companies MODIFY COLUMN custom_settlement_delay_hours INT NULL COMMENT "Custom settlement delay for this company"');
    }
};
