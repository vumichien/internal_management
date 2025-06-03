<?php

namespace Tests\Unit;

use App\Models\Project\Project;
use App\Models\Project\ProjectAssignment;
use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use App\Models\Employee\TimeEntry;
use App\Models\Financial\FinancialRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::factory()->create();
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'project_id',
            'name',
            'description',
            'start_date',
            'end_date',
            'actual_end_date',
            'status',
            'priority',
            'budget',
            'actual_cost',
            'estimated_hours',
            'actual_hours',
            'currency',
            'customer_id',
            'project_manager_id',
            'category',
            'type',
            'completion_percentage',
            'billing_type',
            'hourly_rate',
            'is_billable',
            'custom_attributes',
            'milestones',
            'deliverables',
            'risk_level',
            'notes',
            'requirements',
            'is_archived',
            'archived_at',
        ];

        $this->assertEquals($fillable, $this->project->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $casts = [
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_end_date' => 'date',
            'budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'completion_percentage' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'is_billable' => 'boolean',
            'is_archived' => 'boolean',
            'custom_attributes' => 'array',
            'milestones' => 'array',
            'deliverables' => 'array',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        foreach ($casts as $attribute => $cast) {
            $this->assertEquals($cast, $this->project->getCasts()[$attribute]);
        }
    }

    /** @test */
    public function it_casts_date_attributes()
    {
        $project = Project::factory()->create([
            'start_date' => '2023-01-15',
            'end_date' => '2023-12-15',
            'actual_end_date' => '2023-11-30',
        ]);

        $this->assertInstanceOf(Carbon::class, $project->start_date);
        $this->assertInstanceOf(Carbon::class, $project->end_date);
        $this->assertInstanceOf(Carbon::class, $project->actual_end_date);
    }

    /** @test */
    public function it_casts_decimal_attributes()
    {
        $project = Project::factory()->create([
            'budget' => 10000.50,
            'actual_cost' => 9500.75,
            'estimated_hours' => 100.25,
            'actual_hours' => 95.50,
        ]);

        $this->assertEquals('10000.50', $project->budget);
        $this->assertEquals('9500.75', $project->actual_cost);
        $this->assertEquals('100.25', $project->estimated_hours);
        $this->assertEquals('95.50', $project->actual_hours);
    }

    /** @test */
    public function it_casts_array_attributes()
    {
        $milestones = [['name' => 'Phase 1', 'completed' => true]];
        $deliverables = ['Website', 'Mobile App'];
        $customAttributes = ['priority_level' => 'high'];

        $project = Project::factory()->create([
            'milestones' => $milestones,
            'deliverables' => $deliverables,
            'custom_attributes' => $customAttributes,
        ]);

        $this->assertIsArray($project->milestones);
        $this->assertIsArray($project->deliverables);
        $this->assertIsArray($project->custom_attributes);
        $this->assertEquals($milestones, $project->milestones);
        $this->assertEquals($deliverables, $project->deliverables);
        $this->assertEquals($customAttributes, $project->custom_attributes);
    }

    /** @test */
    public function it_belongs_to_customer()
    {
        $customer = Customer::factory()->create();
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $project->customer);
        $this->assertEquals($customer->id, $project->customer->id);
    }

    /** @test */
    public function it_belongs_to_project_manager()
    {
        $manager = Employee::factory()->create();
        $project = Project::factory()->create(['project_manager_id' => $manager->id]);

        $this->assertInstanceOf(Employee::class, $project->projectManager);
        $this->assertEquals($manager->id, $project->projectManager->id);
    }

    /** @test */
    public function it_has_many_project_assignments()
    {
        $assignments = ProjectAssignment::factory()->count(3)->create(['project_id' => $this->project->id]);

        $this->assertCount(3, $this->project->projectAssignments);
        $this->assertInstanceOf(ProjectAssignment::class, $this->project->projectAssignments->first());
    }

    /** @test */
    public function it_has_many_time_entries()
    {
        $timeEntries = TimeEntry::factory()->count(2)->create(['project_id' => $this->project->id]);

        $this->assertCount(2, $this->project->timeEntries);
        $this->assertInstanceOf(TimeEntry::class, $this->project->timeEntries->first());
    }

    /** @test */
    public function it_has_many_financial_records()
    {
        $records = FinancialRecord::factory()->count(2)->create(['project_id' => $this->project->id]);

        $this->assertCount(2, $this->project->financialRecords);
        $this->assertInstanceOf(FinancialRecord::class, $this->project->financialRecords->first());
    }

    /** @test */
    public function it_belongs_to_many_employees()
    {
        $employee = Employee::factory()->create();
        ProjectAssignment::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $employee->id,
        ]);

        $this->assertCount(1, $this->project->employees);
        $this->assertInstanceOf(Employee::class, $this->project->employees->first());
    }

    /** @test */
    public function it_has_active_assignments()
    {
        ProjectAssignment::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'is_active' => true,
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => false,
        ]);

        $this->assertCount(2, $this->project->activeAssignments);
    }

    /** @test */
    public function it_has_revenue_records()
    {
        FinancialRecord::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'type' => 'revenue',
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'expense',
        ]);

        $this->assertCount(2, $this->project->revenues);
    }

    /** @test */
    public function it_has_expense_records()
    {
        FinancialRecord::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'type' => 'expense',
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'revenue',
        ]);

        $this->assertCount(2, $this->project->expenses);
    }

    /** @test */
    public function it_checks_if_project_is_active()
    {
        $activeProject = Project::factory()->create(['status' => 'active']);
        $inactiveProject = Project::factory()->create(['status' => 'completed']);

        $this->assertTrue($activeProject->isActive());
        $this->assertFalse($inactiveProject->isActive());
    }

    /** @test */
    public function it_checks_if_project_is_completed()
    {
        $completedProject = Project::factory()->create(['status' => 'completed']);
        $activeProject = Project::factory()->create(['status' => 'active']);

        $this->assertTrue($completedProject->isCompleted());
        $this->assertFalse($activeProject->isCompleted());
    }

    /** @test */
    public function it_checks_if_project_is_on_hold()
    {
        $onHoldProject = Project::factory()->create(['status' => 'on-hold']);
        $activeProject = Project::factory()->create(['status' => 'active']);

        $this->assertTrue($onHoldProject->isOnHold());
        $this->assertFalse($activeProject->isOnHold());
    }

    /** @test */
    public function it_checks_if_project_is_cancelled()
    {
        $cancelledProject = Project::factory()->create(['status' => 'cancelled']);
        $activeProject = Project::factory()->create(['status' => 'active']);

        $this->assertTrue($cancelledProject->isCancelled());
        $this->assertFalse($activeProject->isCancelled());
    }

    /** @test */
    public function it_checks_if_project_is_overdue()
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
        $this->assertFalse($completedProject->isOverdue());
    }

    /** @test */
    public function it_checks_if_project_is_over_budget()
    {
        $overBudgetProject = Project::factory()->create([
            'budget' => 10000,
            'actual_cost' => 12000,
        ]);
        $underBudgetProject = Project::factory()->create([
            'budget' => 10000,
            'actual_cost' => 8000,
        ]);
        $noBudgetProject = Project::factory()->create([
            'budget' => null,
            'actual_cost' => 5000,
        ]);

        $this->assertTrue($overBudgetProject->isOverBudget());
        $this->assertFalse($underBudgetProject->isOverBudget());
        $this->assertFalse($noBudgetProject->isOverBudget());
    }

    /** @test */
    public function it_calculates_project_duration()
    {
        $project = Project::factory()->create([
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(30),
        ]);

        $this->assertEquals(30, $project->duration);
    }

    /** @test */
    public function it_returns_null_duration_when_dates_missing()
    {
        $project = Project::factory()->create([
            'start_date' => null,
            'end_date' => Carbon::now()->addDays(30),
        ]);

        $this->assertNull($project->duration);
    }

    /** @test */
    public function it_calculates_actual_duration()
    {
        $project = Project::factory()->create([
            'start_date' => Carbon::now()->subDays(20),
            'actual_end_date' => Carbon::now(),
        ]);

        $this->assertEquals(20, $project->actual_duration);
    }

    /** @test */
    public function it_calculates_budget_variance()
    {
        $project = Project::factory()->create([
            'budget' => 10000,
            'actual_cost' => 12000,
        ]);

        $this->assertEquals(2000, $project->budget_variance);
    }

    /** @test */
    public function it_calculates_budget_variance_percentage()
    {
        $project = Project::factory()->create([
            'budget' => 10000,
            'actual_cost' => 12000,
        ]);

        $this->assertEquals(20.0, $project->budget_variance_percentage);
    }

    /** @test */
    public function it_calculates_hours_variance()
    {
        $project = Project::factory()->create([
            'estimated_hours' => 100,
            'actual_hours' => 120,
        ]);

        $this->assertEquals(20, $project->hours_variance);
    }

    /** @test */
    public function it_calculates_total_revenue()
    {
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'revenue',
            'amount' => 5000,
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'revenue',
            'amount' => 3000,
        ]);

        $this->assertEquals(8000, $this->project->total_revenue);
    }

    /** @test */
    public function it_calculates_total_expenses()
    {
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'expense',
            'amount' => 2000,
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'expense',
            'amount' => 1500,
        ]);

        $this->assertEquals(3500, $this->project->total_expenses);
    }

    /** @test */
    public function it_calculates_profit()
    {
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'revenue',
            'amount' => 10000,
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'expense',
            'amount' => 6000,
        ]);

        $this->assertEquals(4000, $this->project->profit);
    }

    /** @test */
    public function it_calculates_profit_margin()
    {
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'revenue',
            'amount' => 10000,
        ]);
        FinancialRecord::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'expense',
            'amount' => 6000,
        ]);

        $this->assertEquals(40.0, $this->project->profit_margin);
    }

    /** @test */
    public function it_returns_null_profit_margin_when_no_revenue()
    {
        $this->assertNull($this->project->profit_margin);
    }

    /** @test */
    public function it_generates_unique_project_id()
    {
        $id1 = Project::generateProjectId();
        $id2 = Project::generateProjectId();

        $this->assertStringStartsWith('PRJ', $id1);
        $this->assertStringStartsWith('PRJ', $id2);
        $this->assertNotEquals($id1, $id2);
        $this->assertEquals(8, strlen($id1)); // PRJ + 5 digits
    }

    /** @test */
    public function it_auto_generates_project_id_on_creation()
    {
        $project = Project::factory()->create(['project_id' => null]);

        $this->assertNotNull($project->project_id);
        $this->assertStringStartsWith('PRJ', $project->project_id);
    }

    /** @test */
    public function it_updates_completion_percentage_based_on_milestones()
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
    public function it_archives_project()
    {
        $this->project->archive();

        $this->project->refresh();
        $this->assertTrue($this->project->is_archived);
        $this->assertNotNull($this->project->archived_at);
    }

    /** @test */
    public function it_unarchives_project()
    {
        $this->project->update(['is_archived' => true, 'archived_at' => now()]);
        $this->project->unarchive();

        $this->project->refresh();
        $this->assertFalse($this->project->is_archived);
        $this->assertNull($this->project->archived_at);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $this->project->delete();

        $this->assertSoftDeleted($this->project);
        $this->assertNotNull($this->project->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete()
    {
        $this->project->delete();
        $this->project->restore();

        $this->assertNull($this->project->deleted_at);
        $this->assertDatabaseHas('projects', ['id' => $this->project->id, 'deleted_at' => null]);
    }
} 