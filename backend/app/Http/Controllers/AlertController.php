<?php

namespace App\Http\Controllers;

use App\Services\AlertService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;

/**
 * AlertController
 * 
 * Thin pass-through controller for alert operations.
 * All business logic in AlertService.
 */
class AlertController extends Controller
{
    private AlertService $alertService;
    private SessionService $sessionService;

    public function __construct(AlertService $alertService, SessionService $sessionService)
    {
        $this->alertService = $alertService;
        $this->sessionService = $sessionService;
    }

    /**
     * Get all active alerts
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $alerts = $this->alertService->getActiveAlerts();
            $count = $this->alertService->getAlertCountBySeverity();

            return response()->json([
                'success' => true,
                'alerts' => $alerts,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch alerts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get alert count only
     * 
     * @return JsonResponse
     */
    public function count(): JsonResponse
    {
        try {
            $count = $this->alertService->getAlertCountBySeverity();

            return response()->json($count);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch alert count',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dismiss an alert
     * 
     * @param int $alertId
     * @return JsonResponse
     */
    public function dismiss(int $alertId): JsonResponse
    {
        try {
            $userId = $this->sessionService->getCurrentUserId();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $success = $this->alertService->dismissAlert($alertId, $userId);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Alert dismissed successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Alert not found',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss alert',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
