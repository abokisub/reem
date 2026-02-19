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
        Schema::table('palmpay_webhooks', function (Blueprint $table) {
            $table->integer('retry_count')->default(0)->after('processed');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count')->index();
            $table->string('status')->default('pending')->after('next_retry_at')->index(); // pending, processing, processed, failed, exhausted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('palmpay_webhooks', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'next_retry_at', 'status']);
        });
    }
};
