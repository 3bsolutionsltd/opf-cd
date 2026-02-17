<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * Get all permissions for a user.
     * 
     * Returns array of permissions based on user's assigned roles.
     * Each permission contains resource and action.
     * 
     * @param int $userId
     * @return array [{resource: string, action: string}]
     */
    public function getUserPermissions(int $userId): array
    {
        $permissions = DB::table('permissions')
            ->join('user_roles', 'permissions.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->select('permissions.resource', 'permissions.action')
            ->distinct()
            ->get();

        return $permissions->map(function ($permission) {
            return [
                'resource' => $permission->resource,
                'action' => $permission->action
            ];
        })->toArray();
    }

    /**
     * Check if user has specific permission.
     * 
     * Returns true if user has the permission, false otherwise.
     * Checks against user's role permissions.
     * 
     * @param int $userId
     * @param string $resource
     * @param string $action
     * @return bool
     */
    public function hasPermission(int $userId, string $resource, string $action): bool
    {
        $count = DB::table('permissions')
            ->join('user_roles', 'permissions.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('permissions.resource', $resource)
            ->where('permissions.action', $action)
            ->count();

        return $count > 0;
    }
}
