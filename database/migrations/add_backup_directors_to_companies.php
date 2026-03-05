<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add backup director support to companies table
     * SAFE: Only adds new columns, doesn't modify existing data
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Backup Director 2
            $table->string('backup_director_2_bvn')->nullable()->after('director_nin');
            $table->string('backup_director_2_nin')->nullable()->after('backup_director_2_bvn');
            
            // Backup Director 3
            $table->string('backup_director_3_bvn')->nullable()->after('backup_director_2_nin');
            $table->string('backup_director_3_nin')->nullable()->after('backup_director_3_bvn');
            
            // Backup Director 4
            $table->string('backup_director_4_bvn')->nullable()->after('backup_director_3_nin');
            $table->string('backup_director_4_nin')->nullable()->after('backup_director_4_bvn');
            
            // Backup Director 5
            $table->string('backup_director_5_bvn')->nullable()->after('backup_director_4_nin');
            $table->string('backup_director_5_nin')->nullable()->after('backup_director_5_bvn');
            
            // Backup Director 6
            $table->string('backup_director_6_bvn')->nullable()->after('backup_director_5_nin');
            $table->string('backup_director_6_nin')->nullable()->after('backup_director_6_bvn');
            
            // Backup Director 7
            $table->string('backup_director_7_bvn')->nullable()->after('backup_director_6_nin');
            $table->string('backup_director_7_nin')->nullable()->after('backup_director_7_bvn');
            
            // Backup Director 8
            $table->string('backup_director_8_bvn')->nullable()->after('backup_director_7_nin');
            $table->string('backup_director_8_nin')->nullable()->after('backup_director_8_bvn');
            
            // Backup Director 9
            $table->string('backup_director_9_bvn')->nullable()->after('backup_director_8_nin');
            $table->string('backup_director_9_nin')->nullable()->after('backup_director_9_bvn');
            
            // Backup Director 10
            $table->string('backup_director_10_bvn')->nullable()->after('backup_director_9_nin');
            $table->string('backup_director_10_nin')->nullable()->after('backup_director_10_bvn');
            
            // KYC Management
            $table->string('preferred_kyc_method')->nullable()->after('backup_director_10_nin')->comment('Preferred KYC method: director_nin, director_bvn, etc.');
            $table->json('kyc_method_blacklist')->nullable()->after('preferred_kyc_method')->comment('Blacklisted KYC methods that failed');
            $table->timestamp('kyc_last_updated')->nullable()->after('kyc_method_blacklist');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'backup_director_2_bvn', 'backup_director_2_nin',
                'backup_director_3_bvn', 'backup_director_3_nin',
                'backup_director_4_bvn', 'backup_director_4_nin',
                'backup_director_5_bvn', 'backup_director_5_nin',
                'backup_director_6_bvn', 'backup_director_6_nin',
                'backup_director_7_bvn', 'backup_director_7_nin',
                'backup_director_8_bvn', 'backup_director_8_nin',
                'backup_director_9_bvn', 'backup_director_9_nin',
                'backup_director_10_bvn', 'backup_director_10_nin',
                'preferred_kyc_method', 'kyc_method_blacklist', 'kyc_last_updated'
            ]);
        });
    }
};