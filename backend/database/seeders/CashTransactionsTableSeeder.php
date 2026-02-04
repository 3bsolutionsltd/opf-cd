<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashTransactionsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cash_transactions')->insert([
            'source_type' => 'project_payment',
            'source_id' => 1,
            'type' => 'inflow',
            'amount' => 45000.00,
            'currency' => 'USD',
            'transaction_date' => '2026-01-20',
            'account_id' => 1,
            'created_at' => now(),
        ]);
    }
}
