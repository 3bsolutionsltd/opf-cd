<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('projects')->insert([
            'name' => 'Mobile App Development',
            'client' => 'TechCorp Solutions',
            'contract_value' => 150000.00,
            'contract_currency' => 'USD',
            'start_date' => '2026-01-15',
            'end_date' => '2026-07-15',
            'status' => 'active',
            'project_lead_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
