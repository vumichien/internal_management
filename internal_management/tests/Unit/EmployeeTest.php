<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Employee\Employee;
use App\Models\Employee\TimeEntry;
use App\Models\Project\Project;
use App\Models\Project\ProjectAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'employee_id',
            'user_id',
            'job_title',
            'department',
            'hire_date',
            'termination_date',
            'salary',
            'employment_type',
            'manager_id',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
            'status',
            'notes',
            'benefits',
            'skills',
            'last_review_date',
            'next_review_date',
            'performance_rating',
        ];

        $this->assertEquals($fillable, $this->employee->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $casts = [
            'hire_date' => 'date',
            'termination_date' => 'date',
            'last_review_date' => 'date',
            'next_review_date' => 'date',
            'salary' => 'decimal:2',
            'performance_rating' => 'decimal:2',
            'benefits' => 'array',
            'skills' => 'array',
            'deleted_at' => 'datetime',
        ];

        foreach ($casts as $attribute => $cast) {
            $this->assertEquals($cast, $this->employee->getCasts()[$attribute]);
        }
    }

    /** @test */
    public function it_casts_date_attributes()
    {
        $employee = Employee::factory()->create([
            'hire_date' => '2023-01-15',
            'termination_date' => '2024-01-15',
            'last_review_date' => '2023-06-15',
            'next_review_date' => '2024-06-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $employee->hire_date);
        $this->assertInstanceOf(Carbon::class, $employee->termination_date);
        $this->assertInstanceOf(Carbon::class, $employee->last_review_date);
        $this->assertInstanceOf(Carbon::class, $employee->next_review_date);
    }

    /** @test */
    public function it_casts_array_attributes()
    {
        $benefits = ['health', 'dental', '401k'];
        $skills = ['PHP', 'Laravel', 'JavaScript'];
        
        $employee = Employee::factory()->create([
            'benefits' => $benefits,
            'skills' => $skills,
        ]);

        $this->assertIsArray($employee->benefits);
        $this->assertIsArray($employee->skills);
        $this->assertEquals($benefits, $employee->benefits);
        $this->assertEquals($skills, $employee->skills);
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->employee->user);
        $this->assertEquals($this->user->id, $this->employee->user->id);
    }

    /** @test */
    public function it_belongs_to_manager()
    {
        $manager = Employee::factory()->create();
        $employee = Employee::factory()->create(['manager_id' => $manager->id]);

        $this->assertInstanceOf(Employee::class, $employee->manager);
        $this->assertEquals($manager->id, $employee->manager->id);
    }

    /** @test */
    public function it_has_many_direct_reports()
    {
        $directReports = Employee::factory()->count(3)->create(['manager_id' => $this->employee->id]);

        $this->assertCount(3, $this->employee->directReports);
        $this->assertInstanceOf(Employee::class, $this->employee->directReports->first());
    }

    /** @test */
    public function it_has_many_project_assignments()
    {
        $assignments = ProjectAssignment::factory()->count(2)->create(['employee_id' => $this->employee->id]);

        $this->assertCount(2, $this->employee->projectAssignments);
        $this->assertInstanceOf(ProjectAssignment::class, $this->employee->projectAssignments->first());
    }

    /** @test */
    public function it_has_many_time_entries()
    {
        $timeEntries = TimeEntry::factory()->count(3)->create(['employee_id' => $this->employee->id]);

        $this->assertCount(3, $this->employee->timeEntries);
        $this->assertInstanceOf(TimeEntry::class, $this->employee->timeEntries->first());
    }

    /** @test */
    public function it_belongs_to_many_projects()
    {
        $project = Project::factory()->create();
        ProjectAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'project_id' => $project->id,
        ]);

        $this->assertCount(1, $this->employee->projects);
        $this->assertInstanceOf(Project::class, $this->employee->projects->first());
    }

    /** @test */
    public function it_has_active_project_assignments()
    {
        ProjectAssignment::factory()->count(2)->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);
        ProjectAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => false,
        ]);

        $this->assertCount(2, $this->employee->activeProjectAssignments);
    }

    /** @test */
    public function it_checks_if_employee_is_active()
    {
        $activeEmployee = Employee::factory()->create(['status' => 'active']);
        $inactiveEmployee = Employee::factory()->create(['status' => 'inactive']);

        $this->assertTrue($activeEmployee->isActive());
        $this->assertFalse($inactiveEmployee->isActive());
    }

    /** @test */
    public function it_checks_if_employee_is_terminated()
    {
        $terminatedEmployee = Employee::factory()->create(['status' => 'terminated']);
        $activeEmployee = Employee::factory()->create(['status' => 'active']);

        $this->assertTrue($terminatedEmployee->isTerminated());
        $this->assertFalse($activeEmployee->isTerminated());
    }

    /** @test */
    public function it_checks_if_employee_is_on_leave()
    {
        $onLeaveEmployee = Employee::factory()->create(['status' => 'on-leave']);
        $activeEmployee = Employee::factory()->create(['status' => 'active']);

        $this->assertTrue($onLeaveEmployee->isOnLeave());
        $this->assertFalse($activeEmployee->isOnLeave());
    }

    /** @test */
    public function it_gets_full_name_from_user()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('John Doe', $employee->full_name);
    }

    /** @test */
    public function it_gets_email_from_user()
    {
        $user = User::factory()->create(['email' => 'john@example.com']);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('john@example.com', $employee->email);
    }

    /** @test */
    public function it_gets_full_address()
    {
        $employee = Employee::factory()->create([
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
        ]);

        $expected = '123 Main St, Apt 4B, New York, NY, 10001, USA';
        $this->assertEquals($expected, $employee->full_address);
    }

    /** @test */
    public function it_gets_full_address_with_missing_fields()
    {
        $employee = Employee::factory()->create([
            'address_line_1' => '123 Main St',
            'address_line_2' => null,
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => null,
        ]);

        $expected = '123 Main St, New York, NY, 10001';
        $this->assertEquals($expected, $employee->full_address);
    }

    /** @test */
    public function it_calculates_years_of_service()
    {
        $hireDate = Carbon::now()->subYears(2)->subMonths(6);
        $employee = Employee::factory()->create(['hire_date' => $hireDate]);

        $this->assertEquals(2.5, $employee->years_of_service);
    }

    /** @test */
    public function it_calculates_years_of_service_for_terminated_employee()
    {
        $hireDate = Carbon::now()->subYears(3);
        $terminationDate = Carbon::now()->subYear();
        $employee = Employee::factory()->create([
            'hire_date' => $hireDate,
            'termination_date' => $terminationDate,
        ]);

        $this->assertEquals(2.0, $employee->years_of_service);
    }

    /** @test */
    public function it_checks_if_employee_is_due_for_review()
    {
        $dueEmployee = Employee::factory()->create([
            'next_review_date' => Carbon::now()->subDay(),
        ]);
        $notDueEmployee = Employee::factory()->create([
            'next_review_date' => Carbon::now()->addDay(),
        ]);
        $noDateEmployee = Employee::factory()->create([
            'next_review_date' => null,
        ]);

        $this->assertTrue($dueEmployee->isDueForReview());
        $this->assertFalse($notDueEmployee->isDueForReview());
        $this->assertFalse($noDateEmployee->isDueForReview());
    }

    /** @test */
    public function it_calculates_total_allocation()
    {
        ProjectAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'allocation_percentage' => 50,
            'is_active' => true,
        ]);
        ProjectAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'allocation_percentage' => 30,
            'is_active' => true,
        ]);
        ProjectAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'allocation_percentage' => 20,
            'is_active' => false, // Should not be included
        ]);

        $this->assertEquals(80, $this->employee->total_allocation);
    }

    /** @test */
    public function it_checks_if_employee_is_over_allocated()
    {
        ProjectAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'allocation_percentage' => 120,
            'is_active' => true,
        ]);

        $this->assertTrue($this->employee->isOverAllocated());
    }

    /** @test */
    public function it_checks_if_employee_is_not_over_allocated()
    {
        ProjectAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'allocation_percentage' => 80,
            'is_active' => true,
        ]);

        $this->assertFalse($this->employee->isOverAllocated());
    }

    /** @test */
    public function it_generates_unique_employee_id()
    {
        $id1 = Employee::generateEmployeeId();
        $id2 = Employee::generateEmployeeId();

        $this->assertStringStartsWith('EMP', $id1);
        $this->assertStringStartsWith('EMP', $id2);
        $this->assertNotEquals($id1, $id2);
        $this->assertEquals(8, strlen($id1)); // EMP + 5 digits
    }

    /** @test */
    public function it_auto_generates_employee_id_on_creation()
    {
        $employee = Employee::factory()->create(['employee_id' => null]);

        $this->assertNotNull($employee->employee_id);
        $this->assertStringStartsWith('EMP', $employee->employee_id);
    }

    /** @test */
    public function it_does_not_override_provided_employee_id()
    {
        $customId = 'CUSTOM123';
        $employee = Employee::factory()->create(['employee_id' => $customId]);

        $this->assertEquals($customId, $employee->employee_id);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $this->employee->delete();

        $this->assertSoftDeleted($this->employee);
        $this->assertNotNull($this->employee->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete()
    {
        $this->employee->delete();
        $this->employee->restore();

        $this->assertNull($this->employee->deleted_at);
        $this->assertDatabaseHas('employees', ['id' => $this->employee->id, 'deleted_at' => null]);
    }
} 