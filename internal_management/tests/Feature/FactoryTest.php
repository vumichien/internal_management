<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Employee\Employee;
use App\Models\Customer\Customer;
use App\Models\Vendor\Vendor;
use App\Models\Project\Project;
use App\Models\Project\ProjectAssignment;
use App\Models\Employee\TimeEntry;
use App\Models\Financial\FinancialRecord;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_creates_valid_user()
    {
        $user = User::factory()->create();
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
        
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
    }

    public function test_employee_factory_creates_valid_employee()
    {
        $employee = Employee::factory()->create();
        
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
        ]);
        
        $this->assertNotNull($employee->job_title);
        $this->assertNotNull($employee->department);
        $this->assertNotNull($employee->user_id);
        $this->assertNotNull($employee->email);
    }

    public function test_customer_factory_creates_valid_customer()
    {
        $customer = Customer::factory()->create();
        
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => $customer->company_name,
        ]);
        
        $this->assertNotNull($customer->company_name);
        $this->assertNotNull($customer->email);
    }

    public function test_vendor_factory_creates_valid_vendor()
    {
        $vendor = Vendor::factory()->create();
        
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'company_name' => $vendor->company_name,
        ]);
        
        $this->assertNotNull($vendor->company_name);
        $this->assertNotNull($vendor->email);
    }

    public function test_project_factory_creates_valid_project()
    {
        $project = Project::factory()->create();
        
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => $project->name,
        ]);
        
        $this->assertNotNull($project->name);
        $this->assertNotNull($project->status);
    }

    public function test_project_assignment_factory_creates_valid_assignment()
    {
        $assignment = ProjectAssignment::factory()->create();
        
        $this->assertDatabaseHas('project_assignments', [
            'id' => $assignment->id,
            'project_id' => $assignment->project_id,
            'employee_id' => $assignment->employee_id,
        ]);
        
        $this->assertNotNull($assignment->project_id);
        $this->assertNotNull($assignment->employee_id);
        $this->assertNotNull($assignment->allocation_percentage);
    }

    public function test_time_entry_factory_creates_valid_entry()
    {
        $timeEntry = TimeEntry::factory()->create();
        
        $this->assertDatabaseHas('time_entries', [
            'id' => $timeEntry->id,
            'employee_id' => $timeEntry->employee_id,
            'project_id' => $timeEntry->project_id,
        ]);
        
        $this->assertNotNull($timeEntry->employee_id);
        $this->assertNotNull($timeEntry->project_id);
        $this->assertNotNull($timeEntry->date);
    }

    public function test_financial_record_factory_creates_valid_record()
    {
        $financialRecord = FinancialRecord::factory()->create();
        
        $this->assertDatabaseHas('financial_records', [
            'id' => $financialRecord->id,
            'record_id' => $financialRecord->record_id,
        ]);
        
        $this->assertNotNull($financialRecord->record_id);
        $this->assertNotNull($financialRecord->type);
        $this->assertNotNull($financialRecord->amount);
        $this->assertNotNull($financialRecord->currency);
    }

    public function test_all_factories_work_together()
    {
        // Create related records
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $customer = Customer::factory()->create();
        $vendor = Vendor::factory()->create();
        $project = Project::factory()->create(['customer_id' => $customer->id]);
        
        // Create dependent records
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
        ]);
        
        $timeEntry = TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
        ]);
        
        $financialRecord = FinancialRecord::factory()->create([
            'project_id' => $project->id,
            'related_entity_type' => 'customer',
            'related_entity_id' => $customer->id,
        ]);
        
        // Verify all records exist (accounting for factory-created related records)
        $this->assertGreaterThanOrEqual(1, User::count());
        $this->assertGreaterThanOrEqual(1, Employee::count());
        $this->assertGreaterThanOrEqual(1, Customer::count());
        $this->assertGreaterThanOrEqual(1, Vendor::count());
        $this->assertGreaterThanOrEqual(1, Project::count());
        $this->assertDatabaseCount('project_assignments', 1);
        $this->assertDatabaseCount('time_entries', 1);
        $this->assertDatabaseCount('financial_records', 1);
    }
}
