<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Employee\Employee;
use App\Models\Project\Project;
use App\Models\Project\ProjectAssignment;
use App\Models\Customer\Customer;
use App\Models\Vendor\Vendor;
use App\Models\Employee\TimeEntry;
use App\Models\Financial\FinancialRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class BusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    // User Business Logic Tests
    /** @test */
    public function user_role_checking_methods_work_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isManager());
        $this->assertFalse($admin->isEmployee());

        $this->assertFalse($manager->isAdmin());
        $this->assertTrue($manager->isManager());
        $this->assertFalse($manager->isEmployee());

        $this->assertFalse($employee->isAdmin());
        $this->assertFalse($employee->isManager());
        $this->assertTrue($employee->isEmployee());
    }

    /** @test */
    public function user_updates_last_login_correctly()
    {
        $user = User::factory()->create([
            'last_login_at' => null,
            'last_login_ip' => null,
        ]);

        $testIp = '192.168.1.100';
        $user->updateLastLogin($testIp);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals($testIp, $user->last_login_ip);
        $this->assertTrue($user->last_login_at->isToday());
    }

    // Employee Business Logic Tests
    /** @test */
    public function employee_status_checking_methods_work_correctly()
    {
        $activeEmployee = Employee::factory()->create(['status' => 'active']);
        $terminatedEmployee = Employee::factory()->create(['status' => 'terminated']);
        $onLeaveEmployee = Employee::factory()->create(['status' => 'on-leave']);

        $this->assertTrue($activeEmployee->isActive());
        $this->assertFalse($activeEmployee->isTerminated());
        $this->assertFalse($activeEmployee->isOnLeave());

        $this->assertFalse($terminatedEmployee->isActive());
        $this->assertTrue($terminatedEmployee->isTerminated());
        $this->assertFalse($terminatedEmployee->isOnLeave());

        $this->assertFalse($onLeaveEmployee->isActive());
        $this->assertFalse($onLeaveEmployee->isTerminated());
        $this->assertTrue($onLeaveEmployee->isOnLeave());
    }

    /** @test */
    public function employee_calculates_years_of_service_correctly()
    {
        // Active employee - 2.5 years
        $activeEmployee = Employee::factory()->create([
            'hire_date' => Carbon::now()->subYears(2)->subMonths(6),
            'termination_date' => null,
        ]);

        // Terminated employee - exactly 1 year of service
        $terminatedEmployee = Employee::factory()->create([
            'hire_date' => Carbon::now()->subYears(2),
            'termination_date' => Carbon::now()->subYear(),
        ]);

        $this->assertEquals(2.5, $activeEmployee->years_of_service);
        $this->assertEquals(1.0, $terminatedEmployee->years_of_service);
    }

    /** @test */
    public function employee_review_due_logic_works_correctly()
    {
        $dueEmployee = Employee::factory()->create([
            'next_review_date' => Carbon::now()->subDay(),
        ]);
        $notDueEmployee = Employee::factory()->create([
            'next_review_date' => Carbon::now()->addMonth(),
        ]);
        $noDateEmployee = Employee::factory()->create([
            'next_review_date' => null,
        ]);

        $this->assertTrue($dueEmployee->isDueForReview());
        $this->assertFalse($notDueEmployee->isDueForReview());
        $this->assertFalse($noDateEmployee->isDueForReview());
    }

    /** @test */
    public function employee_allocation_calculations_work_correctly()
    {
        $employee = Employee::factory()->create();

        // Create active assignments
        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'allocation_percentage' => 60,
            'is_active' => true,
        ]);
        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'allocation_percentage' => 30,
            'is_active' => true,
        ]);
        // Inactive assignment - should not count
        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'allocation_percentage' => 50,
            'is_active' => false,
        ]);

        $this->assertEquals(90, $employee->total_allocation);
        $this->assertFalse($employee->isOverAllocated());

        // Add another assignment to make over-allocated
        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'allocation_percentage' => 20,
            'is_active' => true,
        ]);

        $employee->refresh();
        $this->assertEquals(110, $employee->total_allocation);
        $this->assertTrue($employee->isOverAllocated());
    }

    // Project Business Logic Tests
    /** @test */
    public function project_status_checking_methods_work_correctly()
    {
        $activeProject = Project::factory()->create(['status' => 'active']);
        $completedProject = Project::factory()->create(['status' => 'completed']);
        $onHoldProject = Project::factory()->create(['status' => 'on-hold']);
        $cancelledProject = Project::factory()->create(['status' => 'cancelled']);

        $this->assertTrue($activeProject->isActive());
        $this->assertFalse($activeProject->isCompleted());
        $this->assertFalse($activeProject->isOnHold());
        $this->assertFalse($activeProject->isCancelled());

        $this->assertTrue($completedProject->isCompleted());
        $this->assertTrue($onHoldProject->isOnHold());
        $this->assertTrue($cancelledProject->isCancelled());
    }

    /** @test */
    public function project_overdue_logic_works_correctly()
    {
        $overdueProject = Project::factory()->create([
            'end_date' => Carbon::now()->subDay(),
            'status' => 'active',
        ]);
        $onTimeProject = Project::factory()->create([
            'end_date' => Carbon::now()->addDay(),
            'status' => 'active',
        ]);
        $completedProject = Project::factory()->create([
            'end_date' => Carbon::now()->subDay(),
            'status' => 'completed',
        ]);

        $this->assertTrue($overdueProject->isOverdue());
        $this->assertFalse($onTimeProject->isOverdue());
        $this->assertFalse($completedProject->isOverdue()); // Completed projects are never overdue
    }

    /** @test */
    public function project_budget_calculations_work_correctly()
    {
        $overBudgetProject = Project::factory()->create([
            'budget' => 10000,
            'actual_cost' => 12000,
        ]);
        $underBudgetProject = Project::factory()->create([
            'budget' => 10000,
            'actual_cost' => 8000,
        ]);

        $this->assertTrue($overBudgetProject->isOverBudget());
        $this->assertFalse($underBudgetProject->isOverBudget());

        $this->assertEquals(2000, $overBudgetProject->budget_variance);
        $this->assertEquals(-2000, $underBudgetProject->budget_variance);

        $this->assertEquals(20.0, $overBudgetProject->budget_variance_percentage);
        $this->assertEquals(-20.0, $underBudgetProject->budget_variance_percentage);
    }

    /** @test */
    public function project_duration_calculations_work_correctly()
    {
        $project = Project::factory()->create([
            'start_date' => Carbon::parse('2023-01-01'),
            'end_date' => Carbon::parse('2023-01-31'),
            'actual_end_date' => Carbon::parse('2023-02-05'),
        ]);

        $this->assertEquals(30, $project->duration); // Planned duration
        $this->assertEquals(35, $project->actual_duration); // Actual duration
    }

    /** @test */
    public function project_financial_calculations_work_correctly()
    {
        $project = Project::factory()->create();

        // Add revenue records
        FinancialRecord::factory()->create([
            'project_id' => $project->id,
            'type' => 'revenue',
            'amount' => 15000,
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $project->id,
            'type' => 'revenue',
            'amount' => 5000,
        ]);

        // Add expense records
        FinancialRecord::factory()->create([
            'project_id' => $project->id,
            'type' => 'expense',
            'amount' => 8000,
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $project->id,
            'type' => 'expense',
            'amount' => 2000,
        ]);

        $this->assertEquals(20000, $project->total_revenue);
        $this->assertEquals(10000, $project->total_expenses);
        $this->assertEquals(10000, $project->profit);
        $this->assertEquals(50.0, $project->profit_margin);
    }

    /** @test */
    public function project_completion_percentage_updates_correctly()
    {
        $milestones = [
            ['name' => 'Phase 1', 'completed' => true],
            ['name' => 'Phase 2', 'completed' => true],
            ['name' => 'Phase 3', 'completed' => false],
            ['name' => 'Phase 4', 'completed' => false],
        ];

        $project = Project::factory()->create(['milestones' => $milestones]);
        $project->updateCompletionPercentage();

        $this->assertEquals(50.0, $project->fresh()->completion_percentage);
    }

    /** @test */
    public function project_archive_unarchive_works_correctly()
    {
        $project = Project::factory()->create([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        $project->archive();
        $project->refresh();

        $this->assertTrue($project->is_archived);
        $this->assertNotNull($project->archived_at);

        $project->unarchive();
        $project->refresh();

        $this->assertFalse($project->is_archived);
        $this->assertNull($project->archived_at);
    }

    // Customer Business Logic Tests
    /** @test */
    public function customer_status_checking_methods_work_correctly()
    {
        $activeCustomer = Customer::factory()->create(['status' => 'active']);
        $prospectCustomer = Customer::factory()->create(['status' => 'prospect']);
        $inactiveCustomer = Customer::factory()->create(['status' => 'inactive']);
        $formerCustomer = Customer::factory()->create(['status' => 'former']);

        $this->assertTrue($activeCustomer->isActive());
        $this->assertFalse($activeCustomer->isProspect());

        $this->assertTrue($prospectCustomer->isProspect());
        $this->assertFalse($prospectCustomer->isActive());

        $this->assertTrue($inactiveCustomer->isInactive());
        $this->assertTrue($formerCustomer->isFormer());
    }

    /** @test */
    public function customer_financial_logic_works_correctly()
    {
        $customerWithBalance = Customer::factory()->create([
            'outstanding_balance' => 5000,
            'credit_limit' => 10000,
        ]);
        $customerOverLimit = Customer::factory()->create([
            'outstanding_balance' => 12000,
            'credit_limit' => 10000,
        ]);

        $this->assertTrue($customerWithBalance->hasOutstandingBalance());
        $this->assertFalse($customerWithBalance->isOverCreditLimit());

        $this->assertTrue($customerOverLimit->hasOutstandingBalance());
        $this->assertTrue($customerOverLimit->isOverCreditLimit());
    }

    /** @test */
    public function customer_contract_expiration_logic_works_correctly()
    {
        $expiringSoonCustomer = Customer::factory()->create([
            'contract_end_date' => Carbon::now()->addDays(15),
        ]);
        $expiredCustomer = Customer::factory()->create([
            'contract_end_date' => Carbon::now()->subDay(),
        ]);
        $futureCustomer = Customer::factory()->create([
            'contract_end_date' => Carbon::now()->addMonths(6),
        ]);

        $this->assertTrue($expiringSoonCustomer->isContractExpiringSoon());
        $this->assertFalse($expiringSoonCustomer->isContractExpired());

        $this->assertFalse($expiredCustomer->isContractExpiringSoon());
        $this->assertTrue($expiredCustomer->isContractExpired());

        $this->assertFalse($futureCustomer->isContractExpiringSoon());
        $this->assertFalse($futureCustomer->isContractExpired());
    }

    // Time Entry Business Logic Tests
    /** @test */
    public function time_entry_status_checking_methods_work_correctly()
    {
        $approvedEntry = TimeEntry::factory()->create(['status' => 'approved']);
        $pendingEntry = TimeEntry::factory()->create(['status' => 'pending']);
        $rejectedEntry = TimeEntry::factory()->create(['status' => 'rejected']);

        $this->assertTrue($approvedEntry->isApproved());
        $this->assertFalse($approvedEntry->isPending());
        $this->assertFalse($approvedEntry->isRejected());

        $this->assertTrue($pendingEntry->isPending());
        $this->assertTrue($rejectedEntry->isRejected());
    }

    /** @test */
    public function time_entry_billable_logic_works_correctly()
    {
        $billableEntry = TimeEntry::factory()->create(['is_billable' => true]);
        $nonBillableEntry = TimeEntry::factory()->create(['is_billable' => false]);

        $this->assertTrue($billableEntry->isBillable());
        $this->assertFalse($nonBillableEntry->isBillable());
    }

    // Project Assignment Business Logic Tests
    /** @test */
    public function project_assignment_status_checking_works_correctly()
    {
        $activeAssignment = ProjectAssignment::factory()->create(['is_active' => true]);
        $inactiveAssignment = ProjectAssignment::factory()->create(['is_active' => false]);

        $this->assertTrue($activeAssignment->isActive());
        $this->assertFalse($inactiveAssignment->isActive());
    }

    /** @test */
    public function project_assignment_allocation_logic_works_correctly()
    {
        $normalAssignment = ProjectAssignment::factory()->create(['allocation_percentage' => 80]);
        $overAllocatedAssignment = ProjectAssignment::factory()->create(['allocation_percentage' => 120]);

        $this->assertFalse($normalAssignment->isOverAllocated());
        $this->assertTrue($overAllocatedAssignment->isOverAllocated());
    }

    // Financial Record Business Logic Tests
    /** @test */
    public function financial_record_type_checking_methods_work_correctly()
    {
        $revenueRecord = FinancialRecord::factory()->create(['type' => 'revenue']);
        $expenseRecord = FinancialRecord::factory()->create(['type' => 'expense']);

        $this->assertTrue($revenueRecord->isRevenue());
        $this->assertFalse($revenueRecord->isExpense());

        $this->assertTrue($expenseRecord->isExpense());
        $this->assertFalse($expenseRecord->isRevenue());
    }

    /** @test */
    public function financial_record_approval_logic_works_correctly()
    {
        $approvedRecord = FinancialRecord::factory()->create(['approval_status' => 'approved']);
        $pendingRecord = FinancialRecord::factory()->create(['approval_status' => 'pending']);
        $rejectedRecord = FinancialRecord::factory()->create(['approval_status' => 'rejected']);

        $this->assertTrue($approvedRecord->isApproved());
        $this->assertFalse($approvedRecord->isPending());
        $this->assertFalse($approvedRecord->isRejected());

        $this->assertTrue($pendingRecord->isPending());
        $this->assertTrue($rejectedRecord->isRejected());
    }

    /** @test */
    public function financial_record_recurring_logic_works_correctly()
    {
        $recurringRecord = FinancialRecord::factory()->create(['is_recurring' => true]);
        $oneTimeRecord = FinancialRecord::factory()->create(['is_recurring' => false]);

        $this->assertTrue($recurringRecord->isRecurring());
        $this->assertFalse($oneTimeRecord->isRecurring());
    }

    // Edge Cases and Complex Scenarios
    /** @test */
    public function employee_with_no_assignments_has_zero_allocation()
    {
        $employee = Employee::factory()->create();
        
        $this->assertEquals(0, $employee->total_allocation);
        $this->assertFalse($employee->isOverAllocated());
    }

    /** @test */
    public function project_with_no_budget_is_never_over_budget()
    {
        $project = Project::factory()->create([
            'budget' => null,
            'actual_cost' => 50000,
        ]);

        $this->assertFalse($project->isOverBudget());
        $this->assertNull($project->budget_variance);
        $this->assertNull($project->budget_variance_percentage);
    }

    /** @test */
    public function project_with_zero_revenue_has_null_profit_margin()
    {
        $project = Project::factory()->create();
        
        // Add only expenses, no revenue
        FinancialRecord::factory()->create([
            'project_id' => $project->id,
            'type' => 'expense',
            'amount' => 5000,
        ]);

        $this->assertEquals(0, $project->total_revenue);
        $this->assertEquals(5000, $project->total_expenses);
        $this->assertEquals(-5000, $project->profit);
        $this->assertNull($project->profit_margin);
    }

    /** @test */
    public function customer_with_no_credit_limit_is_never_over_limit()
    {
        $customer = Customer::factory()->create([
            'credit_limit' => null,
            'outstanding_balance' => 100000,
        ]);

        $this->assertFalse($customer->isOverCreditLimit());
    }
} 