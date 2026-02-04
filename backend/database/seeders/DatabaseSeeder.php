<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            ProjectsTableSeeder::class,
            TasksTableSeeder::class,
            AccountsTableSeeder::class,
            PaymentMilestonesTableSeeder::class,
            CashTransactionsTableSeeder::class,
            ExpensesTableSeeder::class,
            OpportunitiesTableSeeder::class,
        ]);
    }
}
