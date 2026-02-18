<?php

namespace App\Services\PalmPay;

use Illuminate\Support\Facades\Log;

/**
 * PalmPay Signature Service
 * 
 * Handles RSA signature generation and verification for PalmPay API requests
 */
class PalmPaySignature
{
    private string $privateKey;
    private string $publicKey;
    private string $palmpayPublicKey;

    public function __construct()
    {
        $this->privateKey = $this->formatKey(config('services.palmpay.private_key'));
        $this->publicKey = $this->formatKey(config('services.palmpay.public_key'));
        $this->palmpayPublicKey = $this->formatKey(config('services.palmpay.palmpay_public_key'));
    }

    /**
     * Format key string to ensure valid PEM format
     */
    private function formatKey(?string $key): string
    {
        if (!$key) {
            return '';
        }
        // If key has no newlines but contains \n literals, replace them
        if (strpos($key, "\n") === false && strpos($key, '\n') !== false) {
            return str_replace('\n', "\n", $key);
        }
        return $key;
    }

    /**
     * Generate signature for outgoing requests to PalmPay
     * 
     * @param array $data Request payload
     * @return string Base64 encoded signature
     */
    public function generateSignature(array $data): string
    {
        // Sort data by keys
        ksort($data);

        // Create signature string
        $signatureString = $this->buildSignatureString($data);

        // Step 2: MD5 hash and convert to uppercase
        $md5Str = strtoupper(md5($signatureString));

        Log::debug('PalmPay Signature Debug', [
            'original_string' => $signatureString,
            'md5_uppercase' => $md5Str
        ]);

        // Load private key
        $privateKey = openssl_pkey_get_private($this->privateKey);

        if (!$privateKey) {
            // Try to provide a more helpful error if key is invalid
            $error = openssl_error_string();
            // Check if it's a format issue
            if (empty($this->privateKey)) {
                $error = "Private key is empty. Check PALMPAY_PRIVATE_KEY in .env";
            }
            throw new \Exception('Failed to load private key: ' . $error);
        }

        // Sign the md5Str using SHA1WithRSA
        $signature = '';
        $success = openssl_sign($md5Str, $signature, $privateKey, OPENSSL_ALGO_SHA1);

        if (!$success) {
            throw new \Exception('Failed to generate signature: ' . openssl_error_string());
        }



        // Return base64 encoded signature
        return base64_encode($signature);
    }

    /**
     * Verify signature from PalmPay webhook
     * 
     * @param array $data Webhook payload
     * @param string $signature Signature from PalmPay
     * @return bool
     */
    public function verifyWebhookSignature(array $data, string $signature): bool
    {
        try {
            // Sort data by keys
            ksort($data);

            // Create signature string
            $signatureString = $this->buildSignatureString($data);

            // Step 2: MD5 hash and convert to uppercase
            $md5Str = strtoupper(md5($signatureString));

            Log::debug('PalmPay Webhook Signature Verification', [
                'signature_string' => $signatureString,
                'md5_uppercase' => $md5Str,
                'signature' => $signature
            ]);

            // Load PalmPay's public key
            $publicKey = openssl_pkey_get_public($this->palmpayPublicKey);

            if (!$publicKey) {
                Log::error('Failed to load PalmPay public key: ' . openssl_error_string());
                return false;
            }

            // Decode signature
            $decodedSignature = base64_decode($signature);

            // Verify the signature of the md5Str using SHA1WithRSA
            $result = openssl_verify($md5Str, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA1);

            // Free the key resource

            return $result === 1;

        } catch (\Exception $e) {
            Log::error('PalmPay Signature Verification Error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Build signature string from data array
     * 
     * @param array $data
     * @return string
     */
    private function buildSignatureString(array $data): string
    {
        $parts = [];

        foreach ($data as $key => $value) {
            // Skip signature field itself
            if ($key === 'sign' || $key === 'signature') {
                continue;
            }

            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            // Handle arrays/objects
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            } else {
                // Trim leading and trailing spaces from all non-empty parameter values
                // as required by PalmPay documentation: "Remove any leading and trailing spaces 
                // from all non-empty parameter values in the request body"
                $value = trim($value);
            }

            $parts[] = $key . '=' . $value;
        }

        return implode('&', $parts);
    }

    /**
     * Generate RSA key pair (for initial setup)
     * 
     * @return array ['private_key' => string, 'public_key' => string]
     */
    public static function generateKeyPair(): array
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if (!$resource) {
            throw new \Exception('Failed to generate key pair: ' . openssl_error_string());
        }

        // Export private key
        openssl_pkey_export($resource, $privateKey);

        // Export public key
        $publicKeyDetails = openssl_pkey_get_details($resource);
        $publicKey = $publicKeyDetails['key'];



        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }
}