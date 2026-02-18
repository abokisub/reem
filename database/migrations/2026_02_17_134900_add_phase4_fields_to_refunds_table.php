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
        Schema::table('refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('refunds', 'refund_type')) {
                $table->enum('refund_type', ['auto', 'manual'])->default('manual')->after('reason')->comment('Refund trigger type');
            }
            if (!Schema::hasColumn('refunds', 'initiated_by')) {
                $table->unsignedBigInteger('initiated_by')->nullable()->after('refund_type')->comment('User ID who initiated manual refund');
                $table->foreign('initiated_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('refunds', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('initiated_by')->comment('Admin notes for manual refunds');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['initiated_by']);
            $table->dropColumn(['refund_type', 'initiated_by', 'admin_notes']);
        });
    }
};
