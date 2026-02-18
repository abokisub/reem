<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Log a sensitive financial or administrative action
     */
    public static function log($action, $model, $oldValues = null, $newValues = null)
    {
        try {
            DB::table('audit_logs')->insert([
                'company_id' => request()->attributes->get('company_id') ?? ($model->company_id ?? null),
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('🛡️ Audit Log Failed: ' . $e->getMessage());
        }
    }
}
