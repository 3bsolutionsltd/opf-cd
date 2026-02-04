<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->insert([
            'name' => 'Business Checking Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 75000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
