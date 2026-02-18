<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Refund Controller
 * Handles manual refund operations
 */
class RefundController extends Controller
{
    protected $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * List all refunds with filters
     * GET /api/admin/refunds
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|exists:companies,id',
            'status' => 'nullable|in:pending,processing,completed,failed,cancelled',
            'refund_type' => 'nullable|in:auto,manual',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Refund::with(['company', 'transaction', 'initiatedBy']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('refund_type')) {
            $query->where('refund_type', $request->refund_type);
        }

        $perPage = $request->get('per_page', 20);
        $refunds = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $refunds,
        ]);
    }

    /**
     * Get refund details
     * GET /api/admin/refunds/{id}
     */
    public function show($id)
    {
        $refund = Refund::with(['company', 'transaction', 'initiatedBy'])->find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $refund,
        ]);
    }

    /**
     * Process manual refund
     * POST /api/admin/refunds
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|exists:transactions,reference',
            'reason' => 'required|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = Transaction::where('reference', $request->transaction_id)->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            $result = $this->refundService->processManualRefund(
                $transaction,
                $request->reason,
                Auth::id(),
                $request->admin_notes
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['refund'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get refund statistics
     * GET /api/admin/refunds/stats
     */
    public function stats(Request $request)
    {
        $query = Refund::query();

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $stats = [
            'total_refunds' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'by_status' => [
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'processing' => (clone $query)->where('status', 'processing')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'failed' => (clone $query)->where('status', 'failed')->count(),
            ],
            'by_type' => [
                'auto' => (clone $query)->where('refund_type', 'auto')->count(),
                'manual' => (clone $query)->where('refund_type', 'manual')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
