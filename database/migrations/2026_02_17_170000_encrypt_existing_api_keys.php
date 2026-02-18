<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class EncryptExistingApiKeys extends Migration
{
    /**
     * Run the migrations.
     * Encrypts all existing API keys in the companies table
     */
    public function up()
    {
        $companies = DB::table('companies')->get();

        foreach ($companies as $company) {
            $updates = [];

            // Encrypt live keys if not already encrypted
            if ($company->api_key && !$this->isEncrypted($company->api_key)) {
                $updates['api_key'] = Crypt::encryptString($company->api_key);
            }
            if ($company->api_secret_key && !$this->isEncrypted($company->api_secret_key)) {
                $updates['api_secret_key'] = Crypt::encryptString($company->api_secret_key);
            }

            // Encrypt test keys if not already encrypted
            if ($company->test_api_key && !$this->isEncrypted($company->test_api_key)) {
                $updates['test_api_key'] = Crypt::encryptString($company->test_api_key);
            }
            if ($company->test_secret_key && !$this->isEncrypted($company->test_secret_key)) {
                $updates['test_secret_key'] = Crypt::encryptString($company->test_secret_key);
            }

            // Encrypt webhook secrets if not already encrypted
            if ($company->webhook_secret && !$this->isEncrypted($company->webhook_secret)) {
                $updates['webhook_secret'] = Crypt::encryptString($company->webhook_secret);
            }
            if ($company->test_webhook_secret && !$this->isEncrypted($company->test_webhook_secret)) {
                $updates['test_webhook_secret'] = Crypt::encryptString($company->test_webhook_secret);
            }

            if (!empty($updates)) {
                DB::table('companies')->where('id', $company->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     * Decrypts all API keys back to plain text
     */
    public function down()
    {
        $companies = DB::table('companies')->get();

        foreach ($companies as $company) {
            $updates = [];

            // Decrypt live keys if encrypted
            if ($company->api_key && $this->isEncrypted($company->api_key)) {
                try {
                    $updates['api_key'] = Crypt::decryptString($company->api_key);
                } catch (\Exception $e) {
                    // Skip if decryption fails
                }
            }
            if ($company->api_secret_key && $this->isEncrypted($company->api_secret_key)) {
                try {
                    $updates['api_secret_key'] = Crypt::decryptString($company->api_secret_key);
                } catch (\Exception $e) {
                    // Skip if decryption fails
                }
            }

            // Decrypt test keys if encrypted
            if ($company->test_api_key && $this->isEncrypted($company->test_api_key)) {
                try {
                    $updates['test_api_key'] = Crypt::decryptString($company->test_api_key);
                } catch (\Exception $e) {
                    // Skip if decryption fails
                }
            }
            if ($company->test_secret_key && $this->isEncrypted($company->test_secret_key)) {
                try {
                    $updates['test_secret_key'] = Crypt::decryptString($company->test_secret_key);
                } catch (\Exception $e) {
                    // Skip if decryption fails
                }
            }

            // Decrypt webhook secrets if encrypted
            if ($company->webhook_secret && $this->isEncrypted($company->webhook_secret)) {
                try {
                    $updates['webhook_secret'] = Crypt::decryptString($company->webhook_secret);
                } catch (\Exception $e) {
                    // Skip if decryption fails
                }
            }
            if ($company->test_webhook_secret && $this->isEncrypted($company->test_webhook_secret)) {
                try {
                    $updates['test_webhook_secret'] = Crypt::decryptString($company->test_webhook_secret);
                } catch (\Exception $e) {
                    // Skip if decryption fails
                }
            }

            if (!empty($updates)) {
                DB::table('companies')->where('id', $company->id)->update($updates);
            }
        }
    }

    /**
     * Check if a string is already encrypted
     */
    private function isEncrypted($value): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
