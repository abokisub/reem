<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Expand the status enum to include all possible values
        DB::statement("ALTER TABLE `transactions` MODIFY COLUMN `status` ENUM(
            'pending',
            'initiated',
            'debited',
            'processing',
            'success',
            'successful',
            'failed',
            'reversed',
            'settled'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE `transactions` MODIFY COLUMN `status` ENUM(
            'pending',
            'processing',
            'success',
            'failed',
            'reversed'
        ) NOT NULL DEFAULT 'pending'");
    }
};
