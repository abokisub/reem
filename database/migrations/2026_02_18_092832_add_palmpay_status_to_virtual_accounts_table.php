<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->string('palmpay_status')->nullable()->after('palmpay_reference');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropColumn('palmpay_status');
        });
    }
};
