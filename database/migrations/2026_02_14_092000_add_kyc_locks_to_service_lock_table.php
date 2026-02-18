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
        Schema::table('service_lock', function (Blueprint $table) {
            $table->boolean('kyc_enhanced_bvn')->default(false)->after('virtual_accounts');
            $table->boolean('kyc_enhanced_nin')->default(false)->after('kyc_enhanced_bvn');
            $table->boolean('kyc_basic_bvn')->default(false)->after('kyc_enhanced_nin');
            $table->boolean('kyc_basic_nin')->default(false)->after('kyc_basic_bvn');
            $table->boolean('kyc_liveness')->default(false)->after('kyc_basic_nin');
            $table->boolean('kyc_face_compare')->default(false)->after('kyc_liveness');
            $table->boolean('kyc_bank_verify')->default(false)->after('kyc_face_compare');
            $table->boolean('kyc_credit_score')->default(false)->after('kyc_bank_verify');
            $table->boolean('kyc_loan')->default(false)->after('kyc_credit_score');
            $table->boolean('kyc_blacklist')->default(false)->after('kyc_loan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_lock', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_enhanced_bvn',
                'kyc_enhanced_nin',
                'kyc_basic_bvn',
                'kyc_basic_nin',
                'kyc_liveness',
                'kyc_face_compare',
                'kyc_bank_verify',
                'kyc_credit_score',
                'kyc_loan',
                'kyc_blacklist',
            ]);
        });
    }
};
