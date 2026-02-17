<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Get user's roles.
     * 
     * Returns array of role names assigned to user.
     * 
     * @param int $userId
     * @return array [string]
     */
    public function getUserRoles(int $userId): array
    {
        $roles = DB::table('roles')
            ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->select('roles.name')
            ->get();

        return $roles->pluck('name')->toArray();
    }

    /**
     * Check if user has specific role.
     * 
     * Returns true if user has the role, false otherwise.
     * 
     * @param int $userId
     * @param string $roleName
     * @return bool
     */
    public function hasRole(int $userId, string $roleName): bool
    {
        $count = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.name', $roleName)
            ->count();

        return $count > 0;
    }
}
