<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->string('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->date('date_of_birth')->nullable()->after('postal_code');
            $table->string('id_type')->nullable()->after('date_of_birth'); // bvn, nin, passport, etc.
            $table->string('id_number')->nullable()->after('id_type');
            $table->string('id_card_path')->nullable()->after('id_number');
            $table->string('utility_bill_path')->nullable()->after('id_card_path');
        });
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'city',
                'state',
                'postal_code',
                'date_of_birth',
                'id_type',
                'id_number',
                'id_card_path',
                'utility_bill_path'
            ]);
        });
    }
};
