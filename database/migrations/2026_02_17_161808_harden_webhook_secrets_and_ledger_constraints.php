<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HardenWebhookSecretsAndLedgerConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('old_webhook_secret')->nullable()->after('webhook_secret');
            $table->timestamp('webhook_secret_expires_at')->nullable()->after('old_webhook_secret');

            $table->text('old_test_webhook_secret')->nullable()->after('test_webhook_secret');
            $table->timestamp('test_webhook_secret_expires_at')->nullable()->after('old_test_webhook_secret');
        });

        // Add DB-level CHECK constraint to ledger_entries if supported (MySQL 8.0.16+)
        // Otherwise, it's just a safeguard in the app layer, but we'll try raw SQL.
        try {
            DB::statement('ALTER TABLE ledger_entries ADD CONSTRAINT check_debit_not_credit CHECK (debit_account_id <> credit_account_id)');
        } catch (\Exception $e) {
            // Log if not supported by DB engine, but continue
        }

        // Immutable Ledger Triggers
        DB::unprepared('
            CREATE TRIGGER prevent_ledger_update
            BEFORE UPDATE ON ledger_entries
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Ledger is append-only. Updates are forbidden.";
            END;
        ');

        DB::unprepared('
            CREATE TRIGGER prevent_ledger_delete
            BEFORE DELETE ON ledger_entries
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Ledger is append-only. Deletions are forbidden.";
            END;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'old_webhook_secret',
                'webhook_secret_expires_at',
                'old_test_webhook_secret',
                'test_webhook_secret_expires_at'
            ]);
        });

        DB::unprepared('DROP TRIGGER IF EXISTS prevent_ledger_update');
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_ledger_delete');
    }
}
