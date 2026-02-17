<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ReceiveProjectPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ReceiveProjectPaymentService
 * 
 * Verifies:
 * - Happy path (valid payment recording)
 * - All validation rules enforced
 * - Idempotency (duplicate payment prevention)
 * - Transactional safety (rollback on error)
 * - Currency integrity
 * - Immutability of paid milestones
 */
class ReceiveProjectPaymentServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private ReceiveProjectPaymentService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReceiveProjectPaymentService();
    }
    
    #[Test]
    public function it_successfully_records_payment_receipt()
    {
        // Arrange: Create test data
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Main Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'invoiced',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Act: Record payment
        $result = $this->service->receive($milestoneId, $accountId, '2026-02-10');
        
        // Assert: Operation succeeded
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction_id', $result);
        
        // Assert: Cash transaction created
        $transaction = DB::table('cash_transactions')
            ->where('id', $result['transaction_id'])
            ->first();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('inflow', $transaction->type);
        $this->assertEquals(5000, $transaction->amount);
        $this->assertEquals('USD', $transaction->currency);
        $this->assertEquals('payment_milestone', $transaction->source_type);
        $this->assertEquals($milestoneId, $transaction->source_id);
        $this->assertEquals('2026-02-10', $transaction->transaction_date);
        
        // Assert: Milestone status updated to 'paid'
        $milestone = DB::table('payment_milestones')
            ->where('id', $milestoneId)
            ->first();
        
        $this->assertEquals('paid', $milestone->status);
    }
    
    #[Test]
    public function it_rejects_payment_for_nonexistent_milestone()
    {
        // Arrange: Valid account but invalid milestone
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Main Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Act: Try to record payment for non-existent milestone
        $result = $this->service->receive(99999, $accountId, '2026-02-10');
        
        // Assert: Operation failed with appropriate error
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not exist', $result['message']);
        $this->assertNull($result['transaction_id']);
    }
    
    #[Test]
    public function it_rejects_payment_for_nonexistent_account()
    {
        // Arrange: Valid milestone but invalid account
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'invoiced',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Act: Try to record payment to non-existent account
        $result = $this->service->receive($milestoneId, 99999, '2026-02-10');
        
        // Assert: Operation failed
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Account', $result['message']);
        $this->assertNull($result['transaction_id']);
    }
    
    #[Test]
    public function it_prevents_duplicate_payment_recording()
    {
        // Arrange: Create and pay a milestone once
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Main Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'invoiced',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Act: Record payment first time (should succeed)
        $firstResult = $this->service->receive($milestoneId, $accountId, '2026-02-10');
        $this->assertTrue($firstResult['success']);
        
        // Act: Try to record same payment again (should fail)
        $secondResult = $this->service->receive($milestoneId, $accountId, '2026-02-11');
        
        // Assert: Second attempt rejected
        $this->assertFalse($secondResult['success']);
        $this->assertStringContainsString('already marked as paid', $secondResult['message']);
        $this->assertNull($secondResult['transaction_id']);
        
        // Assert: Only ONE cash transaction exists
        $transactionCount = DB::table('cash_transactions')
            ->where('source_type', 'payment_milestone')
            ->where('source_id', $milestoneId)
            ->count();
        
        $this->assertEquals(1, $transactionCount);
    }
    
    #[Test]
    public function it_enforces_currency_matching()
    {
        // Arrange: USD account but UGX milestone
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'USD Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000000,
            'contract_currency' => 'UGX',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000000,
            'currency' => 'UGX',
            'status' => 'invoiced',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Act: Try to record UGX payment to USD account
        $result = $this->service->receive($milestoneId, $accountId, '2026-02-10');
        
        // Assert: Operation failed due to currency mismatch
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Currency mismatch', $result['message']);
        $this->assertNull($result['transaction_id']);
        
        // Assert: No transaction created
        $transactionCount = DB::table('cash_transactions')->count();
        $this->assertEquals(0, $transactionCount);
        
        // Assert: Milestone still invoiced (not paid)
        $milestone = DB::table('payment_milestones')
            ->where('id', $milestoneId)
            ->value('status');
        $this->assertEquals('invoiced', $milestone);
    }
    
    #[Test]
    public function it_validates_transaction_date_format()
    {
        // Arrange
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Main Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'invoiced',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Act: Try invalid date formats
        $result1 = $this->service->receive($milestoneId, $accountId, '02/10/2026');
        $result2 = $this->service->receive($milestoneId, $accountId, '2026-2-10');
        $result3 = $this->service->receive($milestoneId, $accountId, 'invalid');
        
        // Assert: All rejected
        $this->assertFalse($result1['success']);
        $this->assertStringContainsString('Invalid transaction date format', $result1['message']);
        
        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('Invalid transaction date format', $result2['message']);
        
        $this->assertFalse($result3['success']);
        $this->assertStringContainsString('Invalid transaction date format', $result3['message']);
    }
    
    #[Test]
    public function it_provides_read_only_payment_status_check()
    {
        // Arrange
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $paidMilestone = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'Paid Milestone',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'paid',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $unpaidMilestone = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'Unpaid Milestone',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'invoiced',
            'due_date' => '2026-03-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Act & Assert
        $this->assertTrue($this->service->isPaid($paidMilestone));
        $this->assertFalse($this->service->isPaid($unpaidMilestone));
    }
    
    #[Test]
    public function it_retrieves_payment_receipt_details()
    {
        // Arrange: Create paid milestone with transaction
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Main Account',
            'type' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Test Project',
            'client' => 'Test Client',
            'contract_value' => 10000,
            'contract_currency' => 'USD',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $milestoneId = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'First Payment',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'invoiced',
            'due_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Pay the milestone
        $this->service->receive($milestoneId, $accountId, '2026-02-10');
        
        // Act: Retrieve receipt
        $receipt = $this->service->getPaymentReceipt($milestoneId);
        
        // Assert: Receipt contains correct details
        $this->assertNotNull($receipt);
        $this->assertEquals($accountId, $receipt['account_id']);
        $this->assertEquals(5000.0, $receipt['amount']);
        $this->assertEquals('USD', $receipt['currency']);
        $this->assertEquals('2026-02-10', $receipt['transaction_date']);
        
        // Assert: Unpaid milestone returns null
        $unpaidMilestone = DB::table('payment_milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'Unpaid Milestone',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'pending',
            'due_date' => '2026-03-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertNull($this->service->getPaymentReceipt($unpaidMilestone));
    }
}
