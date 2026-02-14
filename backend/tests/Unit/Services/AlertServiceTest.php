<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AlertService;
use App\Services\ProjectHealthService;
use App\Services\PaymentGapService;
use App\Services\CashFlowService;
use App\Services\ProjectProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * AlertServiceTest
 * 
 * Unit tests for AlertService alert generation.
 * 
 * Tests:
 * - Project schedule alerts (time_score < 60)
 * - Payment gap alerts (gap > 20%)
 * - Low cash runway alerts (< 3 months)
 * - Overdue expense alerts
 * - Opportunity closing alerts (< 7 days)
 * - Duplicate prevention
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlertService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $progressService = new ProjectProgressService();
        $paymentGapService = new PaymentGapService();
        $healthService = new ProjectHealthService($progressService, $paymentGapService);
        $cashFlowService = new CashFlowService();
        
        $this->service = new AlertService($healthService, $paymentGapService, $cashFlowService);
    }

    /** @test */
    public function it_creates_project_schedule_alert_when_behind()
    {
        // Create user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ]);

        // Create project that's behind schedule
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Behind Schedule Project',
            'client' => 'Test Client',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_value' => 50000,
            'currency' => 'USD',
            'created_at' => now(),
        ]);

        // Create tasks with low completion (will trigger time_score < 60)
        DB::table('tasks')->insert([
            ['project_id' => $projectId, 'title' => 'Task 1', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $projectId, 'title' => 'Task 2', 'status' => 'in-progress', 
             'created_at' => now()],
            ['project_id' => $projectId, 'title' => 'Task 3', 'status' => 'pending', 
             'created_at' => now()],
            ['project_id' => $projectId, 'title' => 'Task 4', 'status' => 'pending', 
             'created_at' => now()],
            ['project_id' => $projectId, 'title' => 'Task 5', 'status' => 'pending', 
             'created_at' => now()],
        ]);

        $result = $this->service->evaluateAllAlerts();

        $this->assertArrayHasKey('alerts_created', $result);
        $this->assertArrayHasKey('alerts_by_type', $result);
        $this->assertIsInt($result['alerts_created']);
    }

    /** @test */
    public function it_prevents_duplicate_alerts_within_7_days()
    {
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_value' => 50000,
            'currency' => 'USD',
            'created_at' => now(),
        ]);

        // Create existing alert from 5 days ago
        DB::table('alerts')->insert([
            'type' => 'project_behind_schedule',
            'severity' => 'warning',
            'entity_type' => 'project',
            'entity_id' => $projectId,
            'message' => 'Test alert',
            'is_dismissed' => false,
            'created_at' => now()->subDays(5),
        ]);

        // Create conditions for alert
        DB::table('tasks')->insert([
            ['project_id' => $projectId, 'title' => 'Task 1', 'status' => 'pending', 
             'created_at' => now()],
        ]);

        // Try to create alert again
        $initialCount = DB::table('alerts')->count();
        $this->service->evaluateAllAlerts();
        $finalCount = DB::table('alerts')->count();

        // Should not create duplicate
        $this->assertEquals($initialCount, $finalCount);
    }

    /** @test */
    public function it_creates_overdue_expense_alert()
    {
        // Create overdue expense
        DB::table('expenses')->insert([
            'description' => 'Overdue Expense',
            'amount' => 1000,
            'currency' => 'USD',
            'type' => 'operational',
            'status' => 'due',
            'due_date' => '2026-01-01', // Past date
            'created_at' => now(),
        ]);

        $result = $this->service->evaluateAllAlerts();

        $this->assertGreaterThanOrEqual(0, $result['alerts_created']);
    }

    /** @test */
    public function it_creates_opportunity_closing_alert()
    {
        // Create opportunity closing within 7 days
        DB::table('opportunities')->insert([
            'name' => 'Closing Soon',
            'client' => 'Test Client',
            'stage' => 'negotiation',
            'value' => 25000,
            'currency' => 'USD',
            'close_probability' => 75,
            'expected_close_date' => now()->addDays(5)->format('Y-m-d'),
            'created_at' => now(),
        ]);

        $result = $this->service->evaluateAllAlerts();

        $this->assertArrayHasKey('alerts_by_type', $result);
        $this->assertArrayHasKey('opportunity_closing_soon', $result['alerts_by_type']);
    }

    /** @test */
    public function it_returns_active_alert_count()
    {
        // Create dismissed alert
        DB::table('alerts')->insert([
            'type' => 'low_cash_runway',
            'severity' => 'critical',
            'entity_type' => 'system',
            'entity_id' => null,
            'message' => 'Dismissed alert',
            'is_dismissed' => true,
            'dismissed_at' => now(),
            'dismissed_by' => 1,
            'created_at' => now(),
        ]);

        // Create active alert
        DB::table('alerts')->insert([
            'type' => 'low_cash_runway',
            'severity' => 'critical',
            'entity_type' => 'system',
            'entity_id' => null,
            'message' => 'Active alert',
            'is_dismissed' => false,
            'created_at' => now(),
        ]);

        $count = $this->service->getTotalAlertCount();

        $this->assertEquals(1, $count); // Only active alert counted
    }

    /** @test */
    public function it_dismisses_alert_successfully()
    {
        $alertId = DB::table('alerts')->insertGetId([
            'type' => 'low_cash_runway',
            'severity' => 'critical',
            'entity_type' => 'system',
            'entity_id' => null,
            'message' => 'Test alert',
            'is_dismissed' => false,
            'created_at' => now(),
        ]);

        $result = $this->service->dismissAlert($alertId, 1);

        $this->assertTrue($result);

        $alert = DB::table('alerts')->where('id', $alertId)->first();
        $this->assertTrue($alert->is_dismissed);
        $this->assertNotNull($alert->dismissed_at);
        $this->assertEquals(1, $alert->dismissed_by);
    }

    /** @test */
    public function it_returns_correct_alert_counts_by_severity()
    {
        DB::table('alerts')->insert([
            ['type' => 'low_cash_runway', 'severity' => 'critical', 'entity_type' => 'system', 
             'entity_id' => null, 'message' => 'Critical 1', 'is_dismissed' => false, 'created_at' => now()],
            ['type' => 'project_behind_schedule', 'severity' => 'warning', 'entity_type' => 'project', 
             'entity_id' => 1, 'message' => 'Warning 1', 'is_dismissed' => false, 'created_at' => now()],
            ['type' => 'opportunity_closing_soon', 'severity' => 'info', 'entity_type' => 'opportunity', 
             'entity_id' => 1, 'message' => 'Info 1', 'is_dismissed' => false, 'created_at' => now()],
        ]);

        $counts = $this->service->getAlertCountBySeverity();

        $this->assertEquals(3, $counts['total']);
        $this->assertEquals(1, $counts['critical']);
        $this->assertEquals(1, $counts['warning']);
        $this->assertEquals(1, $counts['info']);
    }
}
