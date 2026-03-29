<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingAdminFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'reason')) {
                $table->text('reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'webhook')) {
                $table->string('webhook')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('users', 'about')) {
                $table->text('about')->nullable()->after('webhook');
            }
            if (!Schema::hasColumn('users', 'user_limit')) {
                $table->decimal('user_limit', 20, 2)->default(0)->after('about');
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reason', 'webhook', 'about', 'user_limit']);
        });
    }
}
