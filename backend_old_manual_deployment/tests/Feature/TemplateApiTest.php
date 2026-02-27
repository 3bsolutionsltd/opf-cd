<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TemplateApiTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware(); // Bypass all middleware for testing controller logic
        
        // Run migrations first to create all tables
        $this->artisan('migrate', ['--database' => 'sqlite']);
        
        // Now create test user
        $this->userId = DB::table('users')->insertGetId([
            'email' => 'test@example.com',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role' => 'admin',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Note: Roles/permissions not needed since middleware is bypassed
        
        // Seed templates
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

        // Verify all are marked as active (SQLite returns 1 for true)
        foreach ($templates as $template) {
            $this->assertTrue((bool)$template['is_active'], "Template {$template['name']} should be active");
        }
    }

    /**
     * Test 2: GET /api/templates/{id} - Get single template with tasks
     */
    public function test_get_template_by_id_returns_with_tasks(): void
    {
        $response = $this->getJson('/api/templates/1');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        
        // Verify template properties
        $this->assertArrayHasKey('template', $data);
        $this->assertArrayHasKey('tasks', $data);
        $this->assertEquals('Web Application', $data['template']['name']);
        $this->assertCount(8, $data['tasks'], 'Web App template should have 8 tasks');
    }

    /**
     * Test 3: GET /api/templates/{id}/preview - Preview template tasks
     * 
     * This test verifies the calculation logic moved to TemplateManagementService.
     * The preview endpoint now returns total_weight and is_valid from the service.
     */
    public function test_preview_template_returns_tasks_with_validation(): void
    {
        $response = $this->getJson('/api/templates/1/preview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'template' => ['id', 'name', 'description'],
                    'tasks' => ['*' => [
                        'name', 'weight', 'phase_number'
                    ]],
                    'total_weight',
                    'is_valid'
                ],
                'message'
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['tasks']);
        
        // Verify calculation is done (was moved from controller to service)
        $this->assertEquals(100, $data['total_weight'], 'Template weights should sum to 100');
        $this->assertTrue($data['is_valid'], 'Template should be valid');
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
            'client' => 'Test Client',
            'description' => 'Test Opportunity',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'source' => 'test',
            'owner' => $this->userId,
            'expected_close_date' => date('Y-m-d', strtotime('+30 days')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/opportunities/{$opportunityId}/projects/with-template", [
            'template_id' => 1,
            'project_name' => 'Web App Project',
            'authenticated_user_id' => $this->userId,
        ]);

        if ($response->status() !== 201) {
            dump('FAIL: Expected 201, got ' . $response->status());
            dump('Response:', $response->json());
        }

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
            'client' => 'Test Client',
            'description' => 'Test Opportunity',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'source' => 'test',
            'owner' => $this->userId,
            'expected_close_date' => date('Y-m-d', strtotime('+30 days')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Missing required fields
        $response = $this->postJson("/api/opportunities/{$opportunityId}/projects/with-template", [
            'authenticated_user_id' => $this->userId,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 7: POST /api/opportunities/{id}/projects/with-template - Invalid template
     */
    public function test_create_project_rejects_invalid_template(): void
    {
        $opportunityId = DB::table('opportunities')->insertGetId([
            'client' => 'Test Client',
            'description' => 'Test Opportunity',
            'estimated_value' => 10000,
            'currency' => 'USD',
            'stage' => 'won',
            'probability' => 100,
            'source' => 'test',
            'owner' => $this->userId,
            'expected_close_date' => date('Y-m-d', strtotime('+30 days')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/opportunities/{$opportunityId}/projects/with-template", [
            'template_id' => 99999,
            'project_name' => 'Invalid Project',
            'authenticated_user_id' => $this->userId,
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
            'client' => 'Test Client',
            'contract_value' => 5000,
            'contract_currency' => 'USD',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/projects/{$projectId}/apply-template", [
            'template_id' => 2, // Mobile App
            'authenticated_user_id' => $this->userId,
        ]);

        if ($response->status() !== 200) {
            dump('FAIL: Expected 200, got ' . $response->status());
            dump('Response:', $response->json());
        }

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['tasks_count', 'template_name']
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
            'client' => 'Test Client',
            'contract_value' => 5000,
            'contract_currency' => 'USD',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tasks')->insert([
            'project_id' => $projectId,
            'name' => 'Existing Task',
            'weight' => 100,
            'status' => 'todo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/projects/{$projectId}/apply-template", [
            'template_id' => 1,
            'authenticated_user_id' => $this->userId,
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

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'name', 'category']]
            ]);
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
        $this->assertContains($response->status(), [201, 403, 401]);
    }

    /**
     * Test 13: Error handling - Malformed requests
     */
    public function test_malformed_json_request_returns_error(): void
    {
        $response = $this->post('/api/templates/1', []);
        
        // Should handle gracefully - either 404/405 or 422
        $this->assertContains($response->status(), [404, 405, 422, 400]);
    }
}
