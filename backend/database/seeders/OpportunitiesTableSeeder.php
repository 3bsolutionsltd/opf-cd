<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpportunitiesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('opportunities')->insert([
            [
                'client' => 'FinanceHub Inc',
                'description' => 'Enterprise Dashboard Development',
                'estimated_value' => 200000.00,
                'probability' => 60.00,
                'stage' => 'proposal',
                'source' => 'referral',
                'owner' => 1,
                'expected_close_date' => '2026-04-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'client' => 'RetailPro Ltd',
                'description' => 'E-commerce Platform Integration',
                'estimated_value' => 120000.00,
                'probability' => 75.00,
                'stage' => 'negotiation',
                'source' => 'direct',
                'owner' => 1,
                'expected_close_date' => '2026-03-31',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
