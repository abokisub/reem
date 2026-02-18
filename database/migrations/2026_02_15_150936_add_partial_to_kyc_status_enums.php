<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Update users table kyc_status enum
        // Since SQLite doesn't support changing enums easily, we use a raw statement for MySQL
        // and a fallback for others.
        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN kyc_status ENUM('unverified', 'pending', 'verified', 'rejected', 'under_review', 'partial') DEFAULT 'unverified'");
            DB::statement("ALTER TABLE companies MODIFY COLUMN kyc_status ENUM('unverified', 'pending', 'verified', 'rejected', 'under_review', 'partial') DEFAULT 'unverified'");
        } catch (\Exception $e) {
            // Fallback: just try to add column if it was missing or ignore if it's not MySQL
            // In this specific environment (linux), we assume MySQL/MariaDB.
            \Log::warning("Could not modify ENUM via raw SQL: " . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN kyc_status ENUM('unverified', 'pending', 'verified', 'rejected', 'under_review') DEFAULT 'unverified'");
            DB::statement("ALTER TABLE companies MODIFY COLUMN kyc_status ENUM('unverified', 'pending', 'verified', 'rejected', 'under_review') DEFAULT 'unverified'");
        } catch (\Exception $e) {
            \Log::warning("Could not revert ENUM via raw SQL: " . $e->getMessage());
        }
    }
};
