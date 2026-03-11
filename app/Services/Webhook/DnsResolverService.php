<?php

namespace App\Services\Webhook;

use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * DNS Resolver Service for Webhook Delivery
 * 
 * Handles DNS resolution issues by maintaining IP mappings and fallback URLs
 */
class DnsResolverService
{
    /**
     * Known DNS servers for testing resolution
     */
    private array $dnsServers = [
        'cloudflare' => '1.1.1.1',
        'google' => '8.8.8.8',
        'opendns' => '208.67.222.222'
    ];
    
    /**
     * Resolve webhook URL with DNS fallback handling
     * 
     * @param Company $company
     * @param bool $isTest
     * @return string|null
     */
    public function resolveWebhookUrl(Company $company, bool $isTest = false): ?string
    {
        $webhookUrl = $isTest ? $company->test_webhook_url : $company->webhook_url;
        
        if (!$webhookUrl) {
            return null;
        }
        
        // Extract domain from URL
        $parsedUrl = parse_url($webhookUrl);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return $webhookUrl; // Return as-is if can't parse
        }
        
        $domain = $parsedUrl['host'];
        
        // Check if domain resolves
        if ($this->canResolveDomain($domain)) {
            Log::debug('DNS resolution successful', [
                'company_id' => $company->id,
                'domain' => $domain,
                'url' => $webhookUrl
            ]);
            return $webhookUrl;
        }
        
        // DNS resolution failed, try to get IP mapping
        Log::warning('DNS resolution failed, attempting IP fallback', [
            'company_id' => $company->id,
            'domain' => $domain,
            'original_url' => $webhookUrl
        ]);
        
        $ipAddress = $this->getIpMapping($company, $domain);
        
        if ($ipAddress) {
            // Create IP-based URL
            $ipBasedUrl = str_replace($domain, $ipAddress, $webhookUrl);
            
            // Update company record with fallback URL
            $this->updateWebhookUrlWithFallback($company, $webhookUrl, $ipBasedUrl, $isTest);
            
            Log::info('Using IP-based webhook URL fallback', [
                'company_id' => $company->id,
                'original_url' => $webhookUrl,
                'fallback_url' => $ipBasedUrl,
                'ip_address' => $ipAddress
            ]);
            
            return $ipBasedUrl;
        }
        
        // No fallback available
        Log::error('No DNS resolution fallback available', [
            'company_id' => $company->id,
            'domain' => $domain,
            'url' => $webhookUrl
        ]);
        
