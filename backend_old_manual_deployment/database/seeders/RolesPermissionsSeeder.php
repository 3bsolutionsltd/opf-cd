<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create or update roles
        DB::table('roles')->updateOrInsert(['name' => 'admin'], ['description' => 'Administrator with full access', 'updated_at' => now()]);
        DB::table('roles')->updateOrInsert(['name' => 'project_manager'], ['description' => 'Project Manager', 'updated_at' => now()]);
        DB::table('roles')->updateOrInsert(['name' => 'finance'], ['description' => 'Finance Manager', 'updated_at' => now()]);
        DB::table('roles')->updateOrInsert(['name' => 'sales'], ['description' => 'Sales Manager', 'updated_at' => now()]);
        DB::table('roles')->updateOrInsert(['name' => 'viewer'], ['description' => 'Read-only viewer', 'updated_at' => now()]);

        // Get role IDs
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $pmRoleId = DB::table('roles')->where('name', 'project_manager')->value('id');
        $financeRoleId = DB::table('roles')->where('name', 'finance')->value('id');
        $salesRoleId = DB::table('roles')->where('name', 'sales')->value('id');
        $viewerRoleId = DB::table('roles')->where('name', 'viewer')->value('id');

        // Clear existing permissions and user_roles
        DB::table('user_roles')->where('user_id', 2)->delete();
        DB::table('permissions')->whereIn('role_id', [$adminRoleId, $pmRoleId, $financeRoleId, $salesRoleId, $viewerRoleId])->delete();

        // Define permissions (resource, action, role_id)
        $permissions = [
            // Dashboard permissions - all roles can view
            ['resource' => 'dashboards', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'dashboards', 'action' => 'view', 'role_id' => $pmRoleId],
            ['resource' => 'dashboards', 'action' => 'view', 'role_id' => $financeRoleId],
            ['resource' => 'dashboards', 'action' => 'view', 'role_id' => $salesRoleId],
            ['resource' => 'dashboards', 'action' => 'view', 'role_id' => $viewerRoleId],

            // Projects - admin and PM
            ['resource' => 'projects', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'projects', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'projects', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'projects', 'action' => 'delete', 'role_id' => $adminRoleId],
            ['resource' => 'projects', 'action' => 'view', 'role_id' => $pmRoleId],
            ['resource' => 'projects', 'action' => 'create', 'role_id' => $pmRoleId],
            ['resource' => 'projects', 'action' => 'edit', 'role_id' => $pmRoleId],

            // Tasks - admin and PM
            ['resource' => 'tasks', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'tasks', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'tasks', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'tasks', 'action' => 'delete', 'role_id' => $adminRoleId],
            ['resource' => 'tasks', 'action' => 'view', 'role_id' => $pmRoleId],
            ['resource' => 'tasks', 'action' => 'create', 'role_id' => $pmRoleId],
            ['resource' => 'tasks', 'action' => 'edit', 'role_id' => $pmRoleId],

            // Milestones - admin, PM, and finance
            ['resource' => 'milestones', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'milestones', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'milestones', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'milestones', 'action' => 'delete', 'role_id' => $adminRoleId],
            ['resource' => 'milestones', 'action' => 'view', 'role_id' => $pmRoleId],
            ['resource' => 'milestones', 'action' => 'view', 'role_id' => $financeRoleId],

            // Expenses - admin and finance
            ['resource' => 'expenses', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'expenses', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'expenses', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'expenses', 'action' => 'delete', 'role_id' => $adminRoleId],
            ['resource' => 'expenses', 'action' => 'view', 'role_id' => $financeRoleId],
            ['resource' => 'expenses', 'action' => 'create', 'role_id' => $financeRoleId],

            // Accounts - admin and finance
            ['resource' => 'accounts', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'accounts', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'accounts', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'accounts', 'action' => 'delete', 'role_id' => $adminRoleId],
            ['resource' => 'accounts', 'action' => 'view', 'role_id' => $financeRoleId],

            // Cash transactions - admin and finance
            ['resource' => 'cash_transactions', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'cash_transactions', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'cash_transactions', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'cash_transactions', 'action' => 'delete', 'role_id' => $adminRoleId],
            ['resource' => 'cash_transactions', 'action' => 'view', 'role_id' => $financeRoleId],
            ['resource' => 'cash_transactions', 'action' => 'create', 'role_id' => $financeRoleId],

            // Opportunities - admin and sales
            ['resource' => 'opportunities', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'opportunities', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'opportunities', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'opportunities', 'action' => 'delete', 'role_id' => $adminRoleId],
            ['resource' => 'opportunities', 'action' => 'view', 'role_id' => $salesRoleId],
            ['resource' => 'opportunities', 'action' => 'create', 'role_id' => $salesRoleId],
            ['resource' => 'opportunities', 'action' => 'edit', 'role_id' => $salesRoleId],

            // Users - admin only
            ['resource' => 'users', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'users', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'users', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'users', 'action' => 'delete', 'role_id' => $adminRoleId],

            // Roles - admin only
            ['resource' => 'roles', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'roles', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'roles', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'roles', 'action' => 'delete', 'role_id' => $adminRoleId],

            // Permissions - admin only
            ['resource' => 'permissions', 'action' => 'view', 'role_id' => $adminRoleId],
            ['resource' => 'permissions', 'action' => 'create', 'role_id' => $adminRoleId],
            ['resource' => 'permissions', 'action' => 'edit', 'role_id' => $adminRoleId],
            ['resource' => 'permissions', 'action' => 'delete', 'role_id' => $adminRoleId],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert($permission);
        }

        // Assign admin role to the admin user (id=2)
        DB::table('user_roles')->insert([
            'user_id' => 2,
            'role_id' => $adminRoleId,
        ]);

        echo "Roles, permissions, and user roles created successfully!\n";
        echo "Admin user (id=2) assigned to admin role.\n";
    }
}
