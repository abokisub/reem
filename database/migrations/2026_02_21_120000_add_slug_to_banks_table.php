<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add slug column
        Schema::table('banks', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->index('slug');
        });

        // Generate slugs for existing banks
        $banks = DB::table('banks')->get();
        foreach ($banks as $bank) {
            $slug = Str::slug($bank->name);
            DB::table('banks')
                ->where('id', $bank->id)
                ->update(['slug' => $slug]);
        }

        // Make slug non-nullable after populating
        Schema::table('banks', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
