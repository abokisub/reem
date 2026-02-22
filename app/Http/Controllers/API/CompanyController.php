<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\CompanyKycHistory;
use App\Models\CompanyWebhookLog;

class CompanyController extends Controller
{
    /**
     * Get Webhook Events
     */
    public function getWebhookEvents(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        $query = CompanyWebhookLog::where('company_id', $user->company->id);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('event_type', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'All' && !empty($request->status)) {
            // Map frontend filter values to database status values
            $statusMap = [
                'sent' => 'delivery_success',
                'failed' => 'delivery_failed',
            ];
            
            $filterStatus = strtolower($request->status);
            $dbStatus = $statusMap[$filterStatus] ?? $filterStatus;
            
            $query->where('status', $dbStatus);
        }

        $events = $query->orderBy('created_at', 'desc')->paginate($request->limit ?? 10);

        $total_sent = CompanyWebhookLog::where('company_id', $user->company->id)
            ->where('status', 'delivery_success')
            ->count();
        $total_failed = CompanyWebhookLog::where('company_id', $user->company->id)
            ->whereIn('status', ['delivery_failed', 'failed'])
            ->count();

        return response()->json([
            'status' => 'success',
            'webhook_events' => $events,
            'total_sent' => $total_sent,
            'total_failed' => $total_failed
        ]);
    }