        return null;
    }
    
    /**
     * Check if domain can be resolved
     * 
     * @param string $domain
     * @return bool
     */
    private function canResolveDomain(string $domain): bool
    {
        // Cache DNS resolution results for 5 minutes
        $cacheKey = "dns_resolution_{$domain}";
        
        return Cache::remember($cacheKey, 300, function () use ($domain) {
            $ip = gethostbyname($domain);
            return $ip !== $domain; // gethostbyname returns domain if resolution fails
        });
    }
    
    /**
     * Get IP mapping for domain from various sources
     * 
     * @param Company $company
     * @param string $domain
     * @return string|null
     */
    private function getIpMapping(Company $company, string $domain): ?string
    {
        // 1. Check company's stored DNS resolution data
        $dnsIssues = $company->dns_resolution_issues ?? [];
        if (isset($dnsIssues[$domain]['ip_address'])) {
            $storedIp = $dnsIssues[$domain]['ip_address'];
            Log::info('Using stored IP mapping', [
                'domain' => $domain,
                'ip' => $storedIp,
                'company_id' => $company->id
            ]);
            return $storedIp;
        }
        
        // 2. Try different DNS servers
        foreach ($this->dnsServers as $name => $dnsServer) {
            $ip = $this->queryDnsServer($domain, $dnsServer);
            if ($ip) {
                Log::info('DNS resolution successful with alternative server', [
                    'domain' => $domain,
                    'ip' => $ip,
                    'dns_server' => $name,
                    'company_id' => $company->id
                ]);
                
                // Store the IP mapping for future use
                $this->storeIpMapping($company, $domain, $ip, $name);
                return $ip;
            }
        }
        
        // 3. Check known IP mappings for common domains
        $knownMappings = $this->getKnownIpMappings();
        if (isset($knownMappings[$domain])) {
            $knownIp = $knownMappings[$domain];
            Log::info('Using known IP mapping', [
                'domain' => $domain,
                'ip' => $knownIp,
                'company_id' => $company->id
            ]);
            
            $this->storeIpMapping($company, $domain, $knownIp, 'known_mapping');
            return $knownIp;
        }
        
        return null;
    }
    
    /**
     * Query specific DNS server for domain resolution
     * 
     * @param string $domain
     * @param string $dnsServer
     * @return string|null
     */
    private function queryDnsServer(string $domain, string $dnsServer): ?string
    {
        try {
            $command = "dig @{$dnsServer} {$domain} +short +time=3 +tries=1";
            $result = shell_exec($command);
            
            if ($result) {
                $lines = array_filter(explode("\n", trim($result)));
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Check if it's a valid IP address
                    if (filter_var($line, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return $line;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('DNS query failed', [
                'domain' => $domain,
                'dns_server' => $dnsServer,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Get known IP mappings for common webhook domains
     * 
     * @return array
     */
    private function getKnownIpMappings(): array
    {
        return [
            'app.oyitipay.com' => '87.98.128.166',
            // Add more known mappings as needed
        ];
    }
    
    /**
     * Store IP mapping in company record
     * 
     * @param Company $company
     * @param string $domain
     * @param string $ip
     * @param string $source
     * @return void
     */
    private function storeIpMapping(Company $company, string $domain, string $ip, string $source): void
    {
        $dnsIssues = $company->dns_resolution_issues ?? [];
        
        $dnsIssues[$domain] = [
            'ip_address' => $ip,
            'source' => $source,
            'discovered_at' => now()->toISOString(),
            'last_used_at' => now()->toISOString()
        ];
        
        $company->update([
            'dns_resolution_issues' => $dnsIssues,
            'dns_last_checked_at' => now()
        ]);
    }
    
    /**
     * Update webhook URL with fallback
     * 
     * @param Company $company
     * @param string $originalUrl
     * @param string $fallbackUrl
     * @param bool $isTest
     * @return void
     */
    private function updateWebhookUrlWithFallback(Company $company, string $originalUrl, string $fallbackUrl, bool $isTest): void
    {
        if ($isTest) {
            $company->update([
                'test_webhook_url_backup' => $originalUrl,
                'test_webhook_url' => $fallbackUrl
            ]);
        } else {
            $company->update([
                'webhook_url_backup' => $originalUrl,
                'webhook_url' => $fallbackUrl
            ]);
        }
        
        Log::info('Webhook URL updated with DNS fallback', [
            'company_id' => $company->id,
            'is_test' => $isTest,
            'original_url' => $originalUrl,
            'fallback_url' => $fallbackUrl
        ]);
    }
    
    /**
     * Restore original webhook URL when DNS is fixed
     * 
     * @param Company $company
     * @param bool $isTest
     * @return bool
     */
    public function restoreOriginalWebhookUrl(Company $company, bool $isTest = false): bool
    {
        $backupField = $isTest ? 'test_webhook_url_backup' : 'webhook_url_backup';
        $urlField = $isTest ? 'test_webhook_url' : 'webhook_url';
        
        $backupUrl = $company->$backupField;
        
        if (!$backupUrl) {
            return false;
        }
        
        // Test if original URL now resolves
        $parsedUrl = parse_url($backupUrl);
        if ($parsedUrl && isset($parsedUrl['host'])) {
            $domain = $parsedUrl['host'];
            
            if ($this->canResolveDomain($domain)) {
                // DNS is fixed, restore original URL
                $company->update([
                    $urlField => $backupUrl,
                    $backupField => null
                ]);
                
                Log::info('Original webhook URL restored', [
                    'company_id' => $company->id,
                    'is_test' => $isTest,
                    'restored_url' => $backupUrl
                ]);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check and restore webhook URLs for all companies with DNS issues
     * 
     * @return array
     */
    public function checkAndRestoreAllWebhookUrls(): array
    {
        $results = [];
        
        $companiesWithBackups = Company::whereNotNull('webhook_url_backup')
            ->orWhereNotNull('test_webhook_url_backup')
            ->get();
        
        foreach ($companiesWithBackups as $company) {
            $restored = [];
            
            if ($company->webhook_url_backup) {
                if ($this->restoreOriginalWebhookUrl($company, false)) {
                    $restored[] = 'production';
                }
            }
            
            if ($company->test_webhook_url_backup) {
                if ($this->restoreOriginalWebhookUrl($company, true)) {
                    $restored[] = 'test';
                }
            }
            
            if (!empty($restored)) {
                $results[] = [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'restored' => $restored
                ];
            }
        }
        
        return $results;
    }
}