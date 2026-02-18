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
        // 1. data_plan Table
        Schema::table('data_plan', function (Blueprint $table) {
            $columns = [
                'msorg1',
                'msorg2',
                'msorg3',
                'msorg4',
                'msorg5',
                'virus1',
                'virus2',
                'virus3',
                'virus4',
                'virus5',
                'free1',
                'free2',
                'free3',
                'free4',
                'free5',
                'simhosting',
                'ogdamns',
                'megasub',
                'megasubcloud',
                'adex1',
                'adex2',
                'adex3',
                'adex4',
                'adex5',
                'zimrax',
                'hamdala',
                'added_by'
            ];
            foreach ($columns as $column) {
                if (!Schema::hasColumn('data_plan', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });

        // 2. cable_plan Table
        Schema::table('cable_plan', function (Blueprint $table) {
            $columns = [
                'habukhan1',
                'habukhan2',
                'habukhan3',
                'habukhan4',
                'habukhan5'
            ];
            foreach ($columns as $column) {
                if (!Schema::hasColumn('cable_plan', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });

        // 3. bill_plan Table
        Schema::table('bill_plan', function (Blueprint $table) {
            $columns = [
                'habukhan1',
                'habukhan2',
                'habukhan3',
                'habukhan4',
                'habukhan5',
                'added_by'
            ];
            foreach ($columns as $column) {
                if (!Schema::hasColumn('bill_plan', $column)) {
                    $table->string($column)->nullable();
                }
            }
            if (!Schema::hasColumn('bill_plan', 'plan_id')) {
                $table->string('plan_id')->nullable();
            }
            if (!Schema::hasColumn('bill_plan', 'plan_status')) {
                $table->boolean('plan_status')->default(1);
            }
        });

        // 4. data_card_plan Table
        Schema::table('data_card_plan', function (Blueprint $table) {
            $decimalColumns = ['smart', 'agent', 'awuf', 'special', 'api'];
            foreach ($decimalColumns as $column) {
                if (!Schema::hasColumn('data_card_plan', $column)) {
                    $table->decimal($column, 10, 2)->default(0)->nullable();
                }
            }

            $stringColumns = [
                'load_pin',
                'habukhan1',
                'habukhan2',
                'habukhan3',
                'habukhan4',
                'habukhan5',
                'free1',
                'free2',
                'free3',
                'name',
                'plan_type',
                'plan_size',
                'plan_day',
                'check_balance'
            ];
            foreach ($stringColumns as $column) {
                if (!Schema::hasColumn('data_card_plan', $column)) {
                    $table->string($column)->nullable();
                }
            }

            if (!Schema::hasColumn('data_card_plan', 'plan_status')) {
                $table->boolean('plan_status')->default(1);
            }
        });

        // 5. recharge_card_plan Table
        if (!Schema::hasTable('recharge_card_plan')) {
            Schema::create('recharge_card_plan', function (Blueprint $table) {
                $table->id();
                $table->string('network')->nullable();
                $table->string('load_pin')->nullable();
                $table->boolean('plan_status')->default(1);
                $table->decimal('smart', 10, 2)->default(0)->nullable();
                $table->decimal('agent', 10, 2)->default(0)->nullable();
                $table->decimal('awuf', 10, 2)->default(0)->nullable();
                $table->decimal('special', 10, 2)->default(0)->nullable();
                $table->decimal('api', 10, 2)->default(0)->nullable();
                $table->string('habukhan1')->nullable();
                $table->string('habukhan2')->nullable();
                $table->string('habukhan3')->nullable();
                $table->string('habukhan4')->nullable();
                $table->string('habukhan5')->nullable();
                $table->string('free1')->nullable();
                $table->string('free2')->nullable();
                $table->string('free3')->nullable();
                $table->string('name')->nullable();
                $table->string('plan_id')->nullable();
                $table->string('check_balance')->nullable();
                $table->timestamps();
            });
        }

        // 6. network Table
        Schema::table('network', function (Blueprint $table) {
            $columns = ['msorg_id', 'virus_id'];
            foreach ($columns as $column) {
                if (!Schema::hasColumn('network', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We generally don't drop columns in down() to avoid accidental data loss during rollbacks in production,
        // unless strictly required. For this fix, we'll keep it simple.
    }
};
