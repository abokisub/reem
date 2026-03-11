<?php

namespace App\Services;

use App\Models\GlobalKycPool;
use App\Models\GlobalKycUsageLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Global KYC Service
 * 
 * Manages the shared pool of KYC numbers that all companies can use as fallback
 * when their own director KYC fails
 */
class GlobalKycService
{
    /**
     * Select the optimal KYC from global pool
     * 
     * @param string|null $preferredType 'bvn' or 'nin' - if null, selects best available
     * @return GlobalKycPool|null
     */
    public function selectOptimalGlobalKyc(?string $preferredType = null): ?GlobalKycPool
    {
        $query = GlobalKycPool::available();
        
        // Filter by preferred type if specified
        if ($preferredType) {
            $query->byType($preferredType);
        }
        
        // Smart selection: Prefer NIN over BVN (more stable), then least used
        $kyc = $query->when(!$preferredType, function ($q) {
                // If no preference, prioritize NIN over BVN
                return $q->orderByRaw("CASE WHEN kyc_type = 'nin' THEN 0 ELSE 1 END");
            })
            ->highestSuccessFirst()
            ->leastUsedFirst()
            ->first();
        
        if ($kyc) {
            Log::info('GlobalKyc: Selected KYC from global pool', [
                'kyc_id' => $kyc->id,
                'kyc_type' => $kyc->kyc_type,
                'kyc_number' => substr($kyc->kyc_number, 0, 5) . '***',
                'usage_count' => $kyc->usage_count,
                'success_rate' => $kyc->success_rate
            ]);
        } else {
            Log::warning('GlobalKyc: No available KYC in global pool', [
                'preferred_type' => $preferredType,
                'total_kyc_count' => GlobalKycPool::count(),
                'active_kyc_count' => GlobalKycPool::where('is_active', true)->count()
            ]);
        }
        
        return $kyc;
    }
    
    /**
     * Record usage of a global KYC
     * 
     * @param int $kycId
     * @param int $companyId
     * @param bool $success
     * @param string|null $errorMessage
     * @param int|null $virtualAccountId
     * @param array|null $requestData
     * @return void
     */
    public function recordUsage(
        int $kycId, 
        int $companyId, 
        bool $success, 
        ?string $errorMessage = null,
        ?int $virtualAccountId = null,
        ?array $requestData = null
    ): void {
        DB::transaction(function () use ($kycId, $companyId, $success, $errorMessage, $virtualAccountId, $requestData) {
            // Get the KYC record
            $kyc = GlobalKycPool::find($kycId);
            if (!$kyc) {
                Log::error('GlobalKyc: Attempted to record usage for non-existent KYC', ['kyc_id' => $kycId]);
                return;
            }
            
            // Update KYC statistics
            $kyc->increment('usage_count');
            
            if ($success) {
                $kyc->increment('success_count');
                $kyc->update([
                    'last_success_at' => now(),
                    'last_used_at' => now()
                ]);
            } else {
                $kyc->increment('failure_count');
                $kyc->update(['last_used_at' => now()]);
                
                // Auto-blacklist if failure rate is too high
                $this->checkAndBlacklistIfNeeded($kyc);
            }
            
            // Create usage log
            GlobalKycUsageLog::create([
                'global_kyc_id' => $kycId,
                'company_id' => $companyId,
                'virtual_account_id' => $virtualAccountId,
                'kyc_number' => $kyc->kyc_number,
                'kyc_type' => $kyc->kyc_type,
                'success' => $success,
                'error_message' => $errorMessage,
                'request_data' => $requestData
            ]);
            
            Log::info('GlobalKyc: Recorded usage', [
                'kyc_id' => $kycId,
                'company_id' => $companyId,
                'success' => $success,
                'new_usage_count' => $kyc->usage_count,
                'success_rate' => $kyc->success_rate
            ]);
        });
    }
    
    /**
     * Manually blacklist a KYC for specified duration
     * 
     * @param int $kycId
     * @param int $hours Duration in hours (default: 24)
     * @param string|null $reason
     * @return bool
     */
    public function blacklistKyc(int $kycId, int $hours = 24, ?string $reason = null): bool
    {
        $kyc = GlobalKycPool::find($kycId);
        if (!$kyc) {
            return false;
        }
        
        $blacklistedUntil = now()->addHours($hours);
        
        $kyc->update([
            'blacklisted_until' => $blacklistedUntil,
            'notes' => $reason ? "Blacklisted: $reason" : "Blacklisted until $blacklistedUntil"
        ]);
        
        Log::warning('GlobalKyc: KYC blacklisted', [
            'kyc_id' => $kycId,
            'kyc_type' => $kyc->kyc_type,
            'blacklisted_until' => $blacklistedUntil,
            'reason' => $reason
        ]);
        
        return true;
    }
    
