<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettlementAndDirectorFieldsToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Settlement Bank Details
            $table->string('bank_name')->nullable()->after('nin');
            $table->string('account_number')->nullable()->after('bank_name');
            $table->string('account_name')->nullable()->after('account_number');
            $table->string('bank_code')->nullable()->after('account_name');

            // Director and Shareholder Information (JSON storage for full return)
            $table->json('directors')->nullable()->after('bank_code');
            $table->json('shareholders')->nullable()->after('directors');

            // Additional identity details (full returns storage)
            if (!Schema::hasColumn('companies', 'identity_details')) {
                $table->json('identity_details')->nullable()->after('verification_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'account_number',
                'account_name',
                'bank_code',
                'directors',
                'shareholders',
            ]);
            if (Schema::hasColumn('companies', 'identity_details')) {
                $table->dropColumn('identity_details');
            }
        });
    }
}
