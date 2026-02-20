<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyLogsController extends Controller
{
    /**
     * Get company webhook logs (incoming webhooks from PalmPay)
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
                    'webhook_logs' => [
                        'data' => [],
                        'total' => 0,
                        'per_page' => $request->limit ?? 50,
                        'current_page' => 1
                    ]
                ]);
            }

            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'success',
                    'webhook_logs' => [
                        'data' => [],
                        'total' => 0,
                        'per_page' => $request->limit ?? 50,
                        'current_page' => 1
                    ]
                ]);
            }

            // Check if user is admin
            $isAdmin = strtoupper($user->type) === 'ADMIN';

            if ($isAdmin) {
                // Admin can see all incoming PalmPay webhooks with company information
                $logs = DB::table('palmpay_webhooks')
                    ->leftJoin('companies', 'palmpay_webhooks.company_id', '=', 'companies.id')
                    ->leftJoin('users', 'companies.user_id', '=', 'users.id')
                    ->select(
                        'palmpay_webhooks.*',
                        'users.name as company_name',
                        'palmpay_webhooks.webhook_url',
                        'palmpay_webhooks.http_status',
                        'palmpay_webhooks.status',
                        'palmpay_webhooks.event_type',
                        'palmpay_webhooks.created_at as sent_at'
                    )
                    ->orderBy('palmpay_webhooks.created_at', 'desc')
                    ->paginate($request->limit ?? 50);

                return response()->json([
                    'status' => 'success',
                    'webhook_logs' => $logs
                ]);
            }

            // Regular company user - show only their webhooks
            $companyId = $user->active_company_id ?? null;

            if (!$companyId) {
                return response()->json([
                    'status' => 'success',
                    'webhook_logs' => [
                        'data' => [],
                        'total' => 0,
                        'per_page' => $request->limit ?? 50,
                        'current_page' => 1
                    ]
                ]);
            }

            // Get incoming PalmPay webhooks for this company
            $logs = DB::table('palmpay_webhooks')
                ->leftJoin('transactions', 'palmpay_webhooks.transaction_id', '=', 'transactions.id')
                ->where('transactions.company_id', $companyId)
                ->select(
                    'palmpay_webhooks.*',
                    'transactions.reference as transaction_ref',
                    'transactions.amount as transaction_amount',
                    'palmpay_webhooks.created_at as sent_at'
                )
                ->orderBy('palmpay_webhooks.created_at', 'desc')
                ->paginate($request->limit ?? 50);

            return response()->json([
                'status' => 'success',
                'webhook_logs' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'webhook_logs' => [
                    'data' => [],
                    'total' => 0,
                    'per_page' => $request->limit ?? 50,
                    'current_page' => 1
                ],
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
                        'companies.name as company_name'
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
