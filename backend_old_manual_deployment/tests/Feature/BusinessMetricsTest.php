<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Services\BusinessMetricsService;
use App\Services\GoalTrackingService;

/**
 * BusinessMetricsTest
 *
 * Integration tests for BusinessMetricsService, GoalTrackingService,
 * and the related API endpoints.
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-009
 */
class BusinessMetricsTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(); // Bypass all middleware for controller logic testing

        // Run migrations
        $this->artisan('migrate', ['--database' => 'sqlite']);

        // Create test user
        $this->userId = DB::table('users')->insertGetId([
            'email'         => 'metrics-test@example.com',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role'          => 'admin',
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    // =========================================================================
    // HELPER: create opportunity
    // =========================================================================

    private function createOpportunity(array $overrides = []): int
    {
        $defaults = [
            'client'              => 'Test Client',
            'description'         => 'Test Opportunity',
            'estimated_value'     => 10000,
            'currency'            => 'USD',
            'stage'               => 'lead',
            'probability'         => 30,
            'source'              => 'referral',
            'owner'               => $this->userId,
            'expected_close_date' => date('Y-m-d', strtotime('+30 days')),
            'created_at'          => now(),
            'updated_at'          => now(),
        ];

        return DB::table('opportunities')->insertGetId(array_merge($defaults, $overrides));
    }

    private function createProject(array $overrides = []): int
    {
        $defaults = [
            'name'              => 'Test Project',
            'client'            => 'Test Client',
            'contract_value'    => 10000,
            'contract_currency' => 'USD',
            'start_date'        => now()->format('Y-m-d'),
            'end_date'          => now()->addDays(60)->format('Y-m-d'),
            'status'            => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ];

        return DB::table('projects')->insertGetId(array_merge($defaults, $overrides));
    }

    // =========================================================================
    // TASK 1.3 — BusinessMetricsService
    // =========================================================================

    /**
     * @test
     */
    public function test_calculate_opportunity_conversion_rate(): void
    {
        // Create 2 won, 1 lost → conversion rate = 2/3 = 66.67%
        $this->createOpportunity(['stage' => 'won', 'probability' => 100]);
        $this->createOpportunity(['stage' => 'won', 'probability' => 100]);
        $this->createOpportunity(['stage' => 'lost', 'probability' => 0]);

        $service = app(BusinessMetricsService::class);
        $result  = $service->calculateOpportunityConversionRate('current_quarter');

        $this->assertEquals('opportunity_conversion_rate', $result['metric_type']);
        $this->assertEquals(3, $result['total_closed']);
        $this->assertEquals(2, $result['total_won']);
        $this->assertEquals(66.67, $result['conversion_rate']);
    }

    /**
     * @test
     */
    public function test_conversion_rate_returns_zero_when_no_closed_opportunities(): void
    {
        // Only open opportunities exist — no closed ones
        $this->createOpportunity(['stage' => 'lead']);

        $service = app(BusinessMetricsService::class);
        $result  = $service->calculateOpportunityConversionRate('current_quarter');

        $this->assertEquals(0, $result['total_closed']);
        $this->assertEquals(0.0, $result['conversion_rate']);
    }

    /**
     * @test
     */
    public function test_calculate_sales_velocity(): void
    {
        $this->createOpportunity([
            'stage'          => 'won',
            'probability'    => 100,
            'estimated_value'=> 50000,
            'created_at'     => now()->subDays(30),
            'updated_at'     => now(),
        ]);

        $service = app(BusinessMetricsService::class);
        $result  = $service->calculateSalesVelocity('current_quarter');

        $this->assertEquals('sales_velocity', $result['metric_type']);
        $this->assertEquals(1, $result['won_count']);
        $this->assertEquals(50000.0, $result['average_deal_size']);
        $this->assertIsFloat($result['average_cycle_days']);
        $this->assertIsFloat($result['sales_velocity']);
    }

    /**
     * @test
     */
    public function test_sales_velocity_returns_zeros_when_no_won_opportunities(): void
    {
        $service = app(BusinessMetricsService::class);
        $result  = $service->calculateSalesVelocity('current_quarter');

        $this->assertEquals(0, $result['won_count']);
        $this->assertEquals(0.0, $result['sales_velocity']);
    }

    /**
     * @test
     */
    public function test_get_pipeline_value_by_stage(): void
    {
        // One lead, one proposal — both active and with future close date
        $this->createOpportunity(['stage' => 'lead', 'estimated_value' => 20000, 'probability' => 20]);
        $this->createOpportunity(['stage' => 'proposal', 'estimated_value' => 30000, 'probability' => 50]);

        $service = app(BusinessMetricsService::class);
        $result  = $service->getPipelineValueByStage();

        $this->assertEquals('pipeline_value_by_stage', $result['metric_type']);
        $this->assertEquals(50000.0, $result['total_pipeline_value']);
        $this->assertGreaterThan(0, $result['weighted_pipeline_value']);
        $this->assertCount(2, $result['by_stage']);
    }

    /**
     * @test
     */
    public function test_pipeline_excludes_lost_opportunities(): void
    {
        $this->createOpportunity(['stage' => 'lost', 'estimated_value' => 99999, 'probability' => 0]);

        $service = app(BusinessMetricsService::class);
        $result  = $service->getPipelineValueByStage();

        // Lost opportunity must not appear in pipeline
        $stages = array_column($result['by_stage'], 'stage');
        $this->assertNotContains('lost', $stages);
    }

    /**
     * @test
     */
    public function test_opportunity_to_project_conversion(): void
    {
        $oppId1 = $this->createOpportunity(['stage' => 'won', 'probability' => 100]);
        $oppId2 = $this->createOpportunity(['stage' => 'won', 'probability' => 100]);

        // Only first opportunity gets a project
        $this->createProject(['opportunity_id' => $oppId1]);

        $service = app(BusinessMetricsService::class);
        $result  = $service->calculateOpportunityToProjectConversion('current_quarter');

        $this->assertEquals('opportunity_to_project_conversion', $result['metric_type']);
        $this->assertEquals(2, $result['total_won']);
        $this->assertEquals(1, $result['converted_to_project']);
        $this->assertEquals(50.0, $result['conversion_rate']);
    }

    /**
     * @test
     */
    public function test_calculate_average_deal_size(): void
    {
        $this->createOpportunity(['stage' => 'won', 'estimated_value' => 10000, 'probability' => 100]);
        $this->createOpportunity(['stage' => 'won', 'estimated_value' => 30000, 'probability' => 100]);

        $service = app(BusinessMetricsService::class);
        $result  = $service->calculateAverageDealSize('current_quarter');

        $this->assertEquals('average_deal_size', $result['metric_type']);
        $this->assertEquals(2, $result['won_count']);
        $this->assertEquals(40000.0, $result['total_won_value']);
        $this->assertEquals(20000.0, $result['average_deal_size']);
    }

    /**
     * @test
     */
    public function test_calculate_stage_conversion_rates(): void
    {
        $this->createOpportunity(['stage' => 'lead']);
        $this->createOpportunity(['stage' => 'lead']);
        $this->createOpportunity(['stage' => 'qualified']);
        $this->createOpportunity(['stage' => 'proposal']);

        $service = app(BusinessMetricsService::class);
        $result  = $service->calculateStageConversionRates('current_quarter');

        $this->assertEquals('stage_conversion_rates', $result['metric_type']);
        $this->assertArrayHasKey('stage_counts', $result);
        $this->assertArrayHasKey('stage_conversions', $result);
        $this->assertIsArray($result['stage_conversions']);
    }

    // =========================================================================
    // TASK 1.4 — GoalTrackingService
    // =========================================================================

    /**
     * @test
     */
    public function test_create_and_get_goal(): void
    {
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal([
            'goal_type'    => 'revenue_target',
            'period'       => 'Q1_2026',
            'target_value' => 100000,
        ], $this->userId);

        $this->assertArrayHasKey('id', $goal);
        $this->assertEquals('revenue_target', $goal['goal_type']);
        $this->assertEquals(100000.0, $goal['target_value']);
        $this->assertEquals('active', $goal['status']);
    }

    /**
     * @test
     */
    public function test_get_active_goals_returns_list(): void
    {
        $service = app(GoalTrackingService::class);
        $service->createGoal(['goal_type' => 'revenue_target', 'period' => 'Q1_2026', 'target_value' => 100000], $this->userId);
        $service->createGoal(['goal_type' => 'deals_target',   'period' => 'Q1_2026', 'target_value' => 10],     $this->userId);

        $goals = $service->getActiveGoals();

        $this->assertCount(2, $goals);
    }

    /**
     * @test
     */
    public function test_calculate_goal_progress_on_track(): void
    {
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal([
            'goal_type'     => 'revenue_target',
            'period'        => 'Q1_2026',
            'target_value'  => 100000,
            'current_value' => 80000,
        ], $this->userId);

        $progress = $service->calculateGoalProgress($goal['id']);

        $this->assertEquals(80.0, $progress['progress_percentage']);
        $this->assertEquals('on_track', $progress['status']);
    }

    /**
     * @test
     */
    public function test_calculate_goal_progress_achieved(): void
    {
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal([
            'goal_type'     => 'revenue_target',
            'period'        => 'Q1_2026',
            'target_value'  => 100000,
            'current_value' => 120000,
        ], $this->userId);

        $progress = $service->calculateGoalProgress($goal['id']);

        $this->assertEquals(100.0, $progress['progress_percentage']);
        $this->assertEquals('achieved', $progress['status']);
    }

    /**
     * @test
     */
    public function test_calculate_goal_progress_behind(): void
    {
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal([
            'goal_type'     => 'revenue_target',
            'period'        => 'Q1_2026',
            'target_value'  => 100000,
            'current_value' => 10000,
        ], $this->userId);

        $progress = $service->calculateGoalProgress($goal['id']);

        $this->assertEquals(10.0, $progress['progress_percentage']);
        $this->assertEquals('behind', $progress['status']);
    }

    /**
     * @test
     */
    public function test_generate_prescriptive_actions_suggests_open_opportunities(): void
    {
        // Create a goal with a gap
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal([
            'goal_type'     => 'revenue_target',
            'period'        => 'Q1_2026',
            'target_value'  => 100000,
            'current_value' => 0,
        ], $this->userId);

        // Create an open opportunity that could close the gap
        $this->createOpportunity([
            'stage'           => 'proposal',
            'estimated_value' => 80000,
            'probability'     => 70,
        ]);

        $result = $service->generatePrescriptiveActions($goal['id']);

        $this->assertEquals($goal['id'], $result['goal_id']);
        $this->assertEquals(100000.0, $result['gap']);
        $this->assertNotEmpty($result['actions']);
    }

    /**
     * @test
     */
    public function test_update_goal_progress_persists_changes(): void
    {
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal([
            'goal_type'     => 'revenue_target',
            'period'        => 'Q1_2026',
            'target_value'  => 100000,
            'current_value' => 75000,
        ], $this->userId);

        $result = $service->updateGoalProgress();

        $this->assertEquals(1, $result['updated_count']);
        $this->assertCount(1, $result['goals']);
        $this->assertEquals(75.0, $result['goals'][0]['progress_percentage']);

        // Verify persisted in DB
        $dbGoal = DB::table('business_goals')->find($goal['id']);
        $this->assertEquals(75.0, (float) $dbGoal->progress_percentage);
    }

    /**
     * @test
     */
    public function test_get_goal_by_id_returns_null_for_missing(): void
    {
        $service = app(GoalTrackingService::class);
        $result  = $service->getGoalById(99999);

        $this->assertNull($result);
    }

    // =========================================================================
    // TASK 1.5 — BusinessMetricsController API endpoints
    // =========================================================================

    /**
     * @test
     */
    public function test_metrics_api_opportunity_conversion_rate_endpoint(): void
    {
        $this->createOpportunity(['stage' => 'won', 'probability' => 100]);
        $this->createOpportunity(['stage' => 'lost', 'probability' => 0]);

        $response = $this->getJson('/api/metrics/opportunity-conversion-rate?period=current_quarter');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'period',
                     'total_closed',
                     'total_won',
                     'conversion_rate',
                     'metric_type',
                 ]);
    }

    /**
     * @test
     */
    public function test_metrics_api_sales_velocity_endpoint(): void
    {
        $response = $this->getJson('/api/metrics/sales-velocity?period=current_quarter');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'period',
                     'won_count',
                     'average_deal_size',
                     'sales_velocity',
                     'metric_type',
                 ]);
    }

    /**
     * @test
     */
    public function test_metrics_api_pipeline_value_endpoint(): void
    {
        $response = $this->getJson('/api/metrics/pipeline-value');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'by_stage',
                     'total_pipeline_value',
                     'weighted_pipeline_value',
                     'metric_type',
                 ]);
    }

    /**
     * @test
     */
    public function test_metrics_api_opportunity_to_project_conversion_endpoint(): void
    {
        $response = $this->getJson('/api/metrics/opportunity-to-project-conversion?period=current_quarter');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'period',
                     'total_won',
                     'converted_to_project',
                     'conversion_rate',
                     'metric_type',
                 ]);
    }

    /**
     * @test
     */
    public function test_metrics_api_average_deal_size_endpoint(): void
    {
        $response = $this->getJson('/api/metrics/average-deal-size?period=current_quarter');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'period',
                     'won_count',
                     'total_won_value',
                     'average_deal_size',
                     'metric_type',
                 ]);
    }

    /**
     * @test
     */
    public function test_metrics_api_stage_conversion_rates_endpoint(): void
    {
        $response = $this->getJson('/api/metrics/stage-conversion-rates?period=current_quarter');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'period',
                     'stage_counts',
                     'stage_conversions',
                     'metric_type',
                 ]);
    }

    // =========================================================================
    // TASK 1.6 — GoalTrackingController API endpoints
    // =========================================================================

    /**
     * @test
     */
    public function test_goals_api_list_endpoint(): void
    {
        $service = app(GoalTrackingService::class);
        $service->createGoal(['goal_type' => 'revenue_target', 'period' => 'Q1_2026', 'target_value' => 100000], $this->userId);

        $response = $this->getJson('/api/goals');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'count']);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * @test
     */
    public function test_goals_api_create_endpoint(): void
    {
        $response = $this->postJson('/api/goals', [
            'goal_type'               => 'deals_target',
            'period'                  => 'Q1_2026',
            'target_value'            => 20,
            'authenticated_user_id'   => $this->userId,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['data' => ['id', 'goal_type', 'period', 'target_value', 'status']]);
    }

    /**
     * @test
     */
    public function test_goals_api_create_validates_required_fields(): void
    {
        $response = $this->postJson('/api/goals', [
            'authenticated_user_id' => $this->userId,
        ]);

        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function test_goals_api_show_endpoint(): void
    {
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal(['goal_type' => 'revenue_target', 'period' => 'Q1_2026', 'target_value' => 100000], $this->userId);

        $response = $this->getJson('/api/goals/' . $goal['id']);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['id', 'goal_type', 'status', 'progress_percentage']]);
    }

    /**
     * @test
     */
    public function test_goals_api_show_returns_404_for_missing(): void
    {
        $response = $this->getJson('/api/goals/99999');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function test_goals_api_prescriptive_actions_endpoint(): void
    {
        $service = app(GoalTrackingService::class);
        $goal    = $service->createGoal(['goal_type' => 'revenue_target', 'period' => 'Q1_2026', 'target_value' => 100000], $this->userId);

        $response = $this->getJson('/api/goals/' . $goal['id'] . '/prescriptive-actions');

        $response->assertStatus(200)
                 ->assertJsonStructure(['goal_id', 'gap', 'actions']);
    }

    /**
     * @test
     */
    public function test_goals_api_prescriptive_actions_returns_404_for_missing(): void
    {
        $response = $this->getJson('/api/goals/99999/prescriptive-actions');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function test_metrics_api_endpoints_accept_quarterly_period(): void
    {
        $response = $this->getJson('/api/metrics/average-deal-size?period=Q1_2026');

        $response->assertStatus(200);
        $this->assertEquals('Q1_2026', $response->json('period'));
    }

    /**
     * @test
     */
    public function test_metrics_api_endpoints_accept_monthly_period(): void
    {
        $response = $this->getJson('/api/metrics/average-deal-size?period=2026-02');

        $response->assertStatus(200);
        $this->assertEquals('2026-02', $response->json('period'));
    }

    /**
     * @test
     */
    public function test_metrics_dashboard_loads(): void
    {
        $response = $this->get('/metrics/dashboard');

        // Either renders view or redirects to login if auth required
        $this->assertContains($response->status(), [200, 302]);
    }
}
