<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_kyc_pool', function (Blueprint $table) {
            $table->unsignedInteger('max_usage')->nullable()->after('failure_count');
        });
    }

    public function down(): void
    {
        Schema::table('global_kyc_pool', function (Blueprint $table) {
            $table->dropColumn('max_usage');
        });
    }
};
