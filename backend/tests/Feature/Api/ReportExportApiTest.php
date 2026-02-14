<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * ReportExportApiTest
 * 
 * Integration tests for report export API endpoints.
 * 
 * Tests:
 * - GET /api/reports/export/cash-flow
 * - GET /api/reports/export/project-health
 * - GET /api/reports/export/expenses
 * - CSV format validation
 * - Authentication and authorization
 * - Data filtering capabilities
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class ReportExportApiTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;
    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ]);

        // Create role with permissions
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Admin',
            'created_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $this->userId,
            'role_id' => $roleId,
        ]);

        DB::table('permissions')->insert([
            'role_id' => $roleId,
            'resource' => 'dashboards',
            'action' => 'view',
            'created_at' => now(),
        ]);

        // Create test account
        $this->accountId = DB::table('accounts')->insertGetId([
            'name' => 'Main Cash',
            'type' => 'cash',
            'currency' => 'USD',
            'opening_balance' => 10000,
            'created_at' => now(),
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_report_exports()
    {
        $response = $this->get('/api/reports/export/cash-flow');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_exports_cash_flow_report_as_csv()
    {
        // Create transactions
        DB::table('cash_transactions')->insert([
            [
                'account_id' => $this->accountId,
                'type' => 'inflow',
                'amount' => 5000,
                'category' => 'revenue',
                'description' => 'Client payment',
                'date' => now()->toDateString(),
                'created_at' => now(),
            ],
            [
                'account_id' => $this->accountId,
                'type' => 'outflow',
                'amount' => 1500,
                'category' => 'expense',
                'description' => 'Office rent',
                'date' => now()->toDateString(),
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->get('/api/reports/export/cash-flow?currency=USD');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition','attachment; filename="cash_flow_report.csv"');

        $csv = $response->getContent();
        $this->assertStringContainsString('Date,Type,Amount,Category,Description', $csv);
        $this->assertStringContainsString('Client payment', $csv);
        $this->assertStringContainsString('Office rent', $csv);
    }

    /** @test */
    public function it_exports_project_health_report_as_csv()
    {
        // Create project
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Website Redesign',
            'client_name' => 'ABC Corp',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'contract_value' => 50000,
            'currency' => 'USD',
            'status' => 'active',
            'created_at' => now(),
        ]);

        // Create milestone
        DB::table('milestones')->insert([
            'project_id' => $projectId,
            'name' => 'Design Phase',
            'due_date' => now()->addDays(10)->toDateString(),
            'is_completed' => false,
            'created_at' => now(),
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->get('/api/reports/export/project-health');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->getContent();
        $this->assertStringContainsString('Project,Client,Health Status,PHI Score', $csv);
        $this->assertStringContainsString('Website Redesign', $csv);
        $this->assertStringContainsString('ABC Corp', $csv);
    }

    /** @test */
    public function it_exports_expenses_report_as_csv()
    {
        DB::table('expenses')->insert([
            [
                'description' => 'Office Supplies',
                'amount' => 250,
                'currency' => 'USD',
                'type' => 'operational',
                'due_date' => now()->toDateString(),
                'status' => 'paid',
                'project_id' => null,
                'created_at' => now(),
            ],
            [
                'description' => 'Software License',
                'amount' => 500,
                'currency' => 'USD',
                'type' => 'operational',
                'due_date' => now()->toDateString(),
                'status' => 'due',
                'project_id' => null,
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->get('/api/reports/export/expenses?status=due');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->getContent();
        $this->assertStringContainsString('Description,Amount,Currency,Type,Due Date,Status', $csv);
        $this->assertStringContainsString('Software License', $csv);
        $this->assertStringNotContainsString('Office Supplies', $csv);  // Filtered out (paid)
    }

    /** @test */
    public function it_exports_opportunities_report_as_csv()
    {
        DB::table('opportunities')->insert([
            [
                'name' => 'Mobile App Project',
                'value' => 75000,
                'currency' => 'USD',
                'stage' => 'proposal',
                'close_probability' => 60,
                'expected_close_date' => now()->addDays(15)->toDateString(),
                'created_at' => now(),
            ],
            [
                'name' => 'Brand Identity',
                'value' => 25000,
                'currency' => 'USD',
                'stage' => 'negotiation',
                'close_probability' => 80,
                'expected_close_date' => now()->addDays(7)->toDateString(),
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->get('/api/reports/export/opportunities');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->getContent();
        $this->assertStringContainsString('Name,Value,Currency,Stage,Probability,Expected Close', $csv);
        $this->assertStringContainsString('Mobile App Project', $csv);
        $this->assertStringContainsString('Brand Identity', $csv);
    }

    /** @test */
    public function it_filters_cash_flow_by_date_range()
    {
        DB::table('cash_transactions')->insert([
            [
                'account_id' => $this->accountId,
                'type' => 'inflow',
                'amount' => 1000,
                'category' => 'revenue',
                'description' => 'Old transaction',
                'date' => now()->subMonths(2)->toDateString(),
                'created_at' => now(),
            ],
            [
                'account_id' => $this->accountId,
                'type' => 'inflow',
                'amount' => 2000,
                'category' => 'revenue',
                'description' => 'Recent transaction',
                'date' => now()->toDateString(),
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->get('/api/reports/export/cash-flow?start_date=' . now()->subMonths(1)->toDateString());

        $response->assertStatus(200);

        $csv = $response->getContent();
        $this->assertStringContainsString('Recent transaction', $csv);
        $this->assertStringNotContainsString('Old transaction', $csv);
    }

    /** @test */
    public function it_returns_empty_csv_when_no_data_matches_filters()
    {
        $response = $this->actingAs((object)['id' => $this->userId])
            ->get('/api/reports/export/expenses?status=nonexistent');

        $response->assertStatus(200);

        $csv = $response->getContent();
        $lines = explode("\n", trim($csv));
        $this->assertCount(1, $lines);  // Only header row
    }
}
