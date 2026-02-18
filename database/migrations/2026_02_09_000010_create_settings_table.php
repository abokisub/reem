<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();

            // Index
            $table->index('key');
        });

        // Insert default settings
        DB::table('settings')->insert([
            [
                'key' => 'palmpay_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable PalmPay integration',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_transaction_fee',
                'value' => '1.5',
                'type' => 'decimal',
                'description' => 'Default transaction fee percentage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'vtu_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable VTU services',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sim_hosting_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable SIM hosting services',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};