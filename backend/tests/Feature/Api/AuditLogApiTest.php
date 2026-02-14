<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * AuditLogApiTest
 * 
 * Integration tests for audit log API endpoints.
 * 
 * Tests:
 * - GET /api/audit-logs
 * - GET /api/audit-logs (with entity filtering)
 * - GET /api/audit-logs (with action filtering)
 * - Response structure validation
 * - Authentication and authorization
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class AuditLogApiTest extends TestCase
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

    /** @test */
    public function it_requires_authentication_for_audit_logs()
    {
        $response = $this->getJson('/api/audit-logs');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_all_audit_logs_with_correct_structure()
    {
        DB::table('audit_logs')->insert([
            [
                'user_id' => $this->userId,
                'action' => 'create',
                'entity_type' => 'project',
                'entity_id' => 1,
                'entity_name' => 'Website Redesign',
                'old_values' => null,
                'new_values' => json_encode(['name' => 'Website Redesign']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Browser',
                'created_at' => now(),
            ],
            [
                'user_id' => $this->userId,
                'action' => 'update',
                'entity_type' => 'expense',
                'entity_id' => 5,
                'entity_name' => 'Office Rent',
                'old_values' => json_encode(['amount' => 1000]),
                'new_values' => json_encode(['amount' => 1200]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Browser',
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'audit_logs' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_name',
                        'action',
                        'entity_type',
                        'entity_id',
                        'entity_name',
                        'old_values',
                        'new_values',
                        'ip_address',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('audit_logs'));
    }

    /** @test */
    public function it_filters_audit_logs_by_entity_type()
    {
        DB::table('audit_logs')->insert([
            [
                'user_id' => $this->userId,
                'action' => 'create',
                'entity_type' => 'project',
                'entity_id' => 1,
                'entity_name' => 'Project A',
                'old_values' => null,
                'new_values' => json_encode(['name' => 'Project A']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'created_at' => now(),
            ],
            [
                'user_id' => $this->userId,
                'action' => 'create',
                'entity_type' => 'expense',
                'entity_id' => 1,
                'entity_name' => 'Expense A',
                'old_values' => null,
                'new_values' => json_encode(['amount' => 100]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/audit-logs?entity_type=project');

        $response->assertStatus(200);
        
        $logs = $response->json('audit_logs');
        $this->assertCount(1, $logs);
        $this->assertEquals('project', $logs[0]['entity_type']);
    }

    /** @test */
    public function it_filters_audit_logs_by_action()
    {
        DB::table('audit_logs')->insert([
            [
                'user_id' => $this->userId,
                'action' => 'create',
                'entity_type' => 'project',
                'entity_id' => 1,
                'entity_name' => 'Project A',
                'old_values' => null,
                'new_values' => json_encode(['name' => 'Project A']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'created_at' => now(),
            ],
            [
                'user_id' => $this->userId,
                'action' => 'delete',
                'entity_type' => 'project',
                'entity_id' => 2,
                'entity_name' => 'Project B',
                'old_values' => json_encode(['name' => 'Project B']),
                'new_values' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/audit-logs?action=delete');

        $response->assertStatus(200);
        
        $logs = $response->json('audit_logs');
        $this->assertCount(1, $logs);
        $this->assertEquals('delete', $logs[0]['action']);
    }

    /** @test */
    public function it_filters_audit_logs_by_entity_id()
    {
        DB::table('audit_logs')->insert([
            [
                'user_id' => $this->userId,
                'action' => 'create',
                'entity_type' => 'project',
                'entity_id' => 1,
                'entity_name' => 'Project 1',
                'old_values' => null,
                'new_values' => json_encode(['name' => 'Project 1']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'created_at' => now(),
            ],
            [
                'user_id' => $this->userId,
                'action' => 'update',
                'entity_type' => 'project',
                'entity_id' => 1,
                'entity_name' => 'Project 1',
                'old_values' => json_encode(['status' => 'active']),
                'new_values' => json_encode(['status' => 'completed']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'created_at' => now(),
            ],
            [
                'user_id' => $this->userId,
                'action' => 'create',
                'entity_type' => 'project',
                'entity_id' => 2,
                'entity_name' => 'Project 2',
                'old_values' => null,
                'new_values' => json_encode(['name' => 'Project 2']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/audit-logs?entity_type=project&entity_id=1');

        $response->assertStatus(200);
        
        $logs = $response->json('audit_logs');
        $this->assertCount(2, $logs);
        $this->assertEquals(1, $logs[0]['entity_id']);
        $this->assertEquals(1, $logs[1]['entity_id']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_logs_match_filters()
    {
        $response = $this->actingAs((object)['id' => $this->userId])
            ->getJson('/api/audit-logs?entity_type=nonexistent');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'audit_logs' => [],
            ]);
    }
}
