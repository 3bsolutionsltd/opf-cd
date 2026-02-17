<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * AuditService
 * 
 * Records and queries audit trail of all data modifications.
 * 
 * Rules:
 * - Single responsibility: log data changes, query audit history
 * - Returns facts only (log created: true/false, query results)
 * - Does NOT enforce permissions (that's middleware's job)
 * - Does NOT send notifications (that's a separate service)
 * 
 * Tracked Actions:
 * - create: New record inserted
 * - update: Existing record modified (stores before/after)
 * - delete: Record removed (stores final state)
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 4
 */
class AuditService
{
    /**
     * Log a CREATE action
     * 
     * Returns FACT ONLY - log ID or null on failure.
     * 
     * @param int $userId
     * @param string $entityType (e.g., 'projects', 'tasks')
     * @param int $entityId
     * @param array $data The created record data
     * @param Request|null $request For IP/user agent capture
     * @return int|null
     */
    public function logCreate(
        int $userId,
        string $entityType,
        int $entityId,
        array $data,
        ?Request $request = null
    ): ?int {
        return $this->createAuditLog(
            $userId,
            'create',
            $entityType,
            $entityId,
            ['after' => $data],
            $request
        );
    }

    /**
     * Log an UPDATE action
     * 
     * Returns FACT ONLY - log ID or null on failure.
     * 
     * @param int $userId
     * @param string $entityType
     * @param int $entityId
     * @param array $before Previous state
     * @param array $after New state
     * @param Request|null $request
     * @return int|null
     */
    public function logUpdate(
        int $userId,
        string $entityType,
        int $entityId,
        array $before,
        array $after,
        ?Request $request = null
    ): ?int {
        // Only log if there are actual changes
        $changes = $this->detectChanges($before, $after);
        if (empty($changes)) {
            return null;
        }

        return $this->createAuditLog(
            $userId,
            'update',
            $entityType,
            $entityId,
            [
                'before' => $before,
                'after' => $after,
                'changed_fields' => array_keys($changes)
            ],
            $request
        );
    }

    /**
     * Log a DELETE action
     * 
     * Returns FACT ONLY - log ID or null on failure.
     * 
     * @param int $userId
     * @param string $entityType
     * @param int $entityId
     * @param array $data The deleted record's final state
     * @param Request|null $request
     * @return int|null
     */
    public function logDelete(
        int $userId,
        string $entityType,
        int $entityId,
        array $data,
        ?Request $request = null
    ): ?int {
        return $this->createAuditLog(
            $userId,
            'delete',
            $entityType,
            $entityId,
            ['before' => $data],
            $request
        );
    }

    /**
     * Get audit logs for a specific entity
     * 
     * Returns FACT ONLY - array of audit log records.
     * 
     * @param string $entityType
     * @param int $entityId
     * @param int $limit
     * @return array
     */
    public function getEntityAuditLog(
        string $entityType,
        int $entityId,
        int $limit = 50
    ): array {
        $logs = DB::table('audit_logs')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'changes' => json_decode($log->changes, true),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
            ];
        })->toArray();
    }

    /**
     * Get audit logs by user
     * 
     * Returns FACT ONLY - array of audit log records.
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserAuditLog(int $userId, int $limit = 50): array
    {
        $logs = DB::table('audit_logs')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'changes' => json_decode($log->changes, true),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
            ];
        })->toArray();
    }

    /**
     * Get recent audit logs across all entities
     * 
     * Returns FACT ONLY - array of audit log records.
     * 
     * @param array $filters Optional filters (entity_type, action, user_id, from_date, to_date)
     * @param int $limit
     * @return array
     */
    public function getAuditLogs(array $filters = [], int $limit = 100): array
    {
        $query = DB::table('audit_logs')
            ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id');

        // Apply filters
        if (!empty($filters['entity_type'])) {
            $query->where('audit_logs.entity_type', $filters['entity_type']);
        }

        if (!empty($filters['entity_id'])) {
            $query->where('audit_logs.entity_id', $filters['entity_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('audit_logs.action', $filters['action']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('audit_logs.user_id', $filters['user_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('audit_logs.created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('audit_logs.created_at', '<=', $filters['to_date']);
        }

        $logs = $query
            ->orderBy('audit_logs.created_at', 'desc')
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user_name,
                'user_email' => $log->user_email,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'changes' => json_decode($log->changes, true),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
            ];
        })->toArray();
    }

    /**
     * Get audit statistics
     * 
     * Returns FACT ONLY - counts by action type.
     * 
     * @param array $filters Optional filters (entity_type, user_id, from_date, to_date)
     * @return array
     */
    public function getAuditStats(array $filters = []): array
    {
        $query = DB::table('audit_logs');

        // Apply filters
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $stats = $query
            ->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action')
            ->toArray();

        return [
            'total' => array_sum($stats),
            'creates' => $stats['create'] ?? 0,
            'updates' => $stats['update'] ?? 0,
            'deletes' => $stats['delete'] ?? 0,
        ];
    }

    /**
     * Internal: Create audit log record
     * 
     * @param int $userId
     * @param string $action
     * @param string $entityType
     * @param int $entityId
     * @param array $changes
     * @param Request|null $request
     * @return int|null
     */
    private function createAuditLog(
        int $userId,
        string $action,
        string $entityType,
        int $entityId,
        array $changes,
        ?Request $request = null
    ): ?int {
        try {
            return DB::table('audit_logs')->insertGetId([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'changes' => json_encode($changes),
                'ip_address' => $request ? $request->ip() : null,
                'user_agent' => $request ? $request->userAgent() : null,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            error_log("Audit log creation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Detect actual changes between before/after states
     * 
     * @param array $before
     * @param array $after
     * @return array Changed fields with before/after values
     */
    private function detectChanges(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $value) {
            // Skip timestamp fields that always change
            if (in_array($key, ['updated_at'])) {
                continue;
            }

            // Check if value changed
            if (!isset($before[$key]) || $before[$key] !== $value) {
                $changes[$key] = [
                    'before' => $before[$key] ?? null,
                    'after' => $value,
                ];
            }
        }

        return $changes;
    }
}
