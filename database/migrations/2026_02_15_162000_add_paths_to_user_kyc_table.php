<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            if (!Schema::hasColumn('user_kyc', 'id_card_path')) {
                $table->string('id_card_path')->nullable()->after('id_number');
            }
            if (!Schema::hasColumn('user_kyc', 'utility_bill_path')) {
                $table->string('utility_bill_path')->nullable()->after('id_card_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropColumn(['id_card_path', 'utility_bill_path']);
        });
    }
};
