<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMultiTenantToPlansAndNetworks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            'data_plan',
            'cable_plan',
            'bill_plan',
            'network'
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                });
            }

            // Initialize existing records with company_id = 1 (System Master)
            DB::table($tableName)->whereNull('company_id')->update(['company_id' => 1]);
        }

        // Special handling for data_plan unique constraint
        try {
            // Check if composite unique already exists
            $compositeExists = collect(DB::select("SHOW INDEX FROM data_plan WHERE Key_name = 'data_plan_company_id_plan_id_unique'"))->count() > 0;

            if (!$compositeExists) {
                Schema::table('data_plan', function (Blueprint $table) {
                    try {
                        $table->dropUnique('data_plan_plan_id_unique');
                    } catch (\Exception $e) {
                    }

                    $table->unique(['company_id', 'plan_id']);
                });
            }
        } catch (\Exception $e) {
            // Handle cases where Doctrine might be needed but failed
            DB::statement("ALTER TABLE data_plan DROP INDEX IF EXISTS data_plan_plan_id_unique");
            DB::statement("ALTER TABLE data_plan ADD UNIQUE INDEX IF NOT EXISTS data_plan_company_id_plan_id_unique (company_id, plan_id)");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No down migration for safety unless specifically needed
    }
}
