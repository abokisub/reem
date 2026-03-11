<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add webhook backup and DNS resolution fields
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Webhook backup fields for DNS resolution issues
            $table->string('webhook_url_backup')->nullable()->after('webhook_url')
                ->comment('Backup webhook URL (original domain-based URL)');
            $table->string('test_webhook_url_backup')->nullable()->after('test_webhook_url')
                ->comment('Backup test webhook URL (original domain-based URL)');
            
            // DNS resolution tracking
            $table->json('dns_resolution_issues')->nullable()->after('webhook_url_backup')
                ->comment('Track DNS resolution issues and IP mappings');
            $table->timestamp('dns_last_checked_at')->nullable()->after('dns_resolution_issues')
                ->comment('When DNS resolution was last checked');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'webhook_url_backup',
                'test_webhook_url_backup', 
                'dns_resolution_issues',
                'dns_last_checked_at'
            ]);
        });
    }
};