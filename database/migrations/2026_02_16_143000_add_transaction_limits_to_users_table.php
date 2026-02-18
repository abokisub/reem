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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'daily_used_date')) {
                $table->date('daily_used_date')->nullable()->default(now());
            }
            if (!Schema::hasColumn('users', 'daily_used')) {
                $table->decimal('daily_used', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('users', 'single_limit')) {
                $table->decimal('single_limit', 15, 2)->default(500000); // Default single limit 500k
            }
            if (!Schema::hasColumn('users', 'daily_limit')) {
                $table->decimal('daily_limit', 15, 2)->default(5000000); // Default daily limit 5M
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_used_date', 'daily_used', 'single_limit', 'daily_limit']);
        });
    }
};
