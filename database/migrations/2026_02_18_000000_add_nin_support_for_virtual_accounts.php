<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add NIN support to company_users table
        Schema::table('company_users', function (Blueprint $table) {
            $table->string('nin', 11)->nullable()->after('id_number')->comment('National Identification Number');
            $table->boolean('nin_verified')->default(false)->after('nin');
            $table->timestamp('nin_verified_at')->nullable()->after('nin_verified');
            
            // Add index for faster lookups
            $table->index('nin');
        });

        // Add NIN and identity_type to virtual_accounts table
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->string('nin', 11)->nullable()->after('bvn')->comment('National Identification Number');
            $table->enum('identity_type', ['personal', 'company'])->default('personal')->after('nin');
            
            // Add index for faster lookups
            $table->index('nin');
            $table->index('identity_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropIndex(['nin']);
            $table->dropColumn(['nin', 'nin_verified', 'nin_verified_at']);
        });

        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropIndex(['nin']);
            $table->dropIndex(['identity_type']);
            $table->dropColumn(['nin', 'identity_type']);
        });
    }
};
