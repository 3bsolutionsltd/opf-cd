<?php

namespace Tests\Unit\Services;

use App\Services\OpportunityProjectService;
use App\Services\ProjectTemplateService;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class OpportunityProjectServiceTest extends TestCase
{
    private OpportunityProjectService $service;
    private ProjectTemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpportunityProjectService();
        $this->templateService = new ProjectTemplateService();

        // Run migrations
        $this->artisan('migrate', ['--database' => 'sqlite']);
        
        // Seed templates and initial data
        $this->artisan('db:seed', ['--class' => 'ProjectTemplateSeeder']);
    }

    /**
     * Test 1: createProjectWithTemplate creates project atomically
     */
    public function test_create_project_with_template_creates_project_and_tasks(): void
    {
        // Create a test opportunity first
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Test Opportunity',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create project with template (Web App template = 1, has 8 tasks)
        $result = $this->service->createProjectWithTemplate($opportunityId, 1, 1, null);

        $this->assertTrue(
            $result['success'],
            "Failed to create project with template: " . ($result['error'] ?? '')
        );
        
        $this->assertArrayHasKey('project', $result);
        $this->assertArrayHasKey('tasks', $result);
        
        // Verify project was created
        $projectId = $result['project']['id'];
        $projectExists = DB::table('projects')->where('id', $projectId)->exists();
        $this->assertTrue($projectExists, "Project should be created");

        // Verify all 8 tasks were created
        $taskCount = DB::table('tasks')->where('project_id', $projectId)->count();
        $this->assertEquals(8, $taskCount, "Should create 8 tasks for Web App template");
        
        // Verify task count in response matches
        $this->assertEquals(8, count($result['tasks']), "Response should contain 8 tasks");
    }

    /**
     * Test 2: Tasks are created with correct weight distribution
     */
    public function test_created_tasks_have_correct_weights(): void
    {
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'E-Commerce Opportunity',
            'estimated_value' => 15000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create with E-Commerce template (ID 3, has 9 tasks)
        $result = $this->service->createProjectWithTemplate($opportunityId, 3, 1, null);

        $this->assertTrue($result['success']);
        
        $projectId = $result['project']['id'];
        
        // Verify weight distribution sums to 100
        $totalWeight = DB::table('tasks')
            ->where('project_id', $projectId)
            ->sum('weight');

        $this->assertEquals(
            100,
            $totalWeight,
            "Tasks for created project should have total weight 100, got $totalWeight"
        );
    }

    /**
     * Test 3: Atomic transaction - all succeed or all rollback
     */
    public function test_transaction_atomicity_all_succeed(): void
    {
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Integration Opportunity',
            'estimated_value' => 8000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $initialProjectCount = DB::table('projects')->count();
        $initialTaskCount = DB::table('tasks')->count();

        // Create project with Integration template (ID 4, has 7 tasks)
        $result = $this->service->createProjectWithTemplate($opportunityId, 4, 1, null);

        $this->assertTrue($result['success']);

        $finalProjectCount = DB::table('projects')->count();
        $finalTaskCount = DB::table('tasks')->count();

        // Verify both project and tasks were created
        $this->assertEquals($initialProjectCount + 1, $finalProjectCount, 'Project count should increase by 1');
        $this->assertEquals($initialTaskCount + 7, $finalTaskCount, 'Task count should increase by 7');
    }

    /**
     * Test 4: Rejects if opportunity not found
     */
    public function test_rejects_if_opportunity_not_found(): void
    {
        $result = $this->service->createProjectWithTemplate(99999, 1, 1, null);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('opportunity', strtolower($result['error']));
    }

    /**
     * Test 5: Rejects if template not found
     */
    public function test_rejects_if_template_not_found(): void
    {
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Test Opportunity',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->createProjectWithTemplate($opportunityId, 99999, 1, null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('template', strtolower($result['error']));
    }

    /**
     * Test 6: Rejects if opportunity is not won
     */
    public function test_rejects_if_opportunity_not_won(): void
    {
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Unwon Opportunity',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'negotiation', // Not won
            'probability' => 50,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->createProjectWithTemplate($opportunityId, 1, 1, null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('won', strtolower($result['error']));
    }

    /**
     * Test 7: Audit trail is created
     */
    public function test_audit_trail_created_for_project_creation(): void
    {
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Test Opportunity',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $initialAuditCount = DB::table('audit_logs')->count();

        $result = $this->service->createProjectWithTemplate($opportunityId, 1, 1, null);

        $this->assertTrue($result['success']);

        // Should have created audit entries for project + tasks
        $finalAuditCount = DB::table('audit_logs')->count();
        $this->assertGreaterThan(
            $initialAuditCount,
            $finalAuditCount,
            'Audit logs should be created for project and tasks'
        );
    }

    /**
     * Test 8: applyTemplateToProject creates tasks on empty project
     */
    public function test_apply_template_to_empty_project(): void
    {
        // Create empty project
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Empty Project',
            'contract_value' => 5000,
            'currency' => 'USD',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Apply template (Mobile App = 2, has 7 tasks)
        $result = $this->service->applyTemplateToProject($projectId, 2, 1);

        $this->assertTrue(
            $result['success'],
            "Failed to apply template: " . ($result['error'] ?? '')
        );

        // Verify 7 tasks were created
        $taskCount = DB::table('tasks')->where('project_id', $projectId)->count();
        $this->assertEquals(7, $taskCount, "Should create 7 tasks for Mobile App template");
    }

    /**
     * Test 9: applyTemplateToProject rejects project with existing tasks
     */
    public function test_apply_template_rejects_project_with_tasks(): void
    {
        // Create project with existing task
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Project with Tasks',
            'contract_value' => 5000,
            'currency' => 'USD',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tasks')->insert([
            'project_id' => $projectId,
            'name' => 'Existing Task',
            'weight' => 100,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to apply template
        $result = $this->service->applyTemplateToProject($projectId, 1, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('existing', strtolower($result['error']));
    }

    /**
     * Test 10: Performance - all templates create quickly
     */
    public function test_performance_project_creation_under_500ms(): void
    {
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Performance Test',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $start = microtime(true);
        
        $result = $this->service->createProjectWithTemplate($opportunityId, 1, 1, null);
        
        $end = microtime(true);
        $duration = ($end - $start) * 1000; // milliseconds

        $this->assertTrue($result['success']);
        $this->assertLessThan(
            500,
            $duration,
            "Project creation should complete in < 500ms, took {$duration}ms"
        );
    }
}
