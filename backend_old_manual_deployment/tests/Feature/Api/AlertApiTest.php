<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * AlertApiTest
 * 
 * Integration tests for alert API endpoints.
 * 
 * Tests:
 * - GET /api/alerts
 * - GET /api/alerts/count
 * - POST /api/alerts/{id}/dismiss
 * - Authentication and authorization
 * - Response structure validation
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class AlertApiTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

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
    }

    #[Test]
    public function it_requires_authentication_for_alerts()
    {
        $response = $this->getJson('/api/alerts');
        $response->assertStatus(401);
    }

    #[Test]
    public function it_returns_active_alerts_only()
    {
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

        // Create dismissed alert
        DB::table('alerts')->insert([
            'type' => 'project_behind_schedule',
            'severity' => 'warning',
            'entity_type' => 'project',
            'entity_id' => 1,
            'message' => 'Dismissed alert',
            'is_dismissed' => true,
            'dismissed_at' => now(),
            'dismissed_by' => $this->userId,
            'created_at' => now(),
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'alerts' => [
                    '*' => [
                        'id',
                        'type',
                        'severity',
                        'entity_type',
                        'message',
                        'is_dismissed',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('alerts'));
    }

    #[Test]
    public function it_returns_alert_counts_by_severity()
    {
        DB::table('alerts')->insert([
            ['type' => 'low_cash_runway', 'severity' => 'critical', 'entity_type' => 'system', 
             'entity_id' => null, 'message' => 'Critical', 'is_dismissed' => false, 'created_at' => now()],
            ['type' => 'low_cash_runway', 'severity' => 'critical', 'entity_type' => 'system', 
             'entity_id' => null, 'message' => 'Critical 2', 'is_dismissed' => false, 'created_at' => now()],
            ['type' => 'project_behind_schedule', 'severity' => 'warning', 'entity_type' => 'project', 
             'entity_id' => 1, 'message' => 'Warning', 'is_dismissed' => false, 'created_at' => now()],
            ['type' => 'opportunity_closing_soon', 'severity' => 'info', 'entity_type' => 'opportunity', 
             'entity_id' => 1, 'message' => 'Info', 'is_dismissed' => false, 'created_at' => now()],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/alerts/count');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 4,
                'critical' => 2,
                'warning' => 1,
                'info' => 1,
            ]);
    }

    #[Test]
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

        $response = $this->actingAs((object)['id' => $this->userId])
            ->postJson("/api/alerts/{$alertId}/dismiss");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $alert = DB::table('alerts')->where('id', $alertId)->first();
        $this->assertTrue($alert->is_dismissed);
    }

    #[Test]
    public function it_returns_404_for_non_existent_alert()
    {
        $response = $this->actingAs((object)['id' => $this->userId])
            ->postJson('/api/alerts/9999/dismiss');

        $response->assertStatus(404);
    }
}
