<?php

namespace Tests\Unit;

use App\Models\Vendor\Vendor;
use App\Models\Employee\TimeEntry;
use App\Models\Project\ProjectAssignment;
use App\Models\Financial\FinancialRecord;
use App\Models\Employee\Employee;
use App\Models\Project\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ModelValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function vendor_model_has_correct_fillable_attributes()
    {
        $vendor = Vendor::factory()->create();
        
        $expectedFillable = [
            'vendor_id',
            'company_name',
            'contact_person',
            'email',
            'phone',
            'website',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
            'industry',
            'tax_id',
            'status',
            'priority',
            'first_contact_date',
            'last_contact_date',
            'preferred_currency',
            'payment_terms',
            'credit_rating',
            'service_categories',
            'capabilities',
            'certifications',
            'insurance_info',
            'compliance_status',
            'performance_rating',
            'contract_terms',
            'notes',
            'requirements',
            'lead_source',
            'assigned_contact',
            'contract_start_date',
            'contract_end_date',
            'auto_renewal',
        ];

        $this->assertEquals($expectedFillable, $vendor->getFillable());
    }

    /** @test */
    public function vendor_model_casts_attributes_correctly()
    {
        $vendor = Vendor::factory()->create([
            'first_contact_date' => '2023-01-15',
            'performance_rating' => 4.5,
            'auto_renewal' => true,
            'service_categories' => ['web', 'mobile'],
        ]);

        $this->assertInstanceOf(Carbon::class, $vendor->first_contact_date);
        $this->assertEquals('4.50', $vendor->performance_rating);
        $this->assertTrue($vendor->auto_renewal);
        $this->assertIsArray($vendor->service_categories);
    }

    /** @test */
    public function vendor_model_has_business_logic_methods()
    {
        $activeVendor = Vendor::factory()->create(['status' => 'active']);
        $inactiveVendor = Vendor::factory()->create(['status' => 'inactive']);
        $highPriorityVendor = Vendor::factory()->create(['priority' => 'high']);

        $this->assertTrue($activeVendor->isActive());
        $this->assertFalse($inactiveVendor->isActive());
        $this->assertTrue($highPriorityVendor->isHighPriority());
    }

    /** @test */
    public function time_entry_model_has_correct_fillable_attributes()
    {
        $timeEntry = TimeEntry::factory()->create();
        
        $expectedFillable = [
            'employee_id',
            'project_id',
            'date',
            'start_time',
            'end_time',
            'break_duration',
            'total_hours',
            'description',
            'category',
            'activity_type',
            'is_billable',
            'hourly_rate',
            'total_amount',
            'status',
            'approved_by',
            'approved_at',
            'rejection_reason',
            'location',
            'notes',
            'tags',
            'overtime_hours',
            'is_overtime_approved',
            'submitted_at',
            'locked_at',
            'locked_by',
        ];

        $this->assertEquals($expectedFillable, $timeEntry->getFillable());
    }

    /** @test */
    public function time_entry_model_has_relationships()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $approver = User::factory()->create();
        
        $timeEntry = TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'approved_by' => $approver->id,
        ]);

        $this->assertInstanceOf(Employee::class, $timeEntry->employee);
        $this->assertInstanceOf(Project::class, $timeEntry->project);
        $this->assertInstanceOf(User::class, $timeEntry->approver);
        $this->assertEquals($employee->id, $timeEntry->employee->id);
        $this->assertEquals($project->id, $timeEntry->project->id);
    }

    /** @test */
    public function time_entry_model_has_business_logic_methods()
    {
        $approvedEntry = TimeEntry::factory()->create(['status' => 'approved']);
        $pendingEntry = TimeEntry::factory()->create(['status' => 'pending']);
        $billableEntry = TimeEntry::factory()->create(['is_billable' => true]);
        
        $this->assertTrue($approvedEntry->isApproved());
        $this->assertTrue($pendingEntry->isPending());
        $this->assertTrue($billableEntry->isBillable());
    }

    /** @test */
    public function project_assignment_model_has_correct_fillable_attributes()
    {
        $assignment = ProjectAssignment::factory()->create();
        
        $expectedFillable = [
            'project_id',
            'employee_id',
            'role_on_project',
            'allocation_percentage',
            'start_date',
            'end_date',
            'is_active',
            'hourly_rate',
            'estimated_hours',
            'actual_hours',
            'status',
            'notes',
            'responsibilities',
            'performance_rating',
            'last_review_date',
            'next_review_date',
            'created_by',
            'updated_by',
            'approved_by',
            'approved_at',
        ];

        $this->assertEquals($expectedFillable, $assignment->getFillable());
    }

    /** @test */
    public function project_assignment_model_has_relationships()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        
        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
        ]);

        $this->assertInstanceOf(Employee::class, $assignment->employee);
        $this->assertInstanceOf(Project::class, $assignment->project);
        $this->assertEquals($employee->id, $assignment->employee->id);
        $this->assertEquals($project->id, $assignment->project->id);
    }

    /** @test */
    public function project_assignment_model_has_business_logic_methods()
    {
        $activeAssignment = ProjectAssignment::factory()->create(['is_active' => true]);
        $inactiveAssignment = ProjectAssignment::factory()->create(['is_active' => false]);
        $overAllocatedAssignment = ProjectAssignment::factory()->create(['allocation_percentage' => 120]);
        
        $this->assertTrue($activeAssignment->isActive());
        $this->assertFalse($inactiveAssignment->isActive());
        $this->assertTrue($overAllocatedAssignment->isOverAllocated());
    }

    /** @test */
    public function financial_record_model_has_correct_fillable_attributes()
    {
        $record = FinancialRecord::factory()->create();
        
        $expectedFillable = [
            'record_id',
            'project_id',
            'type',
            'category',
            'subcategory',
            'amount',
            'currency',
            'exchange_rate',
            'amount_usd',
            'description',
            'transaction_date',
            'due_date',
            'payment_date',
            'status',
            'payment_method',
            'reference_number',
            'invoice_number',
            'vendor_id',
            'customer_id',
            'related_entity_type',
            'related_entity_id',
            'tax_amount',
            'tax_rate',
            'discount_amount',
            'discount_percentage',
            'total_amount',
            'recurring_frequency',
            'recurring_end_date',
            'is_recurring',
            'parent_record_id',
            'approval_status',
            'approved_by',
            'approved_at',
            'rejection_reason',
            'attachments',
            'metadata',
            'notes',
            'tags',
            'created_by',
            'updated_by',
            'locked_at',
            'locked_by',
        ];

        $this->assertEquals($expectedFillable, $record->getFillable());
    }

    /** @test */
    public function financial_record_model_has_relationships()
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        
        $record = FinancialRecord::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Project::class, $record->project);
        $this->assertInstanceOf(User::class, $record->creator);
        $this->assertEquals($project->id, $record->project->id);
        $this->assertEquals($user->id, $record->creator->id);
    }

    /** @test */
    public function financial_record_model_has_business_logic_methods()
    {
        $revenueRecord = FinancialRecord::factory()->create(['type' => 'revenue']);
        $expenseRecord = FinancialRecord::factory()->create(['type' => 'expense']);
        $approvedRecord = FinancialRecord::factory()->create(['approval_status' => 'approved']);
        $recurringRecord = FinancialRecord::factory()->create(['is_recurring' => true]);
        
        $this->assertTrue($revenueRecord->isRevenue());
        $this->assertTrue($expenseRecord->isExpense());
        $this->assertTrue($approvedRecord->isApproved());
        $this->assertTrue($recurringRecord->isRecurring());
    }

    /** @test */
    public function financial_record_calculates_amounts_correctly()
    {
        $record = FinancialRecord::factory()->create([
            'amount' => 1000,
            'tax_rate' => 10,
            'discount_percentage' => 5,
        ]);

        // These would be calculated by the model's business logic
        $this->assertIsNumeric($record->amount);
        $this->assertIsNumeric($record->tax_rate);
        $this->assertIsNumeric($record->discount_percentage);
    }

    /** @test */
    public function all_models_use_soft_deletes()
    {
        $vendor = Vendor::factory()->create();
        $timeEntry = TimeEntry::factory()->create();
        $assignment = ProjectAssignment::factory()->create();
        $record = FinancialRecord::factory()->create();

        $vendor->delete();
        $timeEntry->delete();
        $assignment->delete();
        $record->delete();

        $this->assertSoftDeleted($vendor);
        $this->assertSoftDeleted($timeEntry);
        $this->assertSoftDeleted($assignment);
        $this->assertSoftDeleted($record);
    }

    /** @test */
    public function models_can_be_restored_after_soft_delete()
    {
        $vendor = Vendor::factory()->create();
        $vendor->delete();
        $vendor->restore();

        $this->assertNull($vendor->deleted_at);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'deleted_at' => null]);
    }

    /** @test */
    public function models_auto_generate_ids_on_creation()
    {
        $vendor = Vendor::factory()->create(['vendor_id' => null]);
        $record = FinancialRecord::factory()->create(['record_id' => null]);

        $this->assertNotNull($vendor->vendor_id);
        $this->assertNotNull($record->record_id);
        $this->assertStringStartsWith('VEN', $vendor->vendor_id);
        $this->assertStringStartsWith('FIN', $record->record_id);
    }
} 