    /**
     * Add new KYC to global pool
     * 
     * @param string $kycNumber
     * @param string $kycType 'bvn' or 'nin'
     * @param string|null $notes
     * @return GlobalKycPool|null
     */
    public function addGlobalKyc(string $kycNumber, string $kycType, ?string $notes = null): ?GlobalKycPool
    {
        try {
            $kyc = GlobalKycPool::create([
                'kyc_type' => $kycType,
                'kyc_number' => $kycNumber,
                'is_active' => true,
                'notes' => $notes
            ]);
            
            Log::info('GlobalKyc: Added new KYC to global pool', [
                'kyc_id' => $kyc->id,
                'kyc_type' => $kycType,
                'kyc_number' => substr($kycNumber, 0, 5) . '***'
            ]);
            
            return $kyc;
            
        } catch (\Exception $e) {
            Log::error('GlobalKyc: Failed to add KYC to global pool', [
                'kyc_type' => $kycType,
                'kyc_number' => substr($kycNumber, 0, 5) . '***',
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Get usage statistics for global KYC pool
     * 
     * @return array
     */
    public function getUsageStats(): array
    {
        $totalKyc = GlobalKycPool::count();
        $activeKyc = GlobalKycPool::where('is_active', true)->count();
        $availableKyc = GlobalKycPool::available()->count();
        $blacklistedKyc = GlobalKycPool::where('blacklisted_until', '>', now())->count();
        
        $totalUsage = GlobalKycPool::sum('usage_count');
        $totalSuccess = GlobalKycPool::sum('success_count');
        $totalFailures = GlobalKycPool::sum('failure_count');
        
        $overallSuccessRate = $totalUsage > 0 ? ($totalSuccess / $totalUsage) * 100 : 100;
        
        $recentUsage = GlobalKycUsageLog::where('created_at', '>=', now()->subDay())->count();
        $recentSuccess = GlobalKycUsageLog::where('created_at', '>=', now()->subDay())
            ->where('success', true)->count();
        
        return [
            'pool_stats' => [
                'total_kyc' => $totalKyc,
                'active_kyc' => $activeKyc,
                'available_kyc' => $availableKyc,
                'blacklisted_kyc' => $blacklistedKyc
            ],
            'usage_stats' => [
                'total_usage' => $totalUsage,
                'total_success' => $totalSuccess,
                'total_failures' => $totalFailures,
                'overall_success_rate' => round($overallSuccessRate, 2)
            ],
            'recent_stats' => [
                'last_24h_usage' => $recentUsage,
                'last_24h_success' => $recentSuccess,
                'last_24h_success_rate' => $recentUsage > 0 ? round(($recentSuccess / $recentUsage) * 100, 2) : 100
            ]
        ];
    }
    
    /**
     * Check if KYC should be auto-blacklisted due to high failure rate
     * 
     * @param GlobalKycPool $kyc
     * @return void
     */
    private function checkAndBlacklistIfNeeded(GlobalKycPool $kyc): void
    {
        // Only check if we have enough usage data
        if ($kyc->usage_count < 5) {
            return;
        }
        
        // Auto-blacklist if success rate drops below 20%
        if ($kyc->success_rate < 20) {
            $this->blacklistKyc(
                $kyc->id, 
                24, 
                "Auto-blacklisted: Success rate {$kyc->success_rate}% below threshold"
            );
        }
    }
    
    /**
     * Get available KYC count by type
     * 
     * @return array
     */
    public function getAvailableKycByType(): array
    {
        return [
            'bvn' => GlobalKycPool::available()->byType('bvn')->count(),
            'nin' => GlobalKycPool::available()->byType('nin')->count()
        ];
    }
    
    /**
     * Clean up old usage logs (keep last 30 days)
     * 
     * @return int Number of deleted records
     */
    public function cleanupOldLogs(): int
    {
        $deleted = GlobalKycUsageLog::where('created_at', '<', now()->subDays(30))->delete();
        
        if ($deleted > 0) {
            Log::info('GlobalKyc: Cleaned up old usage logs', ['deleted_count' => $deleted]);
        }
        
        return $deleted;
    }
}