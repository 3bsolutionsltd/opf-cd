<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMilestonesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payment_milestones')->insert([
            [
                'project_id' => 1,
                'name' => 'Initial Payment',
                'amount' => 45000.00,
                'currency' => 'USD',
                'due_date' => '2026-01-15',
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => 1,
                'name' => 'Milestone 2 - Design Complete',
                'amount' => 45000.00,
                'currency' => 'USD',
                'due_date' => '2026-03-15',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => 1,
                'name' => 'Final Payment',
                'amount' => 60000.00,
                'currency' => 'USD',
                'due_date' => '2026-07-15',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
