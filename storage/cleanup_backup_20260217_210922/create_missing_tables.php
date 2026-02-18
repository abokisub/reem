<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Support Tables
DB::statement("CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_code VARCHAR(20) UNIQUE,
    user_id INT,
    subject VARCHAR(255),
    status VARCHAR(20) DEFAULT 'open',
    priority VARCHAR(20) DEFAULT 'medium',
    type VARCHAR(20) DEFAULT 'human',
    current_handler VARCHAR(20) DEFAULT 'agent',
    last_message TEXT NULL,
    last_message_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

DB::statement("CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT,
    sender_type VARCHAR(20),
    sender_id INT NULL,
    message TEXT,
    system_message BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

DB::statement("CREATE TABLE IF NOT EXISTS ai_learning_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100),
    response TEXT,
    action VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

echo "Support tables created.\n";

// Transaction Calculator Tables
$tables = ['data', 'airtime', 'cable', 'exam', 'bulksms', 'deposit', 'message', 'cash', 'bill', 'transfers', 'card_transactions', 'virtual_cards', 'donations'];

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        // Create generic table to prevent crash
        DB::statement("CREATE TABLE $table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NULL,
            details TEXT NULL,
            amount DECIMAL(15,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'success',
            plan_status INT DEFAULT 1,
            plan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            username VARCHAR(255) NULL
        )");

        echo "Created table: $table\n";
    }

    // Add specific cols if missing
    if ($table == 'data') {
        if (!Schema::hasColumn($table, 'network'))
            DB::statement("ALTER TABLE $table ADD COLUMN network VARCHAR(50) NULL");
        if (!Schema::hasColumn($table, 'network_type'))
            DB::statement("ALTER TABLE $table ADD COLUMN network_type VARCHAR(50) NULL");
        if (!Schema::hasColumn($table, 'plan_name'))
            DB::statement("ALTER TABLE $table ADD COLUMN plan_name VARCHAR(100) NULL");
    }
    if ($table == 'airtime') {
        if (!Schema::hasColumn($table, 'network'))
            DB::statement("ALTER TABLE $table ADD COLUMN network VARCHAR(50) NULL");
        if (!Schema::hasColumn($table, 'network_type'))
            DB::statement("ALTER TABLE $table ADD COLUMN network_type VARCHAR(50) NULL");
        if (!Schema::hasColumn($table, 'discount'))
            DB::statement("ALTER TABLE $table ADD COLUMN discount DECIMAL(15,2) DEFAULT 0");
    }
    if ($table == 'cable') {
        if (!Schema::hasColumn($table, 'cable_name'))
            DB::statement("ALTER TABLE $table ADD COLUMN cable_name VARCHAR(50) NULL");
        if (!Schema::hasColumn($table, 'charges'))
            DB::statement("ALTER TABLE $table ADD COLUMN charges DECIMAL(15,2) DEFAULT 0");
    }
    if ($table == 'exam') {
        if (!Schema::hasColumn($table, 'exam_name'))
            DB::statement("ALTER TABLE $table ADD COLUMN exam_name VARCHAR(50) NULL");
        if (!Schema::hasColumn($table, 'quantity'))
            DB::statement("ALTER TABLE $table ADD COLUMN quantity INT DEFAULT 1");
    }
    if ($table == 'transfers') {
        if (!Schema::hasColumn($table, 'charge'))
            DB::statement("ALTER TABLE $table ADD COLUMN charge DECIMAL(15,2) DEFAULT 0");
        if (!Schema::hasColumn($table, 'user_id'))
            DB::statement("ALTER TABLE $table ADD COLUMN user_id INT NULL");
    }
    if ($table == 'deposit') {
        if (!Schema::hasColumn($table, 'date'))
            DB::statement("ALTER TABLE $table ADD COLUMN date TIMESTAMP NULL");
        if (!Schema::hasColumn($table, 'charges'))
            DB::statement("ALTER TABLE $table ADD COLUMN charges DECIMAL(15,2) DEFAULT 0");
    }
    if ($table == 'message') {
        if (!Schema::hasColumn($table, 'habukhan_date'))
            DB::statement("ALTER TABLE $table ADD COLUMN habukhan_date TIMESTAMP NULL");
        if (!Schema::hasColumn($table, 'role'))
            DB::statement("ALTER TABLE $table ADD COLUMN role VARCHAR(50) NULL");
    }
    if ($table == 'cash') {
        if (!Schema::hasColumn($table, 'amount_credit'))
            DB::statement("ALTER TABLE $table ADD COLUMN amount_credit DECIMAL(15,2) DEFAULT 0");
    }
    if ($table == 'card_transactions') {
        if (!Schema::hasColumn($table, 'card_id'))
            DB::statement("ALTER TABLE $table ADD COLUMN card_id INT NULL");
        if (!Schema::hasColumn($table, 'currency'))
            DB::statement("ALTER TABLE $table ADD COLUMN currency VARCHAR(10) NULL");
    }
    if ($table == 'virtual_cards') {
        if (!Schema::hasColumn($table, 'card_id'))
            DB::statement("ALTER TABLE $table ADD COLUMN card_id INT NULL");
        if (!Schema::hasColumn($table, 'card_type'))
            DB::statement("ALTER TABLE $table ADD COLUMN card_type VARCHAR(20) NULL");
        if (!Schema::hasColumn($table, 'user_id'))
            DB::statement("ALTER TABLE $table ADD COLUMN user_id INT NULL");
    }
}

echo "All required tables checked/created.\n";
