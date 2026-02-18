<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReconciliationMismatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reconciliation_mismatches', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('provider_reference')->nullable();
            $table->string('internal_reference')->nullable();
            $table->decimal('amount_provider', 20, 2)->default(0);
            $table->decimal('amount_internal', 20, 2)->default(0);
            $table->string('type'); // missing_internal, missing_provider, amount_mismatch
            $table->string('status')->default('unresolved'); // unresolved, resolved, ignored
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['report_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reconciliation_mismatches');
    }
}
