<?php

namespace Tests\Unit\Services;

use App\Services\MilestoneManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for Milestone Management Service - Payment Prevention
 * 
 * Ensures that milestones cannot be marked as 'paid' directly.
 * Users must use ReceiveProjectPaymentService instead.
 */
class MilestoneManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private MilestoneManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MilestoneManagementService();
    }

    #[Test]
    public function it_prevents_direct_marking_as_paid()
    {
        // Arrange: Create a project
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
        ]);

        // Create a pending milestone
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'pending',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Try to directly mark as paid
        $result = $this->service->updateMilestone($milestoneId, [
            'status' => 'paid',
        ]);

        // Assert: Operation rejected
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Cannot mark milestone as paid directly', $result['message']);
        $this->assertStringContainsString('payment recording form', $result['message']);

        // Assert: Milestone status unchanged
        $milestone = DB::table('payment_milestones')->where('id', $milestoneId)->first();
        $this->assertEquals('pending', $milestone->status);
    }

    #[Test]
    public function it_allows_updating_to_invoiced()
    {
        // Arrange: Create a project
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
        ]);

        // Create a pending milestone
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'pending',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Update to invoiced (allowed)
        $result = $this->service->updateMilestone($milestoneId, [
            'status' => 'invoiced',
        ]);

        // Assert: Operation succeeded
        $this->assertTrue($result['success']);

        // Assert: Milestone status updated
        $milestone = DB::table('payment_milestones')->where('id', $milestoneId)->first();
        $this->assertEquals('invoiced', $milestone->status);
    }

    #[Test]
    public function it_prevents_editing_paid_milestones()
    {
        // Arrange: Create a project
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
        ]);

        // Create a paid milestone
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'paid',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Try to edit paid milestone
        $result = $this->service->updateMilestone($milestoneId, [
            'amount' => 6000,
        ]);

        // Assert: Operation rejected
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Cannot edit paid milestones', $result['message']);
        $this->assertStringContainsString('immutable', $result['message']);

        // Assert: Milestone unchanged
        $milestone = DB::table('payment_milestones')->where('id', $milestoneId)->first();
        $this->assertEquals(5000, $milestone->amount);
    }
}
