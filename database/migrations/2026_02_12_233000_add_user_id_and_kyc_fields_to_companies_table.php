<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Association with User
            if (!Schema::hasColumn('companies', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            // Identification Fields
            if (!Schema::hasColumn('companies', 'bvn')) {
                $table->string('bvn', 11)->nullable()->after('business_registration_number');
            }
            if (!Schema::hasColumn('companies', 'nin')) {
                $table->string('nin', 11)->nullable()->after('bvn');
            }

            // Business Classification
            if (!Schema::hasColumn('companies', 'business_type')) {
                $table->string('business_type')->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'business_category')) {
                $table->string('business_category')->nullable()->after('business_type');
            }

            // Verification Data
            if (!Schema::hasColumn('companies', 'verification_data')) {
                $table->json('verification_data')->nullable()->after('kyc_documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'bvn', 'nin', 'business_type', 'business_category', 'verification_data']);
        });
    }
};
