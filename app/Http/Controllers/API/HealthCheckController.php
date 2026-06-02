<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Queue & System Health Monitoring Endpoint
     */
    public function index()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'components' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'queue' => $this->checkQueue(),
            ]
        ];

        $statusCode = (collect($health['components'])->contains('unhealthy')) ? 503 : 200;

        if ($statusCode === 503) {
            $health['status'] = 'unhealthy';
        }

        return response()->json($health, $statusCode);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->select('SELECT 1');
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkRedis()
    {
        try {
            Cache::store('redis')->get('ping');
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkQueue()
    {
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 100) {
            return 'warning'; // Threshold for alerts
        }
        return 'healthy';
    }
}
