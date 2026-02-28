<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LeadQualificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * LeadQualificationServiceTest
 *
 * Unit tests for BANT-based lead qualification scoring.
 *
 * Tests:
 * - HOT classification (score >= 70)
 * - WARM classification (score 40-69)
 * - COLD classification (score < 40)
 * - Score breakdown returned correctly
 * - Returns null for non-existent opportunity
 * - Default BANT field handling
 *
 * Source: docs/STRATEGIC_VISION_INTELLIGENT_OPERATIONS.md Section 2
 */
class LeadQualificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeadQualificationService $service;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeadQualificationService();

        $this->userId = DB::table('users')->insertGetId([
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOpportunity(array $overrides = []): int
    {
        return DB::table('opportunities')->insertGetId(array_merge([
            'client' => 'Test Client',
            'description' => 'Test Opportunity',
            'estimated_value' => 50000,
            'probability' => 50,
            'stage' => 'lead',
            'source' => 'referral',
            'owner' => $this->userId,
            'expected_close_date' => now()->addDays(60)->toDateString(),
            'qualification_score' => 0,
            'budget_confirmed' => 'unknown',
            'authority_level' => 'unknown',
            'need_validation' => 'unknown',
            'timeline_urgency' => 'unclear',
            'strategic_fit' => 'cold_lead',
            'disqualification_reason' => null,
            'last_contact_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /** @test */
    public function it_returns_null_for_non_existent_opportunity(): void
    {
        $result = $this->service->calculateQualificationScore(99999);

        $this->assertNull($result);
    }

    /** @test */
    public function it_classifies_hot_lead_with_maximum_scores(): void
    {
        // 25 + 20 + 15 + 20 + 20 = 100
        $id = $this->createOpportunity([
            'estimated_value' => 150000,     // 25 pts
            'strategic_fit'   => 'existing_client', // 20 pts
            'timeline_urgency' => 'immediate',       // 15 pts
            'authority_level' => 'decision_maker',   // 20 pts
            'need_validation' => 'critical',          // 20 pts
        ]);

        $result = $this->service->calculateQualificationScore($id);

        $this->assertNotNull($result);
        $this->assertEquals(100, $result['score']);
        $this->assertEquals('HOT', $result['classification']);
        $this->assertEquals('qualify', $result['recommendation']['action']);
        $this->assertEquals('HIGH', $result['recommendation']['priority']);
        $this->assertEquals('qualified', $result['recommendation']['suggested_stage']);
    }

    /** @test */
    public function it_classifies_warm_lead_with_medium_scores(): void
    {
        // 15 + 15 + 10 + 10 + 5 = 55
        $id = $this->createOpportunity([
            'estimated_value' => 75000,     // 15 pts
            'strategic_fit'   => 'referral', // 15 pts
            'timeline_urgency' => 'this_quarter', // 10 pts
            'authority_level' => 'influencer',    // 10 pts
            'need_validation' => 'unknown',        // 5 pts (default)
        ]);

        $result = $this->service->calculateQualificationScore($id);

        $this->assertNotNull($result);
        $this->assertGreaterThanOrEqual(40, $result['score']);
        $this->assertLessThanOrEqual(69, $result['score']);
        $this->assertEquals('WARM', $result['classification']);
        $this->assertEquals('review', $result['recommendation']['action']);
        $this->assertEquals('MEDIUM', $result['recommendation']['priority']);
    }

    /** @test */
    public function it_classifies_cold_lead_with_minimum_scores(): void
    {
        // 5 + 5 + 5 + 0 + 5 = 20
        $id = $this->createOpportunity([
            'estimated_value' => 10000,     // 5 pts
            'strategic_fit'   => 'cold_lead', // 5 pts
            'timeline_urgency' => 'unclear',   // 5 pts (default)
            'authority_level' => 'unknown',    // 0 pts
            'need_validation' => 'unknown',    // 5 pts (default)
        ]);

        $result = $this->service->calculateQualificationScore($id);

        $this->assertNotNull($result);
        $this->assertLessThan(40, $result['score']);
        $this->assertEquals('COLD', $result['classification']);
        $this->assertEquals('nurture_or_disqualify', $result['recommendation']['action']);
        $this->assertEquals('LOW', $result['recommendation']['priority']);
    }

    /** @test */
    public function it_returns_score_breakdown_with_all_categories(): void
    {
        $id = $this->createOpportunity();

        $result = $this->service->calculateQualificationScore($id);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertArrayHasKey('contract_value', $result['breakdown']);
        $this->assertArrayHasKey('strategic_fit', $result['breakdown']);
        $this->assertArrayHasKey('urgency', $result['breakdown']);
        $this->assertArrayHasKey('authority', $result['breakdown']);
        $this->assertArrayHasKey('need', $result['breakdown']);

        foreach ($result['breakdown'] as $category) {
            $this->assertArrayHasKey('points', $category);
            $this->assertArrayHasKey('reason', $category);
        }
    }

    /** @test */
    public function it_scores_contract_value_correctly(): void
    {
        // >$100K = 25 pts
        $id1 = $this->createOpportunity(['estimated_value' => 120000, 'client' => 'Client A']);
        $result1 = $this->service->calculateQualificationScore($id1);
        $this->assertEquals(25, $result1['breakdown']['contract_value']['points']);

        // $50K-$100K = 15 pts
        $id2 = $this->createOpportunity(['estimated_value' => 60000, 'client' => 'Client B']);
        $result2 = $this->service->calculateQualificationScore($id2);
        $this->assertEquals(15, $result2['breakdown']['contract_value']['points']);

        // $25K-$50K = 10 pts
        $id3 = $this->createOpportunity(['estimated_value' => 30000, 'client' => 'Client C']);
        $result3 = $this->service->calculateQualificationScore($id3);
        $this->assertEquals(10, $result3['breakdown']['contract_value']['points']);

        // <$25K = 5 pts
        $id4 = $this->createOpportunity(['estimated_value' => 10000, 'client' => 'Client D']);
        $result4 = $this->service->calculateQualificationScore($id4);
        $this->assertEquals(5, $result4['breakdown']['contract_value']['points']);
    }

    /** @test */
    public function it_returns_result_with_required_keys(): void
    {
        $id = $this->createOpportunity();

        $result = $this->service->calculateQualificationScore($id);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('classification', $result);
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertArrayHasKey('recommendation', $result);
        $this->assertArrayHasKey('action', $result['recommendation']);
        $this->assertArrayHasKey('priority', $result['recommendation']);
        $this->assertArrayHasKey('message', $result['recommendation']);
        $this->assertArrayHasKey('suggested_stage', $result['recommendation']);
    }
}
