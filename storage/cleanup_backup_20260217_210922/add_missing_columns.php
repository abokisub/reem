<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Checking for missing columns...\n";

// 1. Fix Settings Table (for Welcome Message)
if (Schema::hasTable('settings')) {
    if (!Schema::hasColumn('settings', 'notif_show')) {
        echo "Adding 'notif_show' to 'settings' table...\n";
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('notif_show')->default(0);
        });
    } else {
        echo "'notif_show' already exists in 'settings'.\n";
    }
} else {
    echo "Error: 'settings' table not found.\n";
}

// 2. Fix Users Table (for Payment Providers in AuthController)
if (Schema::hasTable('users')) {
    $columns = ['palmpay', 'opay', 'paystack_account', 'webhook'];

    foreach ($columns as $column) {
        if (!Schema::hasColumn('users', $column)) {
            echo "Adding '$column' to 'users' table...\n";
            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->string($column)->nullable();
            });
        } else {
            echo "'$column' already exists in 'users'.\n";
        }
    }
} else {
    echo "Error: 'users' table not found.\n";
}

echo "Done.\n";
