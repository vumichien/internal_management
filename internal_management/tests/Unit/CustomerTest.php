<?php

namespace Tests\Unit;

use App\Models\Customer\Customer;
use App\Models\Project\Project;
use App\Models\Financial\FinancialRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'customer_id',
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
            'company_size',
            'tax_id',
            'annual_revenue',
            'status',
            'priority',
            'first_contact_date',
            'last_contact_date',
            'preferred_currency',
            'payment_terms',
            'credit_limit',
            'outstanding_balance',
            'additional_contacts',
            'communication_preferences',
            'notes',
            'requirements',
            'lead_source',
            'assigned_sales_rep',
            'contract_start_date',
            'contract_end_date',
            'auto_renewal',
        ];

        $this->assertEquals($fillable, $this->customer->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $casts = [
            'annual_revenue' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'first_contact_date' => 'date',
            'last_contact_date' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'auto_renewal' => 'boolean',
            'additional_contacts' => 'array',
            'communication_preferences' => 'array',
            'deleted_at' => 'datetime',
        ];

        foreach ($casts as $attribute => $cast) {
            $this->assertEquals($cast, $this->customer->getCasts()[$attribute]);
        }
    }

    /** @test */
    public function it_has_many_projects()
    {
        $projects = Project::factory()->count(3)->create(['customer_id' => $this->customer->id]);

        $this->assertCount(3, $this->customer->projects);
        $this->assertInstanceOf(Project::class, $this->customer->projects->first());
    }

    /** @test */
    public function it_has_many_financial_records()
    {
        $records = FinancialRecord::factory()->count(2)->create([
            'related_entity_id' => $this->customer->id,
            'related_entity_type' => 'customer',
        ]);

        $this->assertCount(2, $this->customer->financialRecords);
        $this->assertInstanceOf(FinancialRecord::class, $this->customer->financialRecords->first());
    }

    /** @test */
    public function it_has_active_projects()
    {
        Project::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);
        Project::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
        ]);

        $this->assertCount(2, $this->customer->activeProjects);
    }

    /** @test */
    public function it_has_completed_projects()
    {
        Project::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
        ]);
        Project::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        $this->assertCount(2, $this->customer->completedProjects);
    }

    /** @test */
    public function it_checks_if_customer_is_active()
    {
        $activeCustomer = Customer::factory()->create(['status' => 'active']);
        $inactiveCustomer = Customer::factory()->create(['status' => 'inactive']);

        $this->assertTrue($activeCustomer->isActive());
        $this->assertFalse($inactiveCustomer->isActive());
    }

    /** @test */
    public function it_checks_if_customer_is_prospect()
    {
        $prospectCustomer = Customer::factory()->create(['status' => 'prospect']);
        $activeCustomer = Customer::factory()->create(['status' => 'active']);

        $this->assertTrue($prospectCustomer->isProspect());
        $this->assertFalse($activeCustomer->isProspect());
    }

    /** @test */
    public function it_checks_if_customer_is_vip()
    {
        $vipCustomer = Customer::factory()->create(['priority' => 'vip']);
        $regularCustomer = Customer::factory()->create(['priority' => 'normal']);

        $this->assertTrue($vipCustomer->isVip());
        $this->assertFalse($regularCustomer->isVip());
    }

    /** @test */
    public function it_checks_if_customer_has_outstanding_balance()
    {
        $customerWithBalance = Customer::factory()->create(['outstanding_balance' => 1000]);
        $customerWithoutBalance = Customer::factory()->create(['outstanding_balance' => 0]);

        $this->assertTrue($customerWithBalance->hasOutstandingBalance());
        $this->assertFalse($customerWithoutBalance->hasOutstandingBalance());
    }

    /** @test */
    public function it_checks_if_customer_is_over_credit_limit()
    {
        $overLimitCustomer = Customer::factory()->create([
            'credit_limit' => 5000,
            'outstanding_balance' => 6000,
        ]);
        $underLimitCustomer = Customer::factory()->create([
            'credit_limit' => 5000,
            'outstanding_balance' => 3000,
        ]);

        $this->assertTrue($overLimitCustomer->isOverCreditLimit());
        $this->assertFalse($underLimitCustomer->isOverCreditLimit());
    }

    /** @test */
    public function it_checks_if_contract_is_expiring_soon()
    {
        $expiringSoonCustomer = Customer::factory()->create([
            'contract_end_date' => Carbon::now()->addDays(15),
        ]);
        $notExpiringSoonCustomer = Customer::factory()->create([
            'contract_end_date' => Carbon::now()->addDays(60),
        ]);

        $this->assertTrue($expiringSoonCustomer->isContractExpiringSoon());
        $this->assertFalse($notExpiringSoonCustomer->isContractExpiringSoon());
    }

    /** @test */
    public function it_checks_if_contract_has_expired()
    {
        $expiredCustomer = Customer::factory()->create([
            'contract_end_date' => Carbon::now()->subDay(),
        ]);
        $activeCustomer = Customer::factory()->create([
            'contract_end_date' => Carbon::now()->addDay(),
        ]);

        $this->assertTrue($expiredCustomer->isContractExpired());
        $this->assertFalse($activeCustomer->isContractExpired());
    }

    /** @test */
    public function it_generates_unique_customer_id()
    {
        $id1 = Customer::generateCustomerId();
        $id2 = Customer::generateCustomerId();

        $this->assertStringStartsWith('CUST', $id1);
        $this->assertStringStartsWith('CUST', $id2);
        $this->assertNotEquals($id1, $id2);
    }

    /** @test */
    public function it_auto_generates_customer_id_on_creation()
    {
        $customer = Customer::factory()->create(['customer_id' => null]);

        $this->assertNotNull($customer->customer_id);
        $this->assertStringStartsWith('CUST', $customer->customer_id);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $this->customer->delete();

        $this->assertSoftDeleted($this->customer);
        $this->assertNotNull($this->customer->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete()
    {
        $this->customer->delete();
        $this->customer->restore();

        $this->assertNull($this->customer->deleted_at);
        $this->assertDatabaseHas('customers', ['id' => $this->customer->id, 'deleted_at' => null]);
    }
} 