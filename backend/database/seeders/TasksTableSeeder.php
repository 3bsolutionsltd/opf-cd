<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TasksTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tasks')->insert([
            [
                'project_id' => 1,
                'name' => 'Requirements Analysis',
                'category' => 'planning',
                'weight' => 15.00,
                'progress' => 100.00,
                'status' => 'done',
                'assigned_to' => 1,
                'start_date' => '2026-01-15',
                'due_date' => '2026-01-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => 1,
                'name' => 'UI/UX Design',
                'category' => 'design',
                'weight' => 25.00,
                'progress' => 80.00,
                'status' => 'wip',
                'assigned_to' => 1,
                'start_date' => '2026-02-01',
                'due_date' => '2026-02-28',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => 1,
                'name' => 'Backend Development',
                'category' => 'development',
                'weight' => 35.00,
                'progress' => 45.00,
                'status' => 'wip',
                'assigned_to' => 1,
                'start_date' => '2026-02-15',
                'due_date' => '2026-05-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => 1,
                'name' => 'Testing & QA',
                'category' => 'testing',
                'weight' => 15.00,
                'progress' => 0.00,
                'status' => 'todo',
                'assigned_to' => 1,
                'start_date' => '2026-06-01',
                'due_date' => '2026-06-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => 1,
                'name' => 'Deployment',
                'category' => 'deployment',
                'weight' => 10.00,
                'progress' => 0.00,
                'status' => 'todo',
                'assigned_to' => 1,
                'start_date' => '2026-07-01',
                'due_date' => '2026-07-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
