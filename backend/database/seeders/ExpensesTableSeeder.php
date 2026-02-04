<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpensesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('expenses')->insert([
            [
                'name' => 'Office Rent',
                'category' => 'facilities',
                'amount' => 3500.00,
                'currency' => 'USD',
                'type' => 'recurring',
                'frequency' => 'monthly',
                'status' => 'due',
                'due_date' => '2026-03-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cloud Infrastructure',
                'category' => 'technology',
                'amount' => 1200.00,
                'currency' => 'USD',
                'type' => 'recurring',
                'frequency' => 'monthly',
                'status' => 'due',
                'due_date' => '2026-03-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
