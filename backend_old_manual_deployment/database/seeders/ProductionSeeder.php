<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ProductionSeeder
 * 
 * Creates initial admin user and role structure for production deployment.
 * 
 * Usage:
 *   php artisan db:seed --class=ProductionSeeder
 * 
 * Creates:
 * - Admin role with full permissions
 * - Finance role with financial data permissions
 * - Project Manager role with project management permissions
 * - Viewer role with read-only permissions
 * - Initial admin user account
 * 
 * IMPORTANT: Change the admin password immediately after first login!
 */
class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create roles
        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Admin',
            'description' => 'Full system access',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $financeRoleId = DB::table('roles')->insertGetId([
            'name' => 'Finance',
            'description' => 'Financial data management',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pmRoleId = DB::table('roles')->insertGetId([
            'name' => 'Project Manager',
            'description' => 'Project management access',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $viewerRoleId = DB::table('roles')->insertGetId([
            'name' => 'Viewer',
            'description' => 'Read-only access',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 2: Create permissions for Admin role (full access)
        $resources = ['projects', 'tasks', 'milestones', 'expenses', 'opportunities', 
                      'accounts', 'dashboards', 'alerts', 'audit_logs', 'reports', 'users'];
        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                DB::table('permissions')->insert([
                    'role_id' => $adminRoleId,
                    'resource' => $resource,
                    'action' => $action,
                    'created_at' => now(),
                ]);
            }
        }

        // Step 3: Create permissions for Finance role
        $financeResources = ['expenses', 'accounts', 'dashboards', 'reports', 'alerts'];
        foreach ($financeResources as $resource) {
            foreach ($actions as $action) {
                DB::table('permissions')->insert([
                    'role_id' => $financeRoleId,
                    'resource' => $resource,
                    'action' => $action,
                    'created_at' => now(),
                ]);
            }
        }

        // Step 4: Create permissions for Project Manager role
        $pmResources = ['projects', 'tasks', 'milestones', 'dashboards', 'reports', 'alerts'];
        foreach ($pmResources as $resource) {
            foreach ($actions as $action) {
                DB::table('permissions')->insert([
                    'role_id' => $pmRoleId,
                    'resource' => $resource,
                    'action' => $action,
                    'created_at' => now(),
                ]);
            }
        }

        // Opportunities view for PM
        DB::table('permissions')->insert([
            'role_id' => $pmRoleId,
            'resource' => 'opportunities',
            'action' => 'view',
            'created_at' => now(),
        ]);

        // Step 5: Create permissions for Viewer role (read-only)
        foreach ($resources as $resource) {
            DB::table('permissions')->insert([
                'role_id' => $viewerRoleId,
                'resource' => $resource,
                'action' => 'view',
                'created_at' => now(),
            ]);
        }

        // Step 6: Create initial admin user
        $adminUserId = DB::table('users')->insertGetId([
            'name' => 'System Administrator',
            'email' => 'admin@opfcapital.com',
            'password' => Hash::make('ChangeMe123!'), // MUST be changed on first login
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 7: Assign admin role to admin user
        DB::table('user_roles')->insert([
            'user_id' => $adminUserId,
            'role_id' => $adminRoleId,
            'created_at' => now(),
        ]);

        // Step 8: Create initial cash account (optional)
        DB::table('accounts')->insert([
            'name' => 'Main Operating Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Success message
        $this->command->info('Production database seeded successfully!');
        $this->command->warn('');
        $this->command->warn('IMPORTANT: Default admin credentials:');
        $this->command->warn('  Email: admin@opfcapital.com');
        $this->command->warn('  Password: ChangeMe123!');
        $this->command->warn('');
        $this->command->error('CHANGE THE ADMIN PASSWORD IMMEDIATELY AFTER FIRST LOGIN!');
        $this->command->warn('');
    }
}
