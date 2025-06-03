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
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    // User-Employee Relationship Tests
    /** @test */
    public function user_has_one_employee_relationship()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Employee::class, $user->employee);
        $this->assertEquals($employee->id, $user->employee->id);
        $this->assertEquals($user->id, $employee->user_id);
    }

    /** @test */
    public function employee_belongs_to_user_relationship()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $employee->user);
        $this->assertEquals($user->id, $employee->user->id);
        $this->assertEquals($user->name, $employee->user->name);
    }

    /** @test */
    public function user_can_have_no_employee()
    {
        $user = User::factory()->create();

        $this->assertNull($user->employee);
    }

    // Employee Hierarchical Relationships
    /** @test */
    public function employee_belongs_to_manager()
    {
        $manager = Employee::factory()->create();
        $employee = Employee::factory()->create(['manager_id' => $manager->id]);

        $this->assertInstanceOf(Employee::class, $employee->manager);
        $this->assertEquals($manager->id, $employee->manager->id);
    }

    /** @test */
    public function employee_has_many_direct_reports()
    {
        $manager = Employee::factory()->create();
        $directReports = Employee::factory()->count(3)->create(['manager_id' => $manager->id]);

        $this->assertInstanceOf(Collection::class, $manager->directReports);
        $this->assertCount(3, $manager->directReports);
        
        foreach ($directReports as $report) {
            $this->assertTrue($manager->directReports->contains($report));
        }
    }

    /** @test */
    public function employee_can_have_no_manager()
    {
        $employee = Employee::factory()->create(['manager_id' => null]);

        $this->assertNull($employee->manager);
    }

    // Project-Customer Relationships
    /** @test */
    public function project_belongs_to_customer()
    {
        $customer = Customer::factory()->create();
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $project->customer);
        $this->assertEquals($customer->id, $project->customer->id);
    }

    /** @test */
    public function customer_has_many_projects()
    {
        $customer = Customer::factory()->create();
        $projects = Project::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Collection::class, $customer->projects);
        $this->assertCount(3, $customer->projects);
        
        foreach ($projects as $project) {
            $this->assertTrue($customer->projects->contains($project));
        }
    }

    /** @test */
    public function customer_active_projects_relationship()
    {
        $customer = Customer::factory()->create();
        $activeProjects = Project::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'status' => 'active'
        ]);
        $completedProject = Project::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);

        $this->assertCount(2, $customer->activeProjects);
        $this->assertCount(1, $customer->completedProjects);
    }

    // Project-Employee Many-to-Many Relationships
    /** @test */
    public function project_belongs_to_many_employees_through_assignments()
    {
        $project = Project::factory()->create();
        $employees = Employee::factory()->count(3)->create();

        foreach ($employees as $employee) {
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
            ]);
        }

        $this->assertInstanceOf(Collection::class, $project->employees);
        $this->assertCount(3, $project->employees);
        
        foreach ($employees as $employee) {
            $this->assertTrue($project->employees->contains($employee));
        }
    }

    /** @test */
    public function employee_belongs_to_many_projects_through_assignments()
    {
        $employee = Employee::factory()->create();
        $projects = Project::factory()->count(2)->create();

        foreach ($projects as $project) {
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
            ]);
        }

        $this->assertInstanceOf(Collection::class, $employee->projects);
        $this->assertCount(2, $employee->projects);
        
        foreach ($projects as $project) {
            $this->assertTrue($employee->projects->contains($project));
        }
    }

    /** @test */
    public function project_assignment_pivot_data_is_accessible()
    {
        $project = Project::factory()->create();
        $employee = Employee::factory()->create();
        
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'role_on_project' => 'Lead Developer',
            'allocation_percentage' => 75,
            'is_active' => true,
        ]);

        $projectEmployee = $project->employees->first();
        $this->assertEquals('Lead Developer', $projectEmployee->pivot->role_on_project);
        $this->assertEquals(75, $projectEmployee->pivot->allocation_percentage);
        $this->assertTrue($projectEmployee->pivot->is_active);
    }

    // ProjectAssignment Direct Relationships
    /** @test */
    public function project_assignment_belongs_to_project_and_employee()
    {
        $project = Project::factory()->create();
        $employee = Employee::factory()->create();
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
        ]);

        $this->assertInstanceOf(Project::class, $assignment->project);
        $this->assertInstanceOf(Employee::class, $assignment->employee);
        $this->assertEquals($project->id, $assignment->project->id);
        $this->assertEquals($employee->id, $assignment->employee->id);
    }

    /** @test */
    public function project_has_many_project_assignments()
    {
        $project = Project::factory()->create();
        $assignments = ProjectAssignment::factory()->count(3)->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Collection::class, $project->projectAssignments);
        $this->assertCount(3, $project->projectAssignments);
    }

    /** @test */
    public function employee_has_many_project_assignments()
    {
        $employee = Employee::factory()->create();
        $assignments = ProjectAssignment::factory()->count(2)->create(['employee_id' => $employee->id]);

        $this->assertInstanceOf(Collection::class, $employee->projectAssignments);
        $this->assertCount(2, $employee->projectAssignments);
    }

    /** @test */
    public function project_active_assignments_relationship()
    {
        $project = Project::factory()->create();
        $activeAssignments = ProjectAssignment::factory()->count(2)->create([
            'project_id' => $project->id,
            'is_active' => true
        ]);
        $inactiveAssignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'is_active' => false
        ]);

        $this->assertCount(2, $project->activeAssignments);
    }

    // TimeEntry Relationships
    /** @test */
    public function time_entry_belongs_to_employee_and_project()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $timeEntry = TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
        ]);

        $this->assertInstanceOf(Employee::class, $timeEntry->employee);
        $this->assertInstanceOf(Project::class, $timeEntry->project);
        $this->assertEquals($employee->id, $timeEntry->employee->id);
        $this->assertEquals($project->id, $timeEntry->project->id);
    }

    /** @test */
    public function time_entry_belongs_to_approver()
    {
        $approver = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['approved_by' => $approver->id]);

        $this->assertInstanceOf(User::class, $timeEntry->approver);
        $this->assertEquals($approver->id, $timeEntry->approver->id);
    }

    /** @test */
    public function employee_has_many_time_entries()
    {
        $employee = Employee::factory()->create();
        $timeEntries = TimeEntry::factory()->count(5)->create(['employee_id' => $employee->id]);

        $this->assertInstanceOf(Collection::class, $employee->timeEntries);
        $this->assertCount(5, $employee->timeEntries);
    }

    /** @test */
    public function project_has_many_time_entries()
    {
        $project = Project::factory()->create();
        $timeEntries = TimeEntry::factory()->count(4)->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Collection::class, $project->timeEntries);
        $this->assertCount(4, $project->timeEntries);
    }

    /** @test */
    public function user_has_many_approved_time_entries()
    {
        $approver = User::factory()->create();
        $timeEntries = TimeEntry::factory()->count(3)->create(['approved_by' => $approver->id]);

        $this->assertInstanceOf(Collection::class, $approver->approvedTimeEntries);
        $this->assertCount(3, $approver->approvedTimeEntries);
    }

    // FinancialRecord Relationships
    /** @test */
    public function financial_record_belongs_to_project()
    {
        $project = Project::factory()->create();
        $record = FinancialRecord::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $record->project);
        $this->assertEquals($project->id, $record->project->id);
    }

    /** @test */
    public function financial_record_belongs_to_creator()
    {
        $user = User::factory()->create();
        $record = FinancialRecord::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $record->creator);
        $this->assertEquals($user->id, $record->creator->id);
    }

    /** @test */
    public function project_has_many_financial_records()
    {
        $project = Project::factory()->create();
        $records = FinancialRecord::factory()->count(6)->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Collection::class, $project->financialRecords);
        $this->assertCount(6, $project->financialRecords);
    }

    /** @test */
    public function project_revenue_and_expense_relationships()
    {
        $project = Project::factory()->create();
        $revenues = FinancialRecord::factory()->count(2)->create([
            'project_id' => $project->id,
            'type' => 'revenue'
        ]);
        $expenses = FinancialRecord::factory()->count(3)->create([
            'project_id' => $project->id,
            'type' => 'expense'
        ]);

        $this->assertCount(2, $project->revenues);
        $this->assertCount(3, $project->expenses);
    }

    /** @test */
    public function user_has_many_created_financial_records()
    {
        $user = User::factory()->create();
        $records = FinancialRecord::factory()->count(4)->create(['created_by' => $user->id]);

        $this->assertInstanceOf(Collection::class, $user->createdFinancialRecords);
        $this->assertCount(4, $user->createdFinancialRecords);
    }

    // Polymorphic Relationships
    /** @test */
    public function customer_has_many_financial_records_polymorphically()
    {
        $customer = Customer::factory()->create();
        $records = FinancialRecord::factory()->count(3)->create([
            'related_entity_type' => 'customer',
            'related_entity_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Collection::class, $customer->financialRecords);
        $this->assertCount(3, $customer->financialRecords);
    }

    // Cascade Delete Tests
    /** @test */
    public function deleting_user_soft_deletes_employee()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertSoftDeleted($user);
        // Employee should still exist but user relationship should be null
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    /** @test */
    public function deleting_project_soft_deletes_related_records()
    {
        $project = Project::factory()->create();
        $assignment = ProjectAssignment::factory()->create(['project_id' => $project->id]);
        $timeEntry = TimeEntry::factory()->create(['project_id' => $project->id]);
        $financialRecord = FinancialRecord::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertSoftDeleted($project);
        // Related records should still exist
        $this->assertDatabaseHas('project_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseHas('time_entries', ['id' => $timeEntry->id]);
        $this->assertDatabaseHas('financial_records', ['id' => $financialRecord->id]);
    }

    /** @test */
    public function deleting_employee_preserves_related_records()
    {
        $employee = Employee::factory()->create();
        $assignment = ProjectAssignment::factory()->create(['employee_id' => $employee->id]);
        $timeEntry = TimeEntry::factory()->create(['employee_id' => $employee->id]);

        $employee->delete();

        $this->assertSoftDeleted($employee);
        // Related records should still exist
        $this->assertDatabaseHas('project_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseHas('time_entries', ['id' => $timeEntry->id]);
    }

    // Eager Loading Tests
    /** @test */
    public function can_eager_load_user_with_employee()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $loadedUser = User::with('employee')->find($user->id);

        $this->assertTrue($loadedUser->relationLoaded('employee'));
        $this->assertEquals($employee->id, $loadedUser->employee->id);
    }

    /** @test */
    public function can_eager_load_project_with_all_relationships()
    {
        $customer = Customer::factory()->create();
        $manager = Employee::factory()->create();
        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'project_manager_id' => $manager->id,
        ]);
        
        $employee = Employee::factory()->create();
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
        ]);
        
        TimeEntry::factory()->create(['project_id' => $project->id]);
        FinancialRecord::factory()->create(['project_id' => $project->id]);

        $loadedProject = Project::with([
            'customer',
            'projectManager',
            'employees',
            'timeEntries',
            'financialRecords'
        ])->find($project->id);

        $this->assertTrue($loadedProject->relationLoaded('customer'));
        $this->assertTrue($loadedProject->relationLoaded('projectManager'));
        $this->assertTrue($loadedProject->relationLoaded('employees'));
        $this->assertTrue($loadedProject->relationLoaded('timeEntries'));
        $this->assertTrue($loadedProject->relationLoaded('financialRecords'));
    }

    // Complex Relationship Scenarios
    /** @test */
    public function employee_can_work_on_multiple_projects_with_different_roles()
    {
        $employee = Employee::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project1->id,
            'role_on_project' => 'Lead Developer',
            'allocation_percentage' => 60,
        ]);

        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project2->id,
            'role_on_project' => 'QA Tester',
            'allocation_percentage' => 40,
        ]);

        $this->assertCount(2, $employee->projects);
        $this->assertCount(2, $employee->projectAssignments);

        $leadRole = $employee->projectAssignments->where('project_id', $project1->id)->first();
        $qaRole = $employee->projectAssignments->where('project_id', $project2->id)->first();

        $this->assertEquals('Lead Developer', $leadRole->role_on_project);
        $this->assertEquals('QA Tester', $qaRole->role_on_project);
    }

    /** @test */
    public function project_can_have_multiple_financial_record_types()
    {
        $project = Project::factory()->create();

        FinancialRecord::factory()->count(2)->create([
            'project_id' => $project->id,
            'type' => 'revenue',
        ]);

        FinancialRecord::factory()->count(3)->create([
            'project_id' => $project->id,
            'type' => 'expense',
        ]);

        $this->assertCount(5, $project->financialRecords);
        $this->assertCount(2, $project->revenues);
        $this->assertCount(3, $project->expenses);
    }
} 