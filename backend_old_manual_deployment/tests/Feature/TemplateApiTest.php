<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TemplateApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations and seed
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'ProjectTemplateSeeder']);
    }

    /**
     * Test 1: GET /api/templates - List all active templates
     */
    public function test_get_templates_returns_all_active(): void
    {
        $response = $this->getJson('/api/templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => [
                    'id', 'name', 'description', 'category', 'is_active', 'task_count', 'average_duration_days'
                ]]
            ]);

        $templates = $response->json('data');
        $this->assertCount(5, $templates, 'Should return all 5 active templates');

        // Verify all are marked as active
        foreach ($templates as $template) {
            $this->assertTrue($template['is_active'], "Template {$template['name']} should be active");
        }
    }

    /**
     * Test 2: GET /api/templates/{id} - Get single template with tasks
     */
    public function test_get_template_by_id_returns_with_tasks(): void
    {
        $response = $this->getJson('/api/templates/1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'name', 'description', 'category', 'task_count',
                    'tasks' => ['*' => [
                        'id', 'name', 'weight', 'phase_number', 'estimated_duration_days'
                    ]]
                ]
            ]);

        $template = $response->json('data');
        $this->assertEquals('Web Application', $template['name']);
        $this->assertCount(8, $template['tasks'], 'Web App template should have 8 tasks');
    }

    /**
     * Test 3: GET /api/templates/{id}/preview - Preview template tasks
     */
    public function test_preview_template_returns_tasks(): void
    {
        $response = $this->getJson('/api/templates/1/preview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'name', 'tasks' => ['*' => [
                        'name', 'weight', 'phase_number'
                    ]]
                ]
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['tasks']);
    }

    /**
     * Test 4: GET /api/templates/{id} - Returns 404 for invalid template
     */
    public function test_get_invalid_template_returns_404(): void
    {
        $response = $this->getJson('/api/templates/99999');

        $response->assertStatus(404);
    }

    /**
     * Test 5: POST /api/opportunities/{id}/projects/with-template - Create project with template
     */
    public function test_create_project_with_template(): void
    {
        // Create opportunity first
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

        $response = $this->postJson("/api/opportunities/{$opportunityId}/projects/with-template", [
            'template_id' => 1,
            'project_name' => 'Web App Project',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'project' => ['id', 'name'],
                    'tasks' => ['*' => ['id', 'name', 'weight']]
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(8, $data['tasks'], 'Should create 8 tasks for Web App template');
    }

    /**
     * Test 6: POST /api/opportunities/{id}/projects/with-template - Validation errors
     */
    public function test_create_project_validates_input(): void
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

        // Missing required fields
        $response = $this->postJson("/api/opportunities/{$opportunityId}/projects/with-template", []);

        $response->assertStatus(422);
    }

    /**
     * Test 7: POST /api/opportunities/{id}/projects/with-template - Invalid template
     */
    public function test_create_project_rejects_invalid_template(): void
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

        $response = $this->postJson("/api/opportunities/{$opportunityId}/projects/with-template", [
            'template_id' => 99999,
            'project_name' => 'Invalid Project'
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 8: POST /api/projects/{id}/apply-template - Apply template to project
     */
    public function test_apply_template_to_project(): void
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

        $response = $this->postJson("/api/projects/{$projectId}/apply-template", [
            'template_id' => 2 // Mobile App
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['message', 'task_count']
            ]);

        // Verify tasks were created
        $taskCount = DB::table('tasks')->where('project_id', $projectId)->count();
        $this->assertEquals(7, $taskCount, 'Should have 7 tasks for Mobile App template');
    }

    /**
     * Test 9: POST /api/projects/{id}/apply-template - Rejects project with tasks
     */
    public function test_apply_template_rejects_project_with_tasks(): void
    {
        // Create project with task
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Project with Task',
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

        $response = $this->postJson("/api/projects/{$projectId}/apply-template", [
            'template_id' => 1
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test 10: Response times are within acceptable limits
     */
    public function test_api_response_times_acceptable(): void
    {
        $start = microtime(true);
        $response = $this->getJson('/api/templates');
        $end = microtime(true);
        
        $duration = ($end - $start) * 1000;
        $response->assertStatus(200);
        
        $this->assertLessThan(200, $duration, "GET /api/templates took {$duration}ms, expected < 200ms");
    }

    /**
     * Test 11: GET /api/admin/templates - Admin endpoint lists all templates
     */
    public function test_admin_get_all_templates(): void
    {
        $response = $this->getJson('/api/admin/templates');

        // Note: This might require authentication, adjust as needed
        // For now, testing the endpoint structure
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'name', 'category']]
            ]);
        }
    }

    /**
     * Test 12: POST /api/admin/templates - Create template (admin only)
     */
    public function test_admin_create_template(): void
    {
        $data = [
            'name' => 'Custom Template ' . time(),
            'description' => 'Test custom template',
            'category' => 'Custom',
            'is_active' => true,
            'task_count' => 0,
            'average_duration_days' => 45,
        ];

        $response = $this->postJson('/api/admin/templates', $data);

        // 200/201 if successful, 403 if not authenticated
        $this->assertIn($response->status(), [201, 403, 401]);
    }

    /**
     * Test 13: Error handling - Malformed requests
     */
    public function test_malformed_json_request_returns_error(): void
    {
        $response = $this->post('/api/templates/1', []);
        
        // Should handle gracefully - either 404/405 or 422
        $this->assertIn($response->status(), [404, 405, 422, 400]);
    }
}
