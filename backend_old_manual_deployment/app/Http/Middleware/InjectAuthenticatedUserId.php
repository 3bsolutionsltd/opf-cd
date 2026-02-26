<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SessionService;

/**
 * InjectAuthenticatedUserId Middleware
 * 
 * Single responsibility: Extract authenticated user ID and inject into request.
 * 
 * This middleware runs after authentication and makes the user ID available
 * to controllers without requiring SessionService injection.
 * 
 * Controllers access it via: $request->get('authenticated_user_id')
 */
class InjectAuthenticatedUserId
{
    protected SessionService $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Handle an incoming request.
     *
     * Extract user ID from session and inject into request attributes.
     * This allows controllers to remain thin without injecting SessionService.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only inject user ID if authenticated
        if ($this->sessionService->isAuthenticated()) {
            $userId = $this->sessionService->getCurrentUserId();
            $request->attributes->set('authenticated_user_id', $userId);
        }

        return $next($request);
    }
}
