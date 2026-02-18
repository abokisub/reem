<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('users', 'paystack_bank')) {
                $table->string('paystack_bank')->nullable()->after('linkedin');
            }
            if (!Schema::hasColumn('users', 'paystack_account')) {
                $table->string('paystack_account')->nullable()->after('paystack_bank');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'paystack_bank')) {
                $table->dropColumn('paystack_bank');
            }
            if (Schema::hasColumn('users', 'paystack_account')) {
                $table->dropColumn('paystack_account');
            }
        });
    }
};
