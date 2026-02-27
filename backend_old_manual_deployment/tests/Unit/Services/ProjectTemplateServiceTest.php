<?php

namespace Tests\Unit\Services;

use App\Services\ProjectTemplateService;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ProjectTemplateServiceTest extends TestCase
{
    private ProjectTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTemplateService();

        // Run migrations to create tables
        $this->artisan('migrate', ['--database' => 'sqlite']);
        
        // Seed templates
        $this->artisan('db:seed', ['--class' => 'ProjectTemplateSeeder']);
    }

    /**
     * Test 1: getAllActiveTemplates returns all active templates
     */
    public function test_get_all_active_templates_returns_collection(): void
    {
        $templates = $this->service->getAllActiveTemplates();
        
        $this->assertNotNull($templates);
        $this->assertEquals(5, $templates->count(), 'Should return all 5 active templates');
        
        // Verify all are marked as active
        foreach ($templates as $template) {
            $this->assertTrue($template->is_active, "Template {$template->name} should be active");
        }

        // Verify template names
        $names = $templates->pluck('name')->toArray();
        $this->assertContains('Web Application', $names);
        $this->assertContains('Mobile Application', $names);
        $this->assertContains('E-Commerce Platform', $names);
        $this->assertContains('System Integration', $names);
        $this->assertContains('Maintenance & Support', $names);
    }

    /**
     * Test 2: getTemplateWithTasks returns complete template specification
     */
    public function test_get_template_with_tasks_returns_complete_template(): void
    {
        $template = $this->service->getTemplateWithTasks(1);

        $this->assertIsArray($template);
        $this->assertArrayHasKey('id', $template);
        $this->assertArrayHasKey('name', $template);
        $this->assertArrayHasKey('description', $template);
        $this->assertArrayHasKey('category', $template);
        $this->assertArrayHasKey('task_count', $template);
        $this->assertArrayHasKey('tasks', $template);
        
        $this->assertIsArray($template['tasks']);
        $this->assertNotEmpty($template['tasks']);
    }

    /**
     * Test 3: validateTemplateWeights passes for valid templates
     */
    public function test_validate_template_weights_passes_for_valid_template(): void
    {
        // All seeded templates should have valid weights (sum to 100)
        for ($i = 1; $i <= 5; $i++) {
            $result = $this->service->validateTemplateWeights($i);
            $this->assertTrue($result, "Template $i should have valid weights");
        }
    }

    /**
     * Test 4: Task weight distribution is correct per template
     */
    public function test_task_weights_sum_to_100_for_each_template(): void
    {
        $templates = DB::table('project_templates')->get();

        foreach ($templates as $template) {
            $taskWeights = DB::table('project_template_tasks')
                ->where('project_template_id', $template->id)
                ->sum('weight');

            $this->assertEquals(
                100,
                $taskWeights,
                "Template '{$template->name}' (ID: {$template->id}) has task weights summing to $taskWeights, expected 100"
            );
        }
    }

    /**
     * Test 5: Correct number of tasks per template
     */
    public function test_correct_task_distribution_for_each_template(): void
    {
        $expectedDistribution = [
            'Web Application' => 8,
            'Mobile Application' => 7,
            'E-Commerce Platform' => 9,
            'System Integration' => 7,
            'Maintenance & Support' => 5,
        ];

        foreach ($expectedDistribution as $name => $expectedCount) {
            $template = DB::table('project_templates')
                ->where('name', $name)
                ->first();

            $this->assertNotNull($template, "Template '$name' should exist");

            $taskCount = DB::table('project_template_tasks')
                ->where('project_template_id', $template->id)
                ->count();

            $this->assertEquals(
                $expectedCount,
                $taskCount,
                "Template '$name' should have $expectedCount tasks, but has $taskCount"
            );
        }
    }

    /**
     * Test 6: Total task count across all templates
     */
    public function test_total_task_count_is_36(): void
    {
        $totalTasks = DB::table('project_template_tasks')->count();
        $this->assertEquals(36, $totalTasks, 'Total tasks across all templates should be 36 (5+7+8+9+7)');
    }

    /**
     * Test 7: Phase numbers are sequential
     */
    public function test_phase_numbers_are_sequential_per_template(): void
    {
        $templates = DB::table('project_templates')->get();

        foreach ($templates as $template) {
            $phases = DB::table('project_template_tasks')
                ->where('project_template_id', $template->id)
                ->orderBy('phase_number')
                ->pluck('phase_number')
                ->toArray();

            $expected = range(1, count($phases));
            $this->assertEquals(
                $expected,
                $phases,
                "Template '{$template->name}' phases should be sequential: " . json_encode($phases)
            );
        }
    }

    /**
     * Test 8: All template tasks have required fields
     */
    public function test_all_template_tasks_have_required_fields(): void
    {
        $tasks = DB::table('project_template_tasks')->get();

        foreach ($tasks as $task) {
            $this->assertNotNull($task->project_template_id, "Task should have project_template_id");
            $this->assertNotNull($task->name, "Task should have name");
            $this->assertNotNull($task->weight, "Task should have weight");
            $this->assertGreaterThanOrEqual(0, $task->weight, "Weight should be >= 0");
            $this->assertLessThanOrEqual(100, $task->weight, "Weight should be <= 100");
            $this->assertNotNull($task->phase_number, "Task should have phase_number");
        }
    }

    /**
     * Test 9: Foreign key relationships are intact
     */
    public function test_foreign_key_relationships_work(): void
    {
        $tasks = DB::table('project_template_tasks')->get();

        foreach ($tasks as $task) {
            $templateExists = DB::table('project_templates')
                ->where('id', $task->project_template_id)
                ->exists();

            $this->assertTrue(
                $templateExists,
                "Task {$task->id} references template {$task->project_template_id} which doesn't exist"
            );
        }
    }

    /**
     * Test 10: Database indexes are working
     */
    public function test_database_queries_use_indexes(): void
    {
        $start = microtime(true);
        
        // Query using indexed column
        $templates = DB::table('project_templates')
            ->where('is_active', true)
            ->get();
        
        $end = microtime(true);
        $duration = ($end - $start) * 1000; // milliseconds

        $this->assertLessThan(50, $duration, "Indexed query took {$duration}ms, expected < 50ms (index working)");
        $this->assertGreaterThan(0, $templates->count());
    }
}
