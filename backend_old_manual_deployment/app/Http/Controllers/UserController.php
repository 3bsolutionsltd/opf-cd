<?php

namespace App\Http\Controllers;

use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;

/**
 * User Controller
 * 
 * Thin pass-through controller - NO transformations, NO calculations.
 * Calls ONE service (UserManagementService).
 */
class UserController extends Controller
{
    /**
     * Injected service
     */
    private UserManagementService $userService;

    /**
     * Constructor - inject ONE service only
     */
    public function __construct(UserManagementService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * API: Get all active users.
     * Used for populating owner dropdowns in forms.
     */
    public function apiIndex(): JsonResponse
    {
        $users = $this->userService->getUsers();
        return response()->json($users, 200);
    }

    /**
     * API: Get user details by ID.
     */
    public function apiShow(int $userId): JsonResponse
    {
        $user = $this->userService->getUserDetails($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json($user, 200);
    }
}
