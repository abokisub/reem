<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyLogsController extends Controller
{
    /**
     * Get company webhook logs
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWebhooks(Request $request)
    {
        try {
            // Get user ID - the id parameter is already the user ID
            $userId = $request->id;
            
            if (!$userId) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $companyId = $user->active_company_id ?? null;

            if (!$companyId) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            // Get webhook logs for this company from webhook_logs table
            $logs = DB::table('webhook_logs')
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $logs,
                'count' => $logs->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get company API request logs
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApiRequests(Request $request)
    {
        try {
            // Get user ID - the id parameter is already the user ID
            $userId = $request->id;
            
            if (!$userId) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $companyId = $user->active_company_id ?? null;

            // Get API request logs
            // Note: Most logs have NULL company_id, so we return all logs for now
            // TODO: Update API logging middleware to capture company_id
            $query = DB::table('api_request_logs')
                ->orderBy('created_at', 'desc')
                ->limit(100);
            
            // If company_id exists, try to filter by it, but also include NULL records
            if ($companyId) {
                $query->where(function($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->orWhereNull('company_id');
                });
            }
            
            $logs = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $logs,
                'count' => $logs->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get company audit logs
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuditLogs(Request $request)
    {
        try {
            // Get user ID - the id parameter is already the user ID
            $userId = $request->id;
            
            if (!$userId) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $companyId = $user->active_company_id ?? null;

            if (!$companyId) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            // Get audit logs for this company
            $logs = DB::table('audit_logs')
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $logs,
                'count' => $logs->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => $e->getMessage()
            ]);
        }
    }
}
