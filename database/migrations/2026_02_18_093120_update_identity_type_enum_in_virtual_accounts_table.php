<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL doesn't support ALTER ENUM directly, so we need to use raw SQL
        DB::statement("ALTER TABLE virtual_accounts MODIFY COLUMN identity_type ENUM('personal', 'company', 'personal_nin') NOT NULL DEFAULT 'personal'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE virtual_accounts MODIFY COLUMN identity_type ENUM('personal', 'company') NOT NULL DEFAULT 'personal'");
    }
};
