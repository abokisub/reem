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

            // Check if user is admin
            $isAdmin = strtoupper($user->type) === 'ADMIN';

            if ($isAdmin) {
                // Admin can see all webhook logs with company information
                $logs = DB::table('webhook_logs')
                    ->leftJoin('companies', 'webhook_logs.company_id', '=', 'companies.id')
                    ->select(
                        'webhook_logs.*',
                        'companies.name as company_name',
                        'companies.business_name'
                    )
                    ->orderBy('webhook_logs.created_at', 'desc')
                    ->paginate($request->limit ?? 50);

                return response()->json([
                    'status' => 'success',
                    'webhook_logs' => $logs
                ]);
            }

            // Regular company user - show only their logs
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
                ->paginate($request->limit ?? 50);

            return response()->json([
                'status' => 'success',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'data' => [],
                'message' => $e->getMessage()
            ], 500);
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

            // Check if user is admin
            $isAdmin = strtoupper($user->type) === 'ADMIN';

            if ($isAdmin) {
                // Admin can see all API request logs with company information
                $logs = DB::table('api_request_logs')
                    ->leftJoin('companies', 'api_request_logs.company_id', '=', 'companies.id')
                    ->select(
                        'api_request_logs.*',
                        'companies.name as company_name',
                        'companies.business_name'
                    )
                    ->orderBy('api_request_logs.created_at', 'desc')
                    ->paginate($request->limit ?? 50);

                return response()->json([
                    'status' => 'success',
                    'api_logs' => $logs
                ]);
            }

            // Regular company user - show only their logs
            $companyId = $user->active_company_id ?? null;

            // Get API request logs
            $query = DB::table('api_request_logs')
                ->orderBy('created_at', 'desc');
            
            // If company_id exists, try to filter by it, but also include NULL records
            if ($companyId) {
                $query->where(function($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->orWhereNull('company_id');
                });
            }
            
            $logs = $query->paginate($request->limit ?? 50);

            return response()->json([
                'status' => 'success',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'data' => [],
                'message' => $e->getMessage()
            ], 500);
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
