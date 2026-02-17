<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * AuditController
 * 
 * Thin pass-through controller for audit log queries.
 * 
 * Rules:
 * - NO business logic
 * - NO calculations
 * - Only calls AuditService and returns results
 * - Permissions enforced by middleware
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 4
 */
class AuditController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get audit logs with optional filters
     * 
     * Query params:
     * - entity_type: Filter by entity type
     * - entity_id: Filter by entity ID
     * - action: Filter by action (create/update/delete)
     * - user_id: Filter by user
     * - from_date: Start date (YYYY-MM-DD)
     * - to_date: End date (YYYY-MM-DD)
     * - limit: Max records (default 100)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'entity_type',
            'entity_id',
            'action',
            'user_id',
            'from_date',
            'to_date'
        ]);

        $limit = (int) $request->input('limit', 100);
        $limit = min($limit, 500); // Cap at 500

        $logs = $this->auditService->getAuditLogs($filters, $limit);

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs),
        ]);
    }

    /**
     * Get audit logs for specific entity
     * 
     * @param string $entityType
     * @param int $entityId
     * @param Request $request
     * @return JsonResponse
     */
    public function entity(string $entityType, int $entityId, Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $limit = min($limit, 200); // Cap at 200

        $logs = $this->auditService->getEntityAuditLog($entityType, $entityId, $limit);

        return response()->json([
            'success' => true,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'logs' => $logs,
            'count' => count($logs),
        ]);
    }

    /**
     * Get audit logs for specific user
     * 
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function user(int $userId, Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $limit = min($limit, 200); // Cap at 200

        $logs = $this->auditService->getUserAuditLog($userId, $limit);

        return response()->json([
            'success' => true,
            'user_id' => $userId,
            'logs' => $logs,
            'count' => count($logs),
        ]);
    }

    /**
     * Get audit statistics
     * 
     * Query params same as index()
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $filters = $request->only([
            'entity_type',
            'user_id',
            'from_date',
            'to_date'
        ]);

        $stats = $this->auditService->getAuditStats($filters);

        return response()->json($stats);
    }
}
