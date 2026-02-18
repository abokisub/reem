<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Archive old payment provider data and remove their tables/columns
     */
    public function up(): void
    {
        // STEP 1: Archive existing data before deletion
        $this->archiveData();

        // STEP 2: Drop old payment provider tables
        $this->dropProviderTables();

        // STEP 3: Remove provider-specific columns from existing tables
        $this->removeProviderColumns();
    }

    /**
     * Archive data from tables that will be dropped
     */
    private function archiveData(): void
    {
        $timestamp = date('Ymd_His');

        // Archive Xixapay tables (if they exist)
        if (Schema::hasTable('xixapay_customers')) {
            DB::statement("CREATE TABLE xixapay_customers_archive_{$timestamp} AS SELECT * FROM xixapay_customers");
        }

        if (Schema::hasTable('xixapay_virtual_accounts')) {
            DB::statement("CREATE TABLE xixapay_virtual_accounts_archive_{$timestamp} AS SELECT * FROM xixapay_virtual_accounts");
        }

        if (Schema::hasTable('xixapay_transactions')) {
            DB::statement("CREATE TABLE xixapay_transactions_archive_{$timestamp} AS SELECT * FROM xixapay_transactions");
        }

        if (Schema::hasTable('xixapay_webhooks')) {
            DB::statement("CREATE TABLE xixapay_webhooks_archive_{$timestamp} AS SELECT * FROM xixapay_webhooks");
        }

        // Archive Paystack table
        if (Schema::hasTable('paystack_key')) {
            DB::statement("CREATE TABLE paystack_key_archive_{$timestamp} AS SELECT * FROM paystack_key");
        }

        // Archive Monnify tables (if they exist)
        if (Schema::hasTable('monnify_accounts')) {
            DB::statement("CREATE TABLE monnify_accounts_archive_{$timestamp} AS SELECT * FROM monnify_accounts");
        }

        if (Schema::hasTable('monnify_transactions')) {
            DB::statement("CREATE TABLE monnify_transactions_archive_{$timestamp} AS SELECT * FROM monnify_transactions");
        }

        if (Schema::hasTable('monnify_webhooks')) {
            DB::statement("CREATE TABLE monnify_webhooks_archive_{$timestamp} AS SELECT * FROM monnify_webhooks");
        }

        // Archive user virtual account data
        DB::statement("
            CREATE TABLE user_virtual_accounts_archive_{$timestamp} AS 
            SELECT 
                id, username, email,
                paystack_account, paystack_bank,
                sterlen, vdf, fed,
                kolomoni_mfb, palmpay,
                xixapay_kyc_data,
                created_at, updated_at
            FROM user
            WHERE paystack_account IS NOT NULL 
               OR sterlen IS NOT NULL 
               OR kolomoni_mfb IS NOT NULL
               OR xixapay_kyc_data IS NOT NULL
        ");
    }

    /**
     * Drop old payment provider tables
     */
    private function dropProviderTables(): void
    {
        // Drop Xixapay tables
        Schema::dropIfExists('xixapay_webhooks');
        Schema::dropIfExists('xixapay_transactions');
        Schema::dropIfExists('xixapay_virtual_accounts');
        Schema::dropIfExists('xixapay_customers');

        // Drop Paystack tables
        Schema::dropIfExists('paystack_key');

        // Drop Monnify tables (if they exist)
        Schema::dropIfExists('monnify_webhooks');
        Schema::dropIfExists('monnify_transactions');
        Schema::dropIfExists('monnify_accounts');
    }

    /**
     * Remove provider-specific columns from existing tables
     */
    private function removeProviderColumns(): void
    {
        // Remove columns from user table
        Schema::table('user', function (Blueprint $table) {
            // Paystack columns
            if (Schema::hasColumn('user', 'paystack_account')) {
                $table->dropColumn('paystack_account');
            }
            if (Schema::hasColumn('user', 'paystack_bank')) {
                $table->dropColumn('paystack_bank');
            }

            // Monnify columns (WEMA Bank virtual accounts)
            if (Schema::hasColumn('user', 'sterlen')) {
                $table->dropColumn('sterlen');
            }
            if (Schema::hasColumn('user', 'vdf')) {
                $table->dropColumn('vdf');
            }
            if (Schema::hasColumn('user', 'fed')) {
                $table->dropColumn('fed');
            }

            // Xixapay columns
            if (Schema::hasColumn('user', 'kolomoni_mfb')) {
                $table->dropColumn('kolomoni_mfb');
            }
            if (Schema::hasColumn('user', 'xixapay_kyc_data')) {
                $table->dropColumn('xixapay_kyc_data');
            }
        });

        // Remove columns from settings table
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (Schema::hasColumn('settings', 'monnify_charge')) {
                    $table->dropColumn('monnify_charge');
                }
                if (Schema::hasColumn('settings', 'xixapay_charge')) {
                    $table->dropColumn('xixapay_charge');
                }
                if (Schema::hasColumn('settings', 'paystack_charge')) {
                    $table->dropColumn('paystack_charge');
                }
                if (Schema::hasColumn('settings', 'monnify_enabled')) {
                    $table->dropColumn('monnify_enabled');
                }
                if (Schema::hasColumn('settings', 'xixapay_enabled')) {
                    $table->dropColumn('xixapay_enabled');
                }
                if (Schema::hasColumn('settings', 'default_virtual_account')) {
                    $table->dropColumn('default_virtual_account');
                }
            });
        }

        // Remove columns from unified_banks table
        if (Schema::hasTable('unified_banks')) {
            Schema::table('unified_banks', function (Blueprint $table) {
                if (Schema::hasColumn('unified_banks', 'paystack_code')) {
                    $table->dropColumn('paystack_code');
                }
                if (Schema::hasColumn('unified_banks', 'xixapay_code')) {
                    $table->dropColumn('xixapay_code');
                }
                if (Schema::hasColumn('unified_banks', 'monnify_code')) {
                    $table->dropColumn('monnify_code');
                }
            });
        }

        // Remove columns from card_transactions table (if exists)
        if (Schema::hasTable('card_transactions')) {
            Schema::table('card_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('card_transactions', 'xixapay_transaction_id')) {
                    $table->dropColumn('xixapay_transaction_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations (restore from archives)
     */
    public function down(): void
    {
        // This is intentionally left empty
        // To rollback, restore from database backup or archive tables manually
        // DO NOT auto-restore as it may cause data conflicts

        echo "\n";
        echo "⚠️  ROLLBACK WARNING ⚠️\n";
        echo "This migration cannot be automatically rolled back.\n";
        echo "To restore data, use your database backup or archive tables.\n";
        echo "Archive tables are named: *_archive_YYYYMMDD_HHMMSS\n";
        echo "\n";
    }
};