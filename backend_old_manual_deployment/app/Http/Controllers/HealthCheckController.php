<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * HealthCheckController
 * 
 * Provides system health status for monitoring and deployment validation.
 * 
 * Endpoint: GET /health
 * 
 * Health checks:
 * - Application status
 * - Database connectivity
 * - Cache functionality
 * - File system writability
 * - Required tables existence
 * 
 * Returns:
 * - 200 OK if all systems healthy
 * - 503 Service Unavailable if any critical system failing
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Phase 4
 */
class HealthCheckController extends Controller
{
    /**
     * Check system health status
     * 
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $enabled = env('HEALTH_CHECK_ENABLED', true);
        
        if (!$enabled) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Health check is disabled',
            ], 200);
        }

        $checks = [
            'application' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');

        $response = [
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'checks' => $checks,
        ];

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json($response, $statusCode);
    }

    /**
     * Check application basic status
     * 
     * @return array
     */
    private function checkApplication(): array
    {
        try {
            return [
                'status' => 'healthy',
                'version' => 'PHP ' . phpversion(),
                'laravel' => app()->version(),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check database connectivity and required tables
     * 
     * @return array
     */
    private function checkDatabase(): array
    {
        try {
            // Test connection
            DB::connection()->getPdo();
            
            // Check required tables exist
            $requiredTables = [
                'users', 'roles', 'permissions', 'projects', 'tasks', 
                'milestones', 'expenses', 'opportunities', 'accounts', 
                'cash_transactions', 'alerts', 'audit_logs'
            ];
            
            $missingTables = [];
            foreach ($requiredTables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            if (!empty($missingTables)) {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Missing tables: ' . implode(', ', $missingTables),
                ];
            }

            // Get database info
            $connection = DB::connection();
            $databaseName = $connection->getDatabaseName();

            return [
                'status' => 'healthy',
                'connection' => DB::getDefaultConnection(),
                'database' => $databaseName,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache functionality
     * 
     * @return array
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);
            
            // Test write
            Cache::put($testKey, $testValue, 10);
            
            // Test read
            $retrieved = Cache::get($testKey);
            
            // Test delete
            Cache::forget($testKey);
            
            if ($retrieved !== $testValue) {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Cache read/write mismatch',
                ];
            }

            return [
                'status' => 'healthy',
                'driver' => config('cache.default'),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage writability
     * 
     * @return array
     */
    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path('logs');
            
            if (!is_writable($storagePath)) {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Storage path not writable: ' . $storagePath,
                ];
            }

            // Check disk space (warn if less than 100MB free)
            $freeSpace = disk_free_space($storagePath);
            $freeSpaceMB = round($freeSpace / 1024 / 1024, 2);
            
            $status = $freeSpaceMB > 100 ? 'healthy' : 'unhealthy';
            $warning = $freeSpaceMB <= 100 ? 'Low disk space' : null;

            return [
                'status' => $status,
                'writable' => true,
                'free_space_mb' => $freeSpaceMB,
                'warning' => $warning,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
