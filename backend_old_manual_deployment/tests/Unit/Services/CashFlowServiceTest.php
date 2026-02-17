<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CashFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * CashFlowServiceTest
 * 
 * Unit tests for CashFlowService critical calculations.
 * 
 * Tests:
 * - getCashAtHand() - current balance calculation
 * - calculateMonthlyBurnRate() - 3-month average outflows
 * - calculateCashRunway() - months remaining calculation
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class CashFlowServiceTest extends TestCase
{
    use RefreshDatabase;

    private CashFlowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CashFlowService();
    }

    /** @test */
    public function it_calculates_cash_at_hand_correctly()
    {
        // Create test account
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Test Bank',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 10000,
            'created_at' => now(),
        ]);

        // Add transactions
        DB::table('cash_transactions')->insert([
            ['account_id' => $accountId, 'transaction_date' => '2026-01-15', 'type' => 'inflow', 
             'amount' => 5000, 'currency' => 'USD', 'description' => 'Revenue', 'created_at' => now()],
            ['account_id' => $accountId, 'transaction_date' => '2026-01-20', 'type' => 'outflow', 
             'amount' => 2000, 'currency' => 'USD', 'description' => 'Expense', 'created_at' => now()],
            ['account_id' => $accountId, 'transaction_date' => '2026-02-05', 'type' => 'inflow', 
             'amount' => 3000, 'currency' => 'USD', 'description' => 'Revenue', 'created_at' => now()],
        ]);

        $cash = $this->service->getCashAtHand('USD');

        // 10000 + 5000 - 2000 + 3000 = 16000
        $this->assertEquals(16000, $cash);
    }

    /** @test */
    public function it_returns_zero_for_no_accounts()
    {
        $cash = $this->service->getCashAtHand('USD');
        $this->assertEquals(0, $cash);
    }

    /** @test */
    public function it_calculates_monthly_burn_rate_correctly()
    {
        // Create account
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Test Bank',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 50000,
            'created_at' => now(),
        ]);

        // Add outflows for last 3 months
        $threeMonthsAgo = now()->subMonths(3);
        $twoMonthsAgo = now()->subMonths(2);
        $oneMonthAgo = now()->subMonths(1);

        DB::table('cash_transactions')->insert([
            ['account_id' => $accountId, 'transaction_date' => $threeMonthsAgo->format('Y-m-d'), 
             'type' => 'outflow', 'amount' => 8000, 'currency' => 'USD', 'description' => 'Expense', 'created_at' => now()],
            ['account_id' => $accountId, 'transaction_date' => $twoMonthsAgo->format('Y-m-d'), 
             'type' => 'outflow', 'amount' => 9000, 'currency' => 'USD', 'description' => 'Expense', 'created_at' => now()],
            ['account_id' => $accountId, 'transaction_date' => $oneMonthAgo->format('Y-m-d'), 
             'type' => 'outflow', 'amount' => 10000, 'currency' => 'USD', 'description' => 'Expense', 'created_at' => now()],
        ]);

        $burnRate = $this->service->calculateMonthlyBurnRate('USD');

        // (8000 + 9000 + 10000) / 3 = 9000
        $this->assertEquals(9000, $burnRate);
    }

    /** @test */
    public function it_returns_zero_burn_rate_for_no_outflows()
    {
        $burnRate = $this->service->calculateMonthlyBurnRate('USD');
        $this->assertEquals(0, $burnRate);
    }

    /** @test */
    public function it_calculates_cash_runway_correctly()
    {
        // Create account with balance
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Test Bank',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 45000,
            'created_at' => now(),
        ]);

        // Add consistent monthly outflows
        for ($i = 1; $i <= 3; $i++) {
            DB::table('cash_transactions')->insert([
                'account_id' => $accountId,
                'transaction_date' => now()->subMonths($i)->format('Y-m-d'),
                'type' => 'outflow',
                'amount' => 5000,
                'currency' => 'USD',
                'description' => 'Monthly expense',
                'created_at' => now(),
            ]);
        }

        $runway = $this->service->calculateCashRunway('USD');

        // Cash: 45000 - 15000 = 30000
        // Burn: 15000 / 3 = 5000
        // Runway: 30000 / 5000 = 6 months
        $this->assertEquals(6, $runway);
    }

    /** @test */
    public function it_returns_zero_runway_when_burn_rate_is_zero()
    {
        // Create account with balance but no outflows
        DB::table('accounts')->insert([
            'name' => 'Test Bank',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 50000,
            'created_at' => now(),
        ]);

        $runway = $this->service->calculateCashRunway('USD');
        $this->assertEquals(0, $runway);
    }

    /** @test */
    public function it_filters_by_currency_correctly()
    {
        // Create USD account
        $usdAccountId = DB::table('accounts')->insertGetId([
            'name' => 'USD Bank',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 10000,
            'created_at' => now(),
        ]);

        // Create UGX account
        $ugxAccountId = DB::table('accounts')->insertGetId([
            'name' => 'UGX Bank',
            'type' => 'bank',
            'currency' => 'UGX',
            'opening_balance' => 5000000,
            'created_at' => now(),
        ]);

        // Add transactions
        DB::table('cash_transactions')->insert([
            ['account_id' => $usdAccountId, 'transaction_date' => '2026-01-15', 'type' => 'inflow', 
             'amount' => 5000, 'currency' => 'USD', 'description' => 'Revenue', 'created_at' => now()],
            ['account_id' => $ugxAccountId, 'transaction_date' => '2026-01-15', 'type' => 'inflow', 
             'amount' => 2000000, 'currency' => 'UGX', 'description' => 'Revenue', 'created_at' => now()],
        ]);

        $usdCash = $this->service->getCashAtHand('USD');
        $ugxCash = $this->service->getCashAtHand('UGX');

        $this->assertEquals(15000, $usdCash);  // 10000 + 5000
        $this->assertEquals(7000000, $ugxCash); // 5000000 + 2000000
    }
}
