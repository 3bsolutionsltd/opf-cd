<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SessionService;
use App\Services\PermissionService;

class CheckPermission
{
    protected $sessionService;
    protected $permissionService;

    public function __construct(SessionService $sessionService, PermissionService $permissionService)
    {
        $this->sessionService = $sessionService;
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * Check if user has required permission to access route.
     * Middleware parameters: resource and action (e.g., 'projects', 'view')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        if (!$this->sessionService->isAuthenticated()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }
            return redirect('/login');
        }

        $userId = $this->sessionService->getCurrentUserId();
        $hasPermission = $this->permissionService->hasPermission($userId, $resource, $action);

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission denied'
                ], 403);
            }
            abort(403, 'Permission denied');
        }

        return $next($request);
    }
}

