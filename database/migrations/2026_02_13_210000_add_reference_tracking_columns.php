<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add client_reference and api_reference columns to transaction tables

        // Data table
        if (Schema::hasTable('data') && !Schema::hasColumn('data', 'client_reference')) {
            Schema::table('data', function (Blueprint $table) {
                $table->string('client_reference', 100)->nullable()->after('transid');
                $table->string('api_reference', 100)->nullable()->after('client_reference');
            });
        }

        // Cable table
        if (Schema::hasTable('cable') && !Schema::hasColumn('cable', 'client_reference')) {
            Schema::table('cable', function (Blueprint $table) {
                $table->string('client_reference', 100)->nullable()->after('transid');
                $table->string('api_reference', 100)->nullable()->after('client_reference');
            });
        }

        // Bill table
        if (Schema::hasTable('bill') && !Schema::hasColumn('bill', 'client_reference')) {
            Schema::table('bill', function (Blueprint $table) {
                $table->string('client_reference', 100)->nullable()->after('transid');
                $table->string('api_reference', 100)->nullable()->after('client_reference');
            });
        }

        // Exam table
        if (Schema::hasTable('exam') && !Schema::hasColumn('exam', 'client_reference')) {
            Schema::table('exam', function (Blueprint $table) {
                $table->string('client_reference', 100)->nullable()->after('transid');
                $table->string('api_reference', 100)->nullable()->after('client_reference');
            });
        }

        // Airtime table
        if (Schema::hasTable('airtime') && !Schema::hasColumn('airtime', 'client_reference')) {
            Schema::table('airtime', function (Blueprint $table) {
                $table->string('client_reference', 100)->nullable()->after('transid');
                $table->string('api_reference', 100)->nullable()->after('client_reference');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove client_reference and api_reference columns

        if (Schema::hasColumn('data', 'client_reference')) {
            Schema::table('data', function (Blueprint $table) {
                $table->dropColumn(['client_reference', 'api_reference']);
            });
        }

        if (Schema::hasColumn('cable', 'client_reference')) {
            Schema::table('cable', function (Blueprint $table) {
                $table->dropColumn(['client_reference', 'api_reference']);
            });
        }

        if (Schema::hasColumn('bill', 'client_reference')) {
            Schema::table('bill', function (Blueprint $table) {
                $table->dropColumn(['client_reference', 'api_reference']);
            });
        }

        if (Schema::hasColumn('exam', 'client_reference')) {
            Schema::table('exam', function (Blueprint $table) {
                $table->dropColumn(['client_reference', 'api_reference']);
            });
        }

        if (Schema::hasColumn('airtime', 'client_reference')) {
            Schema::table('airtime', function (Blueprint $table) {
                $table->dropColumn(['client_reference', 'api_reference']);
            });
        }
    }
};
