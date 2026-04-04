<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\GlobalKycPool;
use App\Models\VirtualAccount;
use App\Services\GlobalKycService;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KycPoolController extends Controller
{
    public function __construct(
        private GlobalKycService $kycService,
        private VirtualAccountService $vaService
    ) {}

    /**
     * GET /api/admin/kyc-pool/overview
     * Full dashboard overview
     */
    public function overview()
    {
        // Pool stats
        $pool = GlobalKycPool::all();
        $poolStats = [
            'total'       => $pool->count(),
            'active'      => $pool->where('is_active', true)->count(),
            'exhausted'   => $pool->filter(fn($p) => $p->max_usage && $p->usage_count >= $p->max_usage)->count(),
            'blacklisted' => $pool->filter(fn($p) => $p->isBlacklisted())->count(),
            'available'   => GlobalKycPool::available()->count(),
            'total_nin'   => $pool->where('kyc_type', 'nin')->count(),
            'total_bvn'   => $pool->where('kyc_type', 'bvn')->count(),
        ];

        // Company KYC health
        $companies = Company::whereNotNull('director_nin')
            ->orWhereNotNull('director_bvn')
            ->get(['id', 'name', 'director_nin', 'director_bvn', 'bvn', 'nin', 'status']);

        $companyHealth = $companies->map(function ($c) {
            $vaCount = VirtualAccount::where('company_id', $c->id)
                ->whereNotNull('palmpay_account_number')
                ->count();
            $maxLimit = 130;
            $pct = $maxLimit > 0 ? round(($vaCount / $maxLimit) * 100) : 0;
            $status = $pct >= 100 ? 'critical' : ($pct >= 80 ? 'warning' : 'healthy');

            return [
                'id'           => $c->id,
                'name'         => $c->name,
                'director_nin' => $c->director_nin ? substr($c->director_nin, 0, 5) . '***' : null,
                'director_bvn' => $c->director_bvn ? substr($c->director_bvn, 0, 5) . '***' : null,
                'va_count'     => $vaCount,
                'max_limit'    => $maxLimit,
                'usage_pct'    => $pct,
                'status'       => $status,
            ];
        });

        // Missing VAs
        $missingVas = $this->getMissingVas();

        return response()->json([
            'status'         => 'success',
            'pool_stats'     => $poolStats,
            'company_health' => $companyHealth,
            'missing_vas'    => $missingVas,
        ]);
    }

    /**
     * GET /api/admin/kyc-pool/entries
     * List all pool entries
     */
    public function entries()
    {
        $entries = GlobalKycPool::orderBy('kyc_type')->orderBy('usage_count')->get()->map(function ($p) {
            return [
                'id'             => $p->id,
                'kyc_type'       => $p->kyc_type,
                'kyc_number'     => substr($p->kyc_number, 0, 5) . '***',
                'is_active'      => $p->is_active,
                'usage_count'    => $p->usage_count,
                'success_count'  => $p->success_count,
                'failure_count'  => $p->failure_count,
                'max_usage'      => $p->max_usage,
                'is_blacklisted' => $p->isBlacklisted(),
                'blacklisted_until' => $p->blacklisted_until,
                'last_used_at'   => $p->last_used_at,
                'notes'          => $p->notes,
                'status'         => $p->isBlacklisted() ? 'blacklisted'
                    : (!$p->is_active ? 'inactive'
                    : ($p->max_usage && $p->usage_count >= $p->max_usage ? 'exhausted' : 'active')),
            ];
        });

        return response()->json(['status' => 'success', 'data' => $entries]);
    }

    /**
     * POST /api/admin/kyc-pool/add
     * Add new BVN/NIN entries to pool
     */
    public function add(Request $request)
    {
        $request->validate([
            'entries'           => 'required|array|min:1',
            'entries.*.type'    => 'required|in:bvn,nin',
            'entries.*.number'  => 'required|string|min:10|max:20',
            'max_usage'         => 'sometimes|integer|min:10|max:500',
        ]);

        $added = 0; $skipped = 0;
        $maxUsage = $request->max_usage ?? 130;

        foreach ($request->entries as $entry) {
            $exists = GlobalKycPool::where('kyc_number', $entry['number'])->exists();
            if ($exists) { $skipped++; continue; }

            GlobalKycPool::create([
                'kyc_type'      => $entry['type'],
                'kyc_number'    => $entry['number'],
                'is_active'     => true,
                'usage_count'   => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'max_usage'     => $maxUsage,
            ]);
            $added++;
        }

        return response()->json([
            'status'  => 'success',
            'message' => "$added added, $skipped skipped (duplicates)",
            'added'   => $added,
            'skipped' => $skipped,
        ]);
    }

    /**
     * DELETE /api/admin/kyc-pool/{id}
     * Deactivate a pool entry
     */
    public function deactivate($id)
    {
        $entry = GlobalKycPool::findOrFail($id);
        $entry->update(['is_active' => false]);
        return response()->json(['status' => 'success', 'message' => 'Entry deactivated']);
    }

    /**
     * POST /api/admin/kyc-pool/{id}/set-max
     * Update max_usage for an entry
     */
    public function setMax(Request $request, $id)
    {
        $request->validate(['max_usage' => 'required|integer|min:1']);
        GlobalKycPool::findOrFail($id)->update(['max_usage' => $request->max_usage]);
        return response()->json(['status' => 'success', 'message' => 'Max usage updated']);
    }

    /**
     * POST /api/admin/kyc-pool/company/{companyId}/assign-fresh
     * Assign a fresh NIN from pool to a company
     */
    public function assignFresh(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        $fresh = GlobalKycPool::available()->where('kyc_type', 'nin')->leastUsedFirst()->first();

        if (!$fresh) {
            return response()->json(['status' => 'error', 'message' => 'No available NIN in pool'], 400);
        }

        $company->update([
            'director_nin'         => $fresh->kyc_number,
            'kyc_method_blacklist' => null,
        ]);

        Log::info('Admin assigned fresh NIN to company', [
            'company_id' => $companyId,
            'kyc_id'     => $fresh->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Fresh NIN assigned to ' . $company->name,
        ]);
    }

    /**
     * POST /api/admin/kyc-pool/regenerate-missing
     * Regenerate missing VAs for a company or all companies
     */
    public function regenerateMissing(Request $request)
    {
        $companyId = $request->company_id ?? null;
        $missing   = $this->getMissingVas($companyId);

        if (empty($missing)) {
            return response()->json(['status' => 'success', 'message' => 'No missing VAs found', 'created' => 0]);
        }

        // Reset circuit breaker
        \Illuminate\Support\Facades\Cache::forget('palmpay_circuit_breaker');
        \Illuminate\Support\Facades\Cache::forget('palmpay_circuit_breaker_time');
        \Illuminate\Support\Facades\Cache::forget('palmpay_failure_count');

        $created = 0; $failed = 0; $errors = [];

        foreach ($missing as $m) {
            try {
                $customer = CompanyUser::find($m['customer_id']);
                $this->vaService->createVirtualAccount(
                    $m['company_id'],
                    $m['uuid'],
                    [
                        'first_name' => $customer->first_name,
                        'last_name'  => $customer->last_name,
                        'email'      => $customer->email,
                        'phone'      => $customer->phone,
                    ],
                    '100033',
                    $m['customer_id']
                );
                $created++;
                sleep(2);
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $m['customer_name'] . ': ' . $e->getMessage();
                \Illuminate\Support\Facades\Cache::forget('palmpay_circuit_breaker');
                \Illuminate\Support\Facades\Cache::forget('palmpay_failure_count');
            }
        }

        return response()->json([
            'status'  => 'success',
            'created' => $created,
            'failed'  => $failed,
            'errors'  => array_slice($errors, 0, 10),
        ]);
    }

    private function getMissingVas(?int $companyId = null): array
    {
        $query = CompanyUser::query();
        if ($companyId) $query->where('company_id', $companyId);

        $customers = $query->get();
        $missing = [];

        foreach ($customers as $c) {
            $va = VirtualAccount::where('company_user_id', $c->id)->whereNull('deleted_at')->first();
            if (!$va) {
                $company = Company::find($c->company_id);
                $missing[] = [
                    'customer_id'   => $c->id,
                    'customer_name' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                    'company_id'    => $c->company_id,
                    'company_name'  => $company->name ?? 'Unknown',
                    'uuid'          => $c->uuid,
                    'email'         => $c->email,
                ];
            }
        }

        return $missing;
    }
}
