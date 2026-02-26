<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * User Management Service
 * 
 * Single responsibility: Handle read operations for users.
 * No decision logic, no business rules - just facts.
 * 
 * Returns facts only - NO business logic, NO frontend concerns.
 */
class UserManagementService
{
    /**
     * Get all active users ordered by email.
     * 
     * @return array Array of user records
     */
    public function getUsers(): array
    {
        $users = DB::table('users')
            ->select('id', 'email', 'role', 'is_active', 'last_login_at', 'created_at')
            ->where('is_active', true)
            ->orderBy('email', 'asc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ];
            })
            ->toArray();

        return $users;
    }

    /**
     * Get user details by ID.
     * 
     * @param int $userId
     * @return array|null User record or null if not found
     */
    public function getUserDetails(int $userId): ?array
    {
        $user = DB::table('users')
            ->select('id', 'email', 'role', 'is_active', 'last_login_at', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
