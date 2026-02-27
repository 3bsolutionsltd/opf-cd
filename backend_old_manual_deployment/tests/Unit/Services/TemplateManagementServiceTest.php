<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TemplateManagementService;
use App\Services\ProjectTemplateService;
use App\Services\OpportunityProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * TemplateManagementServiceTest
 * 
 * Unit tests for TemplateManagementService orchestration layer.
 * 
 * Tests:
 * - Template preview with weight calculation
 * - Project creation with template validation
 * - Template application with validation
 * - Admin template management delegation
 * 
 * This service was created to fix architectural violations where
 * TemplateController was injecting multiple services and performing calculations.
 */
class TemplateManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $templateService = new ProjectTemplateService();
        $projectService = new OpportunityProjectService(app(\App\Services\AuditService::class));
        
        $this->service = new TemplateManagementService($templateService, $projectService);
        
        // Seed templates
        $this->artisan('db:seed', ['--class' => 'ProjectTemplateSeeder']);
    }

    /** @test */
    public function it_gets_all_active_templates()
    {
        $templates = $this->service->getAllActiveTemplates();
        
        $this->assertGreaterThanOrEqual(5, count($templates));
        
        foreach ($templates as $template) {
            $this->assertTrue($template->is_active);
        }
    }

    /** @test */
    public function it_gets_template_preview_with_weight_calculation()
    {
        // Get first template (Web Application)
        $template = DB::table('project_templates')
            ->where('name', 'Web Application')
            ->first();
        
        $result = $this->service->getTemplatePreview($template->id);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('template', $result['data']);
        $this->assertArrayHasKey('tasks', $result['data']);
        $this->assertArrayHasKey('total_weight', $result['data']);
        $this->assertArrayHasKey('is_valid', $result['data']);
        
        // Verify calculation was done in service
        $this->assertEquals(100, $result['data']['total_weight']);
        $this->assertTrue($result['data']['is_valid']);
    }

    /** @test */
    public function it_returns_error_for_nonexistent_template_preview()
    {
        $result = $this->service->getTemplatePreview(99999);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Template not found', $result['message']);
        $this->assertNull($result['data']);
    }

    /** @test */
    public function it_validates_template_is_active_before_creating_project()
    {
        // Create inactive template
        $templateId = DB::table('project_templates')->insertGetId([
            'name' => 'Inactive Template',
            'description' => 'Test inactive template',
            'category' => 'test',
            'is_active' => false,
            'task_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create opportunity
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Test Opportunity',
            'client' => 'Test Client',
            'estimated_value' => 50000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'created_at' => now(),
        ]);
        
        $result = $this->service->createProjectWithTemplate(
            $opportunityId,
            $templateId,
            1,
            null
        );
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('inactive', $result['message']);
    }

    /** @test */
    public function it_creates_project_with_template_successfully()
    {
        // Get active template
        $template = DB::table('project_templates')
            ->where('name', 'Mobile Application')
            ->where('is_active', true)
            ->first();
        
        // Create opportunity
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Mobile App Project',
            'client' => 'Test Client',
            'estimated_value' => 75000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'created_at' => now(),
        ]);
        
        $result = $this->service->createProjectWithTemplate(
            $opportunityId,
            $template->id,
            1,
            null
        );
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('project', $result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertEquals(7, $result['tasks_count']); // Mobile app has 7 tasks
        $this->assertEquals('Mobile Application', $result['template_name']);
        
        // Verify project created in database
        $this->assertDatabaseHas('projects', [
            'id' => $result['project']['id'],
            'opportunity_id' => $opportunityId,
        ]);
    }

    /** @test */
    public function it_validates_opportunity_stage_is_won()
    {
        $template = DB::table('project_templates')
            ->where('is_active', true)
            ->first();
        
        // Create opportunity with stage NOT won
        $opportunityId = DB::table('opportunities')->insertGetId([
            'name' => 'Prospect Opportunity',
            'client' => 'Test Client',
            'estimated_value' => 50000,
            'currency' => 'USD',
            'stage' => 'proposal', // NOT won
            'probability' => 50,
            'created_at' => now(),
        ]);
        
        $result = $this->service->createProjectWithTemplate(
            $opportunityId,
            $template->id,
            1,
            null
        );
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('won', $result['message']);
    }

    /** @test */
    public function it_applies_template_to_existing_project()
    {
        $template = DB::table('project_templates')
            ->where('name', 'System Integration')
            ->first();
        
        // Create project without template
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Integration Project',
            'client' => 'Test Client',
            'contract_value' => 60000,
            'contract_currency' => 'USD',
            'start_date' => now()->format('Y-m-d'),
            'status' => 'planned',
            'created_at' => now(),
        ]);
        
        $result = $this->service->applyTemplateToProject(
            $projectId,
            $template->id,
            1,
            null
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals(7, $result['tasks_count']); // System Integration has 7 tasks
        $this->assertEquals('System Integration', $result['template_name']);
        
        // Verify tasks created
        $tasksCount = DB::table('tasks')
            ->where('project_id', $projectId)
            ->count();
        
        $this->assertEquals(7, $tasksCount);
    }

    /** @test */
    public function it_prevents_applying_template_to_project_with_existing_tasks()
    {
        $template = DB::table('project_templates')
            ->where('is_active', true)
            ->first();
        
        // Create project with existing task
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Existing Project',
            'client' => 'Test Client',
            'contract_value' => 50000,
            'contract_currency' => 'USD',
            'start_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
        ]);
        
        DB::table('tasks')->insert([
            'project_id' => $projectId,
            'name' => 'Existing Task',
            'status' => 'in-progress',
            'created_at' => now(),
        ]);
        
        $result = $this->service->applyTemplateToProject(
            $projectId,
            $template->id,
            1,
            null
        );
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already has', $result['message']);
    }

    /** @test */
    public function it_delegates_admin_operations_correctly()
    {
        // Test createTemplate delegation
        $templateId = $this->service->createTemplate([
            'name' => 'New Test Template',
            'description' => 'Created via orchestration service',
            'category' => 'test',
            'is_active' => true,
        ]);
        
        $this->assertIsInt($templateId);
        $this->assertDatabaseHas('project_templates', [
            'id' => $templateId,
            'name' => 'New Test Template',
        ]);
        
        // Test updateTemplate delegation
        $success = $this->service->updateTemplate($templateId, [
            'description' => 'Updated description',
        ]);
        
        $this->assertTrue($success);
        $this->assertDatabaseHas('project_templates', [
            'id' => $templateId,
            'description' => 'Updated description',
        ]);
        
        // Test deleteTemplate delegation
        $deleteSuccess = $this->service->deleteTemplate($templateId);
        $this->assertTrue($deleteSuccess);
        
        $this->assertDatabaseMissing('project_templates', [
            'id' => $templateId,
        ]);
    }

    /** @test */
    public function it_delegates_task_operations_correctly()
    {
        $template = DB::table('project_templates')
            ->where('is_active', true)
            ->first();
        
        // Test addTaskToTemplate delegation
        $taskId = $this->service->addTaskToTemplate($template->id, [
            'name' => 'New Task',
            'description' => 'Task added via service',
            'weight' => 10,
            'phase_number' => 1,
            'estimated_duration_days' => 5,
        ]);
        
        $this->assertIsInt($taskId);
        $this->assertDatabaseHas('project_template_tasks', [
            'id' => $taskId,
            'project_template_id' => $template->id,
            'name' => 'New Task',
        ]);
        
        // Test updateTemplateTask delegation
        $updateSuccess = $this->service->updateTemplateTask($taskId, [
            'weight' => 15,
        ]);
        
        $this->assertTrue($updateSuccess);
        
        // Test deleteTemplateTask delegation
        $deleteSuccess = $this->service->deleteTemplateTask($taskId);
        $this->assertTrue($deleteSuccess);
    }

    /** @test */
    public function it_validates_template_weights()
    {
        $template = DB::table('project_templates')
            ->where('name', 'E-Commerce Platform')
            ->first();
        
        // Should pass validation (templates are seeded with 100% weight)
        $isValid = $this->service->validateTemplateWeights($template->id);
        
        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_checks_template_existence()
    {
        $template = DB::table('project_templates')
            ->where('is_active', true)
            ->first();
        
        $exists = $this->service->templateExists($template->id);
        $this->assertTrue($exists);
        
        $notExists = $this->service->templateExists(99999);
        $this->assertFalse($notExists);
    }
}