    /**
     * Get the authenticated user's company credentials.
     */
    public function getCredentials(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'No company associated with this account. Please complete your business activation first.'
            ], 404);
        }

        // Check if KYC is verified, approved, or partial
        if (!in_array($company->kyc_status, ['verified', 'approved', 'partial'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your business KYC is not yet active. Credentials are not available until your business is activated.'
            ], 403);
        }

        // Check if credentials need to be generated - use raw DB to avoid decryption issues
        $updates = [];
        
        // Use raw DB query to check for NULL values without triggering decryption
        $rawCompany = DB::table('companies')->where('id', $company->id)->first();
        
        if (!$rawCompany->api_public_key) {
            $updates['api_public_key'] = bin2hex(random_bytes(20));
        }
        
        if (!$rawCompany->api_secret_key) {
            $updates['api_secret_key'] = bin2hex(random_bytes(60));
        }
        
        if (!$rawCompany->test_public_key) {
            $updates['test_public_key'] = bin2hex(random_bytes(20));
        }
        
        if (!$rawCompany->test_secret_key) {
            $updates['test_secret_key'] = bin2hex(random_bytes(60));
        }
        
        if (!$rawCompany->business_id) {
            $updates['business_id'] = bin2hex(random_bytes(20));
        }
        
        if (!$rawCompany->webhook_secret) {
            $updates['webhook_secret'] = 'whsec_' . bin2hex(random_bytes(32));
        }
        
        if (!$rawCompany->test_webhook_secret) {
            $updates['test_webhook_secret'] = 'whsec_test_' . bin2hex(random_bytes(32));
        }

        if (!empty($updates)) {
            DB::table('companies')->where('id', $company->id)->update($updates);
            // Reload only the updated fields without triggering full model refresh
            $rawCompany = DB::table('companies')->where('id', $company->id)->first();
        }

        // Try to decrypt webhook secrets, regenerate if fails
        $webhookSecret = null;
        $testWebhookSecret = null;
        
        try {
            // Manually decrypt to avoid serialization issues
            $encryptedWebhookSecret = DB::table('companies')
                ->where('id', $company->id)
                ->value('webhook_secret');
            
            $encryptedTestWebhookSecret = DB::table('companies')
                ->where('id', $company->id)
                ->value('test_webhook_secret');
            
            if ($encryptedWebhookSecret) {
                $webhookSecret = decrypt($encryptedWebhookSecret);
            }
            
            if ($encryptedTestWebhookSecret) {
                $testWebhookSecret = decrypt($encryptedTestWebhookSecret);
            }
        } catch (\Exception $e) {
            // If decryption fails, regenerate the secrets
            \Log::error('Webhook secret decryption failed, regenerating', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            $webhookSecret = 'whsec_' . bin2hex(random_bytes(32));
            $testWebhookSecret = 'whsec_test_' . bin2hex(random_bytes(32));
            
            DB::table('companies')->where('id', $company->id)->update([
                'webhook_secret' => encrypt($webhookSecret),
                'test_webhook_secret' => encrypt($testWebhookSecret),
            ]);
        }
        
        // If still null, generate new ones
        if (!$webhookSecret) {
            $webhookSecret = 'whsec_' . bin2hex(random_bytes(32));
            DB::table('companies')->where('id', $company->id)->update([
                'webhook_secret' => encrypt($webhookSecret),
            ]);
        }
        
        if (!$testWebhookSecret) {
            $testWebhookSecret = 'whsec_test_' . bin2hex(random_bytes(32));
            DB::table('companies')->where('id', $company->id)->update([
                'test_webhook_secret' => encrypt($testWebhookSecret),
            ]);
        }

        // Return credentials (secret keys are hidden by model)
        return response()->json([
            'status' => 'success',
            'data' => [
                'business_id' => !empty($updates) ? $rawCompany->business_id : $company->business_id,
                'api_key' => !empty($updates) ? $rawCompany->api_public_key : $company->api_public_key,
                'secret_key' => !empty($updates) ? $rawCompany->api_secret_key : $company->api_secret_key,
                'test_api_key' => !empty($updates) ? $rawCompany->test_public_key : $company->test_public_key,
                'test_secret_key' => !empty($updates) ? $rawCompany->test_secret_key : $company->test_secret_key,
                'public_key' => !empty($updates) ? $rawCompany->api_public_key : $company->api_public_key,
                'webhook_url' => $company->webhook_url,
                'webhook_secret' => $webhookSecret,
                'test_webhook_url' => $company->test_webhook_url,
                'test_webhook_secret' => $testWebhookSecret,
                'is_active' => $company->is_active,
            ]
        ]);
    }

    /**
     * Regenerate API credentials.
     */
    public function regenerateCredentials(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        $company = $user->company;

        // Check if KYC is verified, approved, or partial
        if (!in_array($company->kyc_status, ['verified', 'approved', 'partial'])) {
            return response()->json(['status' => 'error', 'message' => 'Your business KYC is not yet active'], 403);
        }

        // Generate new credentials in strictly hex format
        $apiKey = bin2hex(random_bytes(20));
        $secretKey = bin2hex(random_bytes(60));

        $company->update([
            'api_key' => $apiKey,
            'secret_key' => $secretKey, // Stored as plain text for manual integration support
        ]);

        // Log history
        CompanyKycHistory::logAction(
            $company->id,
            'all',
            'credentials_regenerated',
            $user->id,
            'API credentials regenerated by user'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Credentials regenerated successfully',
            'data' => [
                'business_id' => $company->business_id, // Return business_id as well
                'api_key' => $apiKey,
                'secret_key' => $secretKey
            ]
        ]);
    }

    /**
     * Update Webhook Settings
     */
    public function updateWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'nullable|url',
        ]);

        $user = $request->user();
        if (!$user || !$user->company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        $company = $user->company;
        $company->update([
            'webhook_url' => $request->webhook_url
        ]);

        // Log history
        CompanyKycHistory::logAction(
            $company->id,
            'all',
            'webhook_updated',
            $user->id,
            'Webhook URL updated by user'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook URL updated'
        ]);
    }

    /**
     * Update API Status (Lock/Unlock)
     */
    public function updateApiStatus(Request $request)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user = $request->user();
        if (!$user || !$user->company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        $company = $user->company;
        $company->update([
            'is_active' => $request->is_active,
        ]);

        // Log history
        CompanyKycHistory::logAction(
            $company->id,
            'all',
            'api_status_updated',
            $user->id,
            'API status updated to ' . ($request->is_active ? 'Unlocked' : 'Locked') . ' by user'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'API status updated to ' . ($request->is_active ? 'Unlocked' : 'Locked'),
            'is_active' => $company->is_active
        ]);
    }

    /**
     * Update Company Settings (Webhook and API Status)
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'webhook_url' => 'nullable|url',
            'is_active' => 'required|boolean',
        ]);

        $user = $request->user();
        if (!$user || !$user->company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        $company = $user->company;
        $company->update([
            'webhook_url' => $request->webhook_url,
            'is_active' => $request->is_active,
        ]);

        // Log history for webhook if changed
        if ($company->wasChanged('webhook_url')) {
            CompanyKycHistory::logAction(
                $company->id,
                'all',
                'webhook_updated',
                $user->id,
                'Webhook URL updated to ' . ($request->webhook_url ?: 'empty') . ' by user'
            );
        }

        // Log history for API status if changed
        if ($company->wasChanged('is_active')) {
            CompanyKycHistory::logAction(
                $company->id,
                'all',
                'api_status_updated',
                $user->id,
                'API status updated to ' . ($request->is_active ? 'Unlocked' : 'Locked') . ' by user'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully',
            'data' => [
                'webhook_url' => $company->webhook_url,
                'is_active' => $company->is_active
            ]
        ]);
    }
}
