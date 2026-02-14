<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * DashboardApiTest
 * 
 * Integration tests for dashboard API endpoints.
 * 
 * Tests:
 * - GET /api/dashboard/summary
 * - Authentication enforcement
 * - Response structure validation
 * - Data accuracy
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with permissions
        $this->userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ]);

        // Create role and assign to user
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Admin',
            'created_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $this->userId,
            'role_id' => $roleId,
        ]);

        // Create permissions
        DB::table('permissions')->insert([
            'role_id' => $roleId,
            'resource' => 'dashboards',
            'action' => 'view',
            'created_at' => now(),
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_dashboard_summary()
    {
        $response = $this->getJson('/api/dashboard/summary');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_dashboard_summary_with_correct_structure()
    {
        // Create test data
        DB::table('projects')->insert([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_value' => 50000,
            'currency' => 'USD',
            'created_at' => now(),
        ]);

        DB::table('accounts')->insert([
            'name' => 'Test Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 10000,
            'created_at' => now(),
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/dashboard/summary?currency=USD');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_projects',
                'active_projects',
                'cash_at_hand',
                'burn_rate',
                'cash_runway_months',
                'total_pipeline_value',
                'total_upcoming_expenses',
                'health_green_count',
                'health_red_count',
                'health_amber_count',
                'projects_at_risk',
                'currency',
                'alert_count',
            ]);

        $this->assertEquals(1, $response->json('total_projects'));
        $this->assertEquals(1, $response->json('active_projects'));
        $this->assertEquals('USD', $response->json('currency'));
    }

    /** @test */
    public function it_calculates_cash_at_hand_correctly()
    {
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Test Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 5000,
            'created_at' => now(),
        ]);

        DB::table('cash_transactions')->insert([
            ['account_id' => $accountId, 'transaction_date' => '2026-01-15', 'type' => 'inflow', 
             'amount' => 3000, 'currency' => 'USD', 'description' => 'Revenue', 'created_at' => now()],
            ['account_id' => $accountId, 'transaction_date' => '2026-01-20', 'type' => 'outflow', 
             'amount' => 1000, 'currency' => 'USD', 'description' => 'Expense', 'created_at' => now()],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/dashboard/summary?currency=USD');

        $response->assertStatus(200);

        // 5000 + 3000 - 1000 = 7000
        $this->assertEquals(7000, $response->json('cash_at_hand'));
    }

    /** @test */
    public function it_filters_by_currency()
    {
        // Create USD account
        DB::table('accounts')->insert([
            'name' => 'USD Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 10000,
            'created_at' => now(),
        ]);

        // Create UGX account
        DB::table('accounts')->insert([
            'name' => 'UGX Account',
            'type' => 'bank',
            'currency' => 'UGX',
            'opening_balance' => 5000000,
            'created_at' => now(),
        ]);

        $usdResponse = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/dashboard/summary?currency=USD');

        $ugxResponse = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/dashboard/summary?currency=UGX');

        $this->assertEquals('USD', $usdResponse->json('currency'));
        $this->assertEquals(10000, $usdResponse->json('cash_at_hand'));

        $this->assertEquals('UGX', $ugxResponse->json('currency'));
        $this->assertEquals(5000000, $ugxResponse->json('cash_at_hand'));
    }

    /** @test */
    public function it_returns_zero_values_for_empty_database()
    {
        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/dashboard/summary?currency=USD');

        $response->assertStatus(200);

        $this->assertEquals(0, $response->json('total_projects'));
        $this->assertEquals(0, $response->json('cash_at_hand'));
        $this->assertEquals(0, $response->json('burn_rate'));
        $this->assertEquals(0, $response->json('total_pipeline_value'));
    }
}
