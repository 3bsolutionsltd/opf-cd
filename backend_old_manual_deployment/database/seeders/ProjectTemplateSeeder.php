<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign keys check temporarily (database-specific)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // 1. Web Application Template
        $webAppId = DB::table('project_templates')->insertGetId([
            'name' => 'Web Application',
            'description' => 'Professional web application development with full stack implementation, testing, and deployment.',
            'category' => 'Web App',
            'is_active' => true,
            'task_count' => 8,
            'average_duration_days' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_template_tasks')->insert([
            [
                'project_template_id' => $webAppId,
                'name' => 'Requirements & Specification',
                'description' => 'Gather requirements, create specifications, define scope and acceptance criteria',
                'weight' => 12,
                'phase_number' => 1,
                'estimated_duration_days' => 7,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $webAppId,
                'name' => 'UI/UX Design',
                'description' => 'Create wireframes, mockups, user flows, and design system',
                'weight' => 10,
                'phase_number' => 2,
                'estimated_duration_days' => 10,
                'dependencies' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $webAppId,
                'name' => 'Frontend Development',
                'description' => 'Implementation of UI components, responsive design, client-side logic',
                'weight' => 20,
                'phase_number' => 3,
                'estimated_duration_days' => 20,
                'dependencies' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $webAppId,
                'name' => 'Backend Development',
                'description' => 'API development, business logic, system integration, security implementation',
                'weight' => 25,
                'phase_number' => 4,
                'estimated_duration_days' => 25,
                'dependencies' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $webAppId,
                'name' => 'Database Design & Implementation',
                'description' => 'Schema design, indexing, optimization, data integrity',
                'weight' => 12,
                'phase_number' => 5,
                'estimated_duration_days' => 10,
                'dependencies' => '4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $webAppId,
                'name' => 'Testing & QA',
                'description' => 'Unit tests, integration tests, system testing, bug fixes',
                'weight' => 10,
                'phase_number' => 6,
                'estimated_duration_days' => 12,
                'dependencies' => '3,4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $webAppId,
                'name' => 'Deployment & Infrastructure',
                'description' => 'Environment setup, CI/CD pipeline, production deployment',
                'weight' => 8,
                'phase_number' => 7,
                'estimated_duration_days' => 8,
                'dependencies' => '6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $webAppId,
                'name' => 'Monitoring & Support',
                'description' => 'Production monitoring, performance optimization, support handoff',
                'weight' => 3,
                'phase_number' => 8,
                'estimated_duration_days' => 3,
                'dependencies' => '7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 2. Mobile Application Template
        $mobileAppId = DB::table('project_templates')->insertGetId([
            'name' => 'Mobile Application',
            'description' => 'Cross-platform mobile app development with iOS and Android support, backend API, and app store deployment.',
            'category' => 'Mobile App',
            'is_active' => true,
            'task_count' => 7,
            'average_duration_days' => 85,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_template_tasks')->insert([
            [
                'project_template_id' => $mobileAppId,
                'name' => 'Requirements & Design',
                'description' => 'App requirements, user flows, UI/UX mockups, feature specification',
                'weight' => 12,
                'phase_number' => 1,
                'estimated_duration_days' => 8,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $mobileAppId,
                'name' => 'Mobile UI Design',
                'description' => 'Platform-specific designs (iOS Human Interface, Material Design), asset creation',
                'weight' => 12,
                'phase_number' => 2,
                'estimated_duration_days' => 8,
                'dependencies' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $mobileAppId,
                'name' => 'iOS Development',
                'description' => 'Native iOS app development, Swift/Objective-C implementation, Apple frameworks',
                'weight' => 20,
                'phase_number' => 3,
                'estimated_duration_days' => 18,
                'dependencies' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $mobileAppId,
                'name' => 'Android Development',
                'description' => 'Native Android app development, Kotlin/Java implementation, Google frameworks',
                'weight' => 20,
                'phase_number' => 4,
                'estimated_duration_days' => 18,
                'dependencies' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $mobileAppId,
                'name' => 'Backend API Development',
                'description' => 'RESTful API, authentication, data synchronization, push notifications',
                'weight' => 22,
                'phase_number' => 5,
                'estimated_duration_days' => 18,
                'dependencies' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $mobileAppId,
                'name' => 'Testing & QA',
                'description' => 'Unit tests, device testing, performance testing, bug fixes',
                'weight' => 10,
                'phase_number' => 6,
                'estimated_duration_days' => 10,
                'dependencies' => '3,4,5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $mobileAppId,
                'name' => 'App Store Launch',
                'description' => 'App Store submission, Google Play submission, release notes, launch coordination',
                'weight' => 4,
                'phase_number' => 7,
                'estimated_duration_days' => 5,
                'dependencies' => '6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 3. E-Commerce Platform Template
        $ecommerceId = DB::table('project_templates')->insertGetId([
            'name' => 'E-Commerce Platform',
            'description' => 'Complete e-commerce solution with product catalog, shopping cart, payments, orders, and admin dashboard.',
            'category' => 'E-Commerce',
            'is_active' => true,
            'task_count' => 9,
            'average_duration_days' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_template_tasks')->insert([
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Requirements & Analysis',
                'description' => 'Business requirements, product strategy, feature list, success metrics',
                'weight' => 10,
                'phase_number' => 1,
                'estimated_duration_days' => 10,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'UX/Design & Branding',
                'description' => 'User experience design, brand implementation, responsive layouts, design system',
                'weight' => 10,
                'phase_number' => 2,
                'estimated_duration_days' => 12,
                'dependencies' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Product Catalog System',
                'description' => 'Product database, categories, variants, inventory management',
                'weight' => 12,
                'phase_number' => 3,
                'estimated_duration_days' => 12,
                'dependencies' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Shopping Cart & Checkout',
                'description' => 'Cart functionality, checkout flow, order creation, payment preparation',
                'weight' => 15,
                'phase_number' => 4,
                'estimated_duration_days' => 14,
                'dependencies' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Payment Integration',
                'description' => 'Payment gateway integration, PCI compliance, transaction handling, refunds',
                'weight' => 12,
                'phase_number' => 5,
                'estimated_duration_days' => 10,
                'dependencies' => '4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Order Management System',
                'description' => 'Order processing, fulfillment, shipping integration, tracking',
                'weight' => 12,
                'phase_number' => 6,
                'estimated_duration_days' => 12,
                'dependencies' => '4,5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Admin Dashboard',
                'description' => 'Admin interface, reporting, analytics, content management',
                'weight' => 12,
                'phase_number' => 7,
                'estimated_duration_days' => 12,
                'dependencies' => '3,6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Testing & QA',
                'description' => 'Functional testing, security testing, load testing, payment testing',
                'weight' => 12,
                'phase_number' => 8,
                'estimated_duration_days' => 14,
                'dependencies' => '2,4,5,6,7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $ecommerceId,
                'name' => 'Go-Live & Support',
                'description' => 'Production deployment, launch coordination, support setup, monitoring',
                'weight' => 5,
                'phase_number' => 9,
                'estimated_duration_days' => 3,
                'dependencies' => '8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 4. System Integration Template
        $integrationId = DB::table('project_templates')->insertGetId([
            'name' => 'System Integration',
            'description' => 'Integration of multiple systems with data synchronization, error handling, and seamless workflows.',
            'category' => 'Integration',
            'is_active' => true,
            'task_count' => 7,
            'average_duration_days' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_template_tasks')->insert([
            [
                'project_template_id' => $integrationId,
                'name' => 'Requirements & Analysis',
                'description' => 'Analyze source/target systems, define integration points, identify challenges',
                'weight' => 14,
                'phase_number' => 1,
                'estimated_duration_days' => 8,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $integrationId,
                'name' => 'Architecture & Planning',
                'description' => 'Design integration architecture, choose tools/technologies, create implementation plan',
                'weight' => 15,
                'phase_number' => 2,
                'estimated_duration_days' => 8,
                'dependencies' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $integrationId,
                'name' => 'API Development',
                'description' => 'Build connectors, adapters, and APIs for system communication',
                'weight' => 20,
                'phase_number' => 3,
                'estimated_duration_days' => 15,
                'dependencies' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $integrationId,
                'name' => 'Data Migration',
                'description' => 'Data mapping, transformation, cleansing, and initial load',
                'weight' => 15,
                'phase_number' => 4,
                'estimated_duration_days' => 12,
                'dependencies' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $integrationId,
                'name' => 'Error Handling & Logging',
                'description' => 'Exception handling, monitoring, logging, alerting mechanisms',
                'weight' => 12,
                'phase_number' => 5,
                'estimated_duration_days' => 8,
                'dependencies' => '3,4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $integrationId,
                'name' => 'Testing & Validation',
                'description' => 'Integration testing, data validation, performance testing',
                'weight' => 14,
                'phase_number' => 6,
                'estimated_duration_days' => 10,
                'dependencies' => '3,4,5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $integrationId,
                'name' => 'Production Rollout',
                'description' => 'Cutover planning, staged rollout, parallel running, user training',
                'weight' => 10,
                'phase_number' => 7,
                'estimated_duration_days' => 7,
                'dependencies' => '6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 5. Maintenance & Support Template
        $maintenanceId = DB::table('project_templates')->insertGetId([
            'name' => 'Maintenance & Support',
            'description' => 'Ongoing maintenance, bug fixes, performance optimization, and technical support for existing systems.',
            'category' => 'Maintenance',
            'is_active' => true,
            'task_count' => 5,
            'average_duration_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_template_tasks')->insert([
            [
                'project_template_id' => $maintenanceId,
                'name' => 'Bug Fixes & Support',
                'description' => 'Identify and fix bugs, handle support tickets, implement patches',
                'weight' => 30,
                'phase_number' => 1,
                'estimated_duration_days' => 10,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $maintenanceId,
                'name' => 'Monitoring & Metrics',
                'description' => 'System monitoring, performance metrics, uptime tracking, alerting',
                'weight' => 20,
                'phase_number' => 2,
                'estimated_duration_days' => 8,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $maintenanceId,
                'name' => 'Performance Enhancements',
                'description' => 'Optimization, refactoring, scalability improvements',
                'weight' => 20,
                'phase_number' => 3,
                'estimated_duration_days' => 10,
                'dependencies' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $maintenanceId,
                'name' => 'Security Updates',
                'description' => 'Dependency updates, security patches, vulnerability fixes',
                'weight' => 15,
                'phase_number' => 4,
                'estimated_duration_days' => 5,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_template_id' => $maintenanceId,
                'name' => 'Documentation & Handoff',
                'description' => 'Documentation updates, knowledge transfer, team handoff',
                'weight' => 15,
                'phase_number' => 5,
                'estimated_duration_days' => 5,
                'dependencies' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Re-enable foreign keys check
        if ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        echo "✅ Project templates seeded successfully with 5 templates and 36 tasks!\n";
    }
}
