<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SettlementQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettlementController extends Controller
{
    /**
     * Get settlement configuration
     */
    public function getConfig(Request $request)
    {
        $settings = DB::table('settings')->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'auto_settlement_enabled' => (bool) ($settings->auto_settlement_enabled ?? true),
                'settlement_delay_hours' => (int) ($settings->settlement_delay_hours ?? 24),
                'settlement_skip_weekends' => (bool) ($settings->settlement_skip_weekends ?? true),
                'settlement_skip_holidays' => (bool) ($settings->settlement_skip_holidays ?? true),
                'settlement_time' => $settings->settlement_time ?? '02:00:00',
                'settlement_minimum_amount' => (float) ($settings->settlement_minimum_amount ?? 100.00),
            ]
        ]);
    }

    /**
     * Update settlement configuration
     */
    public function updateConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auto_settlement_enabled' => 'required|boolean',
            'settlement_delay_hours' => 'required|integer|min:1|max:168', // Max 7 days
            'settlement_skip_weekends' => 'required|boolean',
            'settlement_skip_holidays' => 'required|boolean',
            'settlement_time' => 'required|date_format:H:i:s',
            'settlement_minimum_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::table('settings')->update([
            'auto_settlement_enabled' => $request->auto_settlement_enabled,
            'settlement_delay_hours' => $request->settlement_delay_hours,
            'settlement_skip_weekends' => $request->settlement_skip_weekends,
            'settlement_skip_holidays' => $request->settlement_skip_holidays,
            'settlement_time' => $request->settlement_time,
            'settlement_minimum_amount' => $request->settlement_minimum_amount,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Settlement configuration updated successfully'
        ]);
    }

    /**
     * Get company-specific settlement configuration
     */
    public function getCompanyConfig(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'custom_settlement_enabled' => (bool) $company->custom_settlement_enabled,
                'custom_settlement_delay_hours' => $company->custom_settlement_delay_hours,
                'custom_settlement_minimum' => $company->custom_settlement_minimum,
            ]
        ]);
    }

    /**
     * Update company-specific settlement configuration
     */
    public function updateCompanyConfig(Request $request, $companyId)
    {
        $validator = Validator::make($request->all(), [
            'custom_settlement_enabled' => 'required|boolean',
            'custom_settlement_delay_hours' => 'nullable|integer|min:1|max:168',
            'custom_settlement_minimum' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        $company = Company::findOrFail($companyId);
        
        $company->update([
            'custom_settlement_enabled' => $request->custom_settlement_enabled,
            'custom_settlement_delay_hours' => $request->custom_settlement_delay_hours,
            'custom_settlement_minimum' => $request->custom_settlement_minimum,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Company settlement configuration updated successfully'
        ]);
    }

    /**
     * Get pending settlements
     */
    public function getPendingSettlements(Request $request)
    {
        $query = DB::table('settlement_queue')
            ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
            ->join('transactions', 'settlement_queue.transaction_id', '=', 'transactions.id')
            ->select(
                'settlement_queue.*',
                'companies.name as company_name',
                'transactions.transaction_id',
                'transactions.reference'
            )
            ->where('settlement_queue.status', 'pending')
            ->orderBy('settlement_queue.scheduled_settlement_date');

        if ($request->has('company_id')) {
            $query->where('settlement_queue.company_id', $request->company_id);
        }

        $settlements = $query->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $settlements
        ]);
    }

    /**
     * Get settlement history
     */
    public function getSettlementHistory(Request $request)
    {
        $query = DB::table('settlement_queue')
            ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
            ->join('transactions', 'settlement_queue.transaction_id', '=', 'transactions.id')
            ->select(
                'settlement_queue.*',
                'companies.name as company_name',
                'transactions.transaction_id',
                'transactions.reference'
            )
            ->whereIn('settlement_queue.status', ['completed', 'failed'])
            ->orderBy('settlement_queue.actual_settlement_date', 'desc');

        if ($request->has('company_id')) {
            $query->where('settlement_queue.company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('settlement_queue.status', $request->status);
        }

        $settlements = $query->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $settlements
        ]);
    }

    /**
     * Get settlement statistics
     */
    public function getStatistics(Request $request)
    {
        $companyId = $request->get('company_id');

        $query = DB::table('settlement_queue');
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $stats = [
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'pending_amount' => (clone $query)->where('status', 'pending')->sum('amount'),
            'completed_today' => (clone $query)
                ->where('status', 'completed')
                ->whereDate('actual_settlement_date', today())
                ->count(),
            'completed_today_amount' => (clone $query)
                ->where('status', 'completed')
                ->whereDate('actual_settlement_date', today())
                ->sum('amount'),
            'failed_count' => (clone $query)->where('status', 'failed')->count(),
            'failed_amount' => (clone $query)->where('status', 'failed')->sum('amount'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
