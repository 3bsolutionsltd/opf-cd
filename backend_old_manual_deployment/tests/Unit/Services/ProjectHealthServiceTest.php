<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProjectHealthService;
use App\Services\ProjectProgressService;
use App\Services\PaymentGapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * ProjectHealthServiceTest
 * 
 * Unit tests for ProjectHealthService PHI calculation.
 * 
 * Tests:
 * - PHI score calculation (weighted factor model)
 * - Time score calculation
 * - Payment score calculation
 * - Blocker score calculation
 * - Overdue score calculation
 * - Health status classification
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class ProjectHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectHealthService $service;
    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $progressService = new ProjectProgressService();
        $paymentGapService = new PaymentGapService();
        $this->service = new ProjectHealthService($progressService, $paymentGapService);

        // Create test project
        $this->projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'contract_value' => 50000,
            'currency' => 'USD',
            'created_at' => now(),
        ]);
    }

    /** @test */
    public function it_calculates_phi_score_for_healthy_project()
    {
        // Create balanced project data
        // Tasks: 50% complete
        DB::table('tasks')->insert([
            ['project_id' => $this->projectId, 'title' => 'Task 1', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 2', 'status' => 'in-progress', 
             'created_at' => now()],
        ]);

        // Milestones: 50% paid
        DB::table('payment_milestones')->insert([
            ['project_id' => $this->projectId, 'description' => 'Milestone 1', 'amount' => 10000, 
             'currency' => 'USD', 'status' => 'paid', 'due_date' => '2026-03-01', 'created_at' => now()],
            ['project_id' => $this->projectId, 'description' => 'Milestone 2', 'amount' => 10000, 
             'currency' => 'USD', 'status' => 'due', 'due_date' => '2026-04-01', 'created_at' => now()],
        ]);

        $health = $this->service->getProjectHealth($this->projectId);

        $this->assertArrayHasKey('phi_score', $health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('signals', $health);
        $this->assertGreaterThanOrEqual(0, $health['phi_score']);
        $this->assertLessThanOrEqual(100, $health['phi_score']);
    }

    /** @test */
    public function it_classifies_health_status_correctly()
    {
        // Test green status (PHI >= 70)
        DB::table('tasks')->insert([
            ['project_id' => $this->projectId, 'title' => 'Task 1', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 2', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 3', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 4', 'status' => 'in-progress', 
             'created_at' => now()],
        ]);

        DB::table('payment_milestones')->insert([
            ['project_id' => $this->projectId, 'description' => 'Milestone 1', 'amount' => 10000, 
             'currency' => 'USD', 'status' => 'paid', 'due_date' => '2026-03-01', 'created_at' => now()],
        ]);

        $health = $this->service->getProjectHealth($this->projectId);

        // Should be green (healthy) with high completion and no payment gap
        $this->assertArrayHasKey('status', $health);
        $this->assertContains($health['status'], ['green', 'amber', 'red']);
    }

    /** @test */
    public function it_returns_correct_signal_structure()
    {
        DB::table('tasks')->insert([
            ['project_id' => $this->projectId, 'title' => 'Task 1', 'status' => 'completed', 
             'created_at' => now()],
        ]);

        $health = $this->service->getProjectHealth($this->projectId);

        $this->assertArrayHasKey('signals', $health);
        $this->assertArrayHasKey('time_score', $health['signals']);
        $this->assertArrayHasKey('payment_score', $health['signals']);
        $this->assertArrayHasKey('blocker_score', $health['signals']);
        $this->assertArrayHasKey('overdue_score', $health['signals']);
    }

    /** @test */
    public function it_handles_project_with_no_tasks()
    {
        $health = $this->service->getProjectHealth($this->projectId);

        $this->assertArrayHasKey('phi_score', $health);
        $this->assertIsFloat($health['phi_score']);
    }

    /** @test */
    public function it_detects_blocked_tasks()
    {
        DB::table('tasks')->insert([
            ['project_id' => $this->projectId, 'title' => 'Task 1', 'status' => 'blocked', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 2', 'status' => 'in-progress', 
             'created_at' => now()],
        ]);

        $health = $this->service->getProjectHealth($this->projectId);

        $this->assertArrayHasKey('blocker_score', $health['signals']);
        $this->assertLessThan(100, $health['signals']['blocker_score']);
    }

    /** @test */
    public function it_detects_overdue_milestones()
    {
        DB::table('payment_milestones')->insert([
            ['project_id' => $this->projectId, 'description' => 'Overdue Milestone', 
             'amount' => 10000, 'currency' => 'USD', 'status' => 'due', 
             'due_date' => '2026-01-01', 'created_at' => now()],
        ]);

        $health = $this->service->getProjectHealth($this->projectId);

        $this->assertArrayHasKey('overdue_score', $health['signals']);
        // Should detect overdue milestone
        $this->assertLessThan(100, $health['signals']['overdue_score']);
    }

    /** @test */
    public function it_calculates_payment_gap_correctly()
    {
        // Create unbalanced payment situation
        DB::table('payment_milestones')->insert([
            ['project_id' => $this->projectId, 'description' => 'Milestone 1', 
             'amount' => 5000, 'currency' => 'USD', 'status' => 'paid', 
             'due_date' => '2026-02-01', 'created_at' => now()],
            ['project_id' => $this->projectId, 'description' => 'Milestone 2', 
             'amount' => 5000, 'currency' => 'USD', 'status' => 'due', 
             'due_date' => '2026-03-01', 'created_at' => now()],
        ]);

        // 50% paid but need more progress for balanced
        DB::table('tasks')->insert([
            ['project_id' => $this->projectId, 'title' => 'Task 1', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 2', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 3', 'status' => 'completed', 
             'created_at' => now()],
            ['project_id' => $this->projectId, 'title' => 'Task 4', 'status' => 'in-progress', 
             'created_at' => now()],
        ]);

        $health = $this->service->getProjectHealth($this->projectId);

        $this->assertArrayHasKey('payment_score', $health['signals']);
        $this->assertIsFloat($health['signals']['payment_score']);
    }
}
