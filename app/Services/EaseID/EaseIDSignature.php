<?php

namespace App\Services\EaseID;

use Illuminate\Support\Facades\Log;

/**
 * EaseID Signature Service
 * 
 * Handles RSA signature generation and verification for EaseID API requests
 */
class EaseIDSignature
{
    private string $privateKey;
    private string $publicKey;
    private string $platformPublicKey;

    public function __construct()
    {
        $this->privateKey = $this->formatKey(config('services.easeid.private_key'));
        $this->publicKey = $this->formatKey(config('services.easeid.public_key'));
        $this->platformPublicKey = $this->formatKey(config('services.easeid.platform_public_key'));
    }

    /**
     * Format key string to ensure valid PEM format
     */
    private function formatKey(?string $key): string
    {
        if (!$key) {
            return '';
        }

        // Remove whitespace and check if it's already in PEM format
        $trimmedKey = trim($key);
        if (strpos($trimmedKey, '-----BEGIN') !== false) {
            return $trimmedKey;
        }

        // If it's a raw Base64 string from .env, wrap it in PEM tags
        return "-----BEGIN PRIVATE KEY-----\n" . wordwrap($trimmedKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
    }

    /**
     * Generate signature for outgoing requests to EaseID
     * 
     * @param array $data Request payload
     * @return string Base64 encoded signature
     */
    public function generateSignature(array $data): string
    {
        // Sort data by keys (ASCII dictionary order)
        ksort($data);

        // Create signature string (key1=value1&key2=value2)
        $signatureString = $this->buildSignatureString($data);

        // Step 2: MD5 hash (lowercase hex as per standard PHP md5, but EaseID might expect uppercase)
        // Documentation says MD5 hash, usually implies uppercase hex for these gateways
        $md5Str = strtoupper(md5($signatureString));

        Log::debug('EaseID Signature Debug', [
            'original_string' => $signatureString,
            'md5_uppercase' => $md5Str
        ]);

        // Load private key
        $privateKey = openssl_pkey_get_private($this->privateKey);

        if (!$privateKey) {
            $error = openssl_error_string();
            if (empty($this->privateKey)) {
                $error = "Private key is empty. Check EASEID_PRIVATE_KEY in .env";
            }
            throw new \Exception('Failed to load EaseID private key: ' . $error);
        }

        // Sign the md5Str using SHA1WithRSA
        $signature = '';
        $success = openssl_sign($md5Str, $signature, $privateKey, OPENSSL_ALGO_SHA1);

        if (!$success) {
            throw new \Exception('Failed to generate EaseID signature: ' . openssl_error_string());
        }



        // Return base64 encoded signature
        return base64_encode($signature);
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
            }

            $parts[] = $key . '=' . $value;
        }

        return implode('&', $parts);
    }
}
