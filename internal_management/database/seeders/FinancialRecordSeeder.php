<?php

namespace Database\Seeders;

use App\Models\Financial\FinancialRecord;
use App\Models\Project\Project;
use App\Models\Customer\Customer;
use App\Models\Vendor\Vendor;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("🌱 Starting optimized financial records seeding...");
        
        // Pre-load all required data to avoid N+1 queries
        $projects = Project::with('customer')->where('is_billable', true)->limit(10)->get();
        $customers = Customer::where('status', 'active')->limit(20)->get();
        $vendors = Vendor::where('status', 'active')->limit(15)->get();
        $employees = Employee::where('status', 'active')->limit(20)->get();
        $users = User::pluck('id')->toArray();

        if ($projects->isEmpty()) {
            $this->command->warn('No projects found. Creating a default project for financial records.');
            // Create a default project for non-project expenses
            $defaultProject = Project::create([
                'name' => 'General Operations',
                'description' => 'Default project for general business expenses',
                'status' => 'active',
                'start_date' => now()->subYear(),
                'end_date' => now()->addYear(),
                'budget' => 1000000,
                'currency' => 'USD',
                'is_billable' => false,
                'priority' => 'medium',
                'customer_id' => $customers->first()?->id ?? 1,
            ]);
            $projects = collect([$defaultProject]);
        }

        $this->command->info("Processing {$projects->count()} projects, {$vendors->count()} vendors, {$employees->count()} employees");

        // Batch create records for better performance
        $batchSize = 100;
        $allRecords = [];

        // Create project-based revenue records
        foreach ($projects as $project) {
            if ($project->customer) {
                $allRecords = array_merge($allRecords, $this->generateProjectRevenue($project, $users));
            }
        }

        // Create monthly recurring expenses (use first project as default)
        $defaultProject = $projects->first();
        $allRecords = array_merge($allRecords, $this->generateRecurringExpenses($vendors, $users, $defaultProject));

        // Create vendor-based expenses
        foreach ($vendors->take(10) as $vendor) {
            $allRecords = array_merge($allRecords, $this->generateVendorExpenses($vendor, $projects, $users));
        }

        // Create employee-based expenses
        foreach ($employees->take(15) as $employee) {
            $allRecords = array_merge($allRecords, $this->generateEmployeeExpenses($employee, $projects, $users, $defaultProject));
        }

        // Insert records in batches for optimal performance
        $this->insertInBatches($allRecords, $batchSize);

        // Create additional records using factories (more efficient)
        $this->command->info("Creating additional factory records...");
        FinancialRecord::factory(30)->create();
        FinancialRecord::factory(15)->paid()->create();
        FinancialRecord::factory(8)->recurring()->create();
        FinancialRecord::factory(5)->requiresApproval()->create();

        $totalRecords = count($allRecords) + 58; // 30 + 15 + 8 + 5
        $this->command->info("✅ Created {$totalRecords} optimized financial records");
    }

    /**
     * Insert records in batches for better performance
     */
    private function insertInBatches(array $records, int $batchSize): void
    {
        $chunks = array_chunk($records, $batchSize);
        $totalChunks = count($chunks);
        
        foreach ($chunks as $index => $chunk) {
            $this->command->info("Inserting batch " . ($index + 1) . " of {$totalChunks}...");
            DB::table('financial_records')->insert($chunk);
        }
    }

    /**
     * Get the standard record template with all required fields
     */
    private function getRecordTemplate(): array
    {
        return [
            'record_id' => null,
            'project_id' => null,
            'type' => null,
            'amount' => null,
            'currency' => 'USD',
            'exchange_rate' => 1.0000,
            'amount_usd' => null,
            'description' => null,
            'category' => null,
            'subcategory' => null,
            'reference_number' => null,
            'external_reference' => null,
            'transaction_date' => null,
            'due_date' => null,
            'paid_date' => null,
            'related_entity_type' => null,
            'related_entity_id' => null,
            'status' => 'draft',
            'is_billable' => true,
            'is_recurring' => false,
            'recurring_frequency' => null,
            'next_occurrence' => null,
            'tax_amount' => 0,
            'tax_rate' => 0,
            'tax_type' => null,
            'account_code' => null,
            'created_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
            'payment_method' => null,
            'payment_reference' => null,
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'attachments' => null,
            'metadata' => null,
            'synced_to_accounting' => false,
            'accounting_sync_at' => null,
            'accounting_system_id' => null,
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    /**
     * Generate revenue records for a project
     */
    private function generateProjectRevenue(Project $project, array $users): array
    {
        $records = [];
        $totalRevenue = $project->budget;
        $milestonesCount = rand(2, 4); // Reduced from 5
        $milestoneAmount = $totalRevenue / $milestonesCount;

        for ($i = 1; $i <= $milestonesCount; $i++) {
            $milestoneDate = $project->start_date->copy()->addDays(
                ($project->start_date->diffInDays($project->end_date) / $milestonesCount) * $i
            );
            
            $status = $milestoneDate->isPast() ? 'paid' : 'pending';
            $paidDate = $status === 'paid' ? $milestoneDate->copy()->addDays(rand(5, 30)) : null;
            $requiresApproval = $milestoneAmount > 10000;

            $record = $this->getRecordTemplate();
            $record['record_id'] = 'REV-' . str_pad($project->id, 4, '0', STR_PAD_LEFT) . '-' . $i;
            $record['project_id'] = $project->id;
            $record['type'] = 'revenue';
            $record['amount'] = $milestoneAmount;
            $record['currency'] = $project->currency ?? 'USD';
            $record['exchange_rate'] = 1.0000;
            $record['amount_usd'] = $milestoneAmount;
            $record['description'] = "Milestone {$i} payment for {$project->name} - {$project->customer->name}";
            $record['category'] = 'Project Revenue';
            $record['subcategory'] = 'Milestone Payment';
            $record['reference_number'] = 'REV-' . str_pad($project->id, 4, '0', STR_PAD_LEFT) . '-' . $i;
            $record['transaction_date'] = $milestoneDate;
            $record['due_date'] = $milestoneDate->copy()->addDays(30);
            $record['paid_date'] = $paidDate;
            $record['related_entity_type'] = 'customer';
            $record['related_entity_id'] = $project->customer_id;
            $record['status'] = $status;
            $record['is_billable'] = true;
            $record['is_recurring'] = false;
            $record['tax_amount'] = $milestoneAmount * 0.08;
            $record['tax_rate'] = 0.08;
            $record['payment_method'] = 'bank_transfer';
            $record['created_by'] = $users[array_rand($users)];
            $record['approved_by'] = $requiresApproval ? $users[array_rand($users)] : null;
            $record['approved_at'] = $requiresApproval ? $milestoneDate : null;
            $record['approval_notes'] = "Milestone {$i} of {$milestonesCount} for project completion";
            $record['metadata'] = json_encode([
                'created_via' => 'manual',
                'fiscal_year' => $milestoneDate->year,
                'quarter' => 'Q' . ceil($milestoneDate->month / 3),
                'client_po_number' => 'PO-' . str_pad($project->customer_id, 6, '0', STR_PAD_LEFT),
                'contract_reference' => 'CTR-' . $project->id
            ]);
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Generate recurring monthly expenses
     */
    private function generateRecurringExpenses($vendors, array $users, Project $defaultProject): array
    {
        $records = [];
        $recurringExpenses = [
            [
                'category' => 'Office Rent',
                'amount' => 8500,
                'description' => 'Monthly office space rental',
                'vendor' => $vendors->first()
            ],
            [
                'category' => 'Software Licenses',
                'amount' => 2500,
                'description' => 'Monthly software subscriptions and licenses',
                'vendor' => $vendors->skip(1)->first() ?? $vendors->first()
            ],
            [
                'category' => 'Utilities',
                'amount' => 1200,
                'description' => 'Monthly utilities (electricity, water, internet)',
                'vendor' => $vendors->skip(2)->first() ?? $vendors->first()
            ],
            [
                'category' => 'Insurance',
                'amount' => 3200,
                'description' => 'Monthly business insurance premiums',
                'vendor' => $vendors->skip(3)->first() ?? $vendors->first()
            ]
        ];

        foreach ($recurringExpenses as $expense) {
            // Create records for the last 2 months (reduced from 3)
            for ($month = 2; $month >= 1; $month--) {
                $expenseDate = now()->subMonths($month)->startOfMonth();
                
                $record = $this->getRecordTemplate();
                $record['record_id'] = 'EXP-' . $expenseDate->format('Ym') . '-' . substr($expense['category'], 0, 3);
                $record['project_id'] = $defaultProject->id;
                $record['type'] = 'expense';
                $record['amount'] = $expense['amount'];
                $record['currency'] = 'USD';
                $record['exchange_rate'] = 1.0000;
                $record['amount_usd'] = $expense['amount'];
                $record['description'] = $expense['description'];
                $record['category'] = $expense['category'];
                $record['subcategory'] = 'Monthly Recurring';
                $record['reference_number'] = 'EXP-' . $expenseDate->format('Ym') . '-' . substr($expense['category'], 0, 3);
                $record['transaction_date'] = $expenseDate;
                $record['due_date'] = $expenseDate->copy()->addDays(30);
                $record['paid_date'] = $expenseDate->copy()->addDays(rand(15, 25));
                $record['related_entity_type'] = 'vendor';
                $record['related_entity_id'] = $expense['vendor']->id;
                $record['status'] = 'paid';
                $record['is_billable'] = false;
                $record['is_recurring'] = true;
                $record['recurring_frequency'] = 'monthly';
                $record['tax_amount'] = $expense['amount'] * 0.05;
                $record['tax_rate'] = 0.05;
                $record['payment_method'] = 'bank_transfer';
                $record['created_by'] = $users[array_rand($users)];
                $record['approval_notes'] = 'Automatically generated recurring expense';
                $record['metadata'] = json_encode([
                    'created_via' => 'recurring',
                    'fiscal_year' => $expenseDate->year,
                    'quarter' => 'Q' . ceil($expenseDate->month / 3),
                    'vendor_invoice' => 'VI-' . $expenseDate->format('Ym') . str_pad($expense['vendor']->id, 3, '0', STR_PAD_LEFT)
                ]);
                $record['created_at'] = now();
                $record['updated_at'] = now();

                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Generate vendor-based expenses
     */
    private function generateVendorExpenses(Vendor $vendor, $projects, array $users): array
    {
        $records = [];
        $expenseCount = rand(1, 3); // Reduced from 2-5
        
        for ($i = 0; $i < $expenseCount; $i++) {
            $expenseDate = now()->subDays(rand(30, 120)); // Reduced range
            $amount = rand(500, 8000); // Reduced max amount
            $project = $projects->random();
            
            $status = $expenseDate->diffInDays(now()) > 60 ? 'paid' : ['pending', 'approved', 'paid'][rand(0, 2)];
            $paidDate = $status === 'paid' ? $expenseDate->copy()->addDays(rand(15, 45)) : null;
            $requiresApproval = $amount > 5000;

            $record = $this->getRecordTemplate();
            $record['record_id'] = 'VEN-' . str_pad($vendor->id, 4, '0', STR_PAD_LEFT) . '-' . $expenseDate->format('md');
            $record['project_id'] = $project->id;
            $record['type'] = 'expense';
            $record['amount'] = $amount;
            $record['currency'] = $vendor->preferred_currency ?? 'USD';
            $record['exchange_rate'] = ($vendor->preferred_currency ?? 'USD') !== 'USD' ? rand(80, 120) / 100 : 1.0000;
            $record['amount_usd'] = ($vendor->preferred_currency ?? 'USD') !== 'USD' ? $amount * (rand(80, 120) / 100) : $amount;
            $record['description'] = $this->generateVendorExpenseDescription($vendor->service_type ?? 'Professional Services');
            $record['category'] = $this->mapServiceTypeToCategory($vendor->service_type ?? 'Professional Services');
            $record['subcategory'] = $vendor->service_type ?? 'Professional Services';
            $record['reference_number'] = 'VEN-' . str_pad($vendor->id, 4, '0', STR_PAD_LEFT) . '-' . $expenseDate->format('md');
            $record['transaction_date'] = $expenseDate;
            $record['due_date'] = $expenseDate->copy()->addDays(30);
            $record['paid_date'] = $paidDate;
            $record['related_entity_type'] = 'vendor';
            $record['related_entity_id'] = $vendor->id;
            $record['status'] = $status;
            $record['is_billable'] = $project ? ($project->is_billable ?? false) : false;
            $record['is_recurring'] = false;
            $record['tax_amount'] = $amount * 0.07;
            $record['tax_rate'] = 0.07;
            $record['payment_method'] = ($vendor->payment_terms ?? 'net_30') === 'immediate' ? 'credit_card' : 'bank_transfer';
            $record['created_by'] = $users[array_rand($users)];
            $record['approved_by'] = $requiresApproval ? $users[array_rand($users)] : null;
            $record['approved_at'] = $requiresApproval ? $expenseDate->copy()->addDays(rand(1, 5)) : null;
            $record['approval_notes'] = "Service provided by {$vendor->company_name}";
            $record['metadata'] = json_encode([
                'created_via' => 'manual',
                'fiscal_year' => $expenseDate->year,
                'quarter' => 'Q' . ceil($expenseDate->month / 3),
                'vendor_invoice' => 'VI-' . $expenseDate->format('Y') . str_pad($vendor->id * 100 + $i, 6, '0', STR_PAD_LEFT)
            ]);
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Generate employee-based expenses
     */
    private function generateEmployeeExpenses(Employee $employee, $projects, array $users, Project $defaultProject): array
    {
        $records = [];
        
        // Create salary record for last month
        $salaryDate = now()->subMonth()->endOfMonth();
        
        $record = $this->getRecordTemplate();
        $record['record_id'] = 'SAL-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT) . '-' . $salaryDate->format('Ym');
        $record['project_id'] = $defaultProject->id;
        $record['type'] = 'expense';
        $record['amount'] = $employee->salary;
        $record['currency'] = 'USD';
        $record['exchange_rate'] = 1.0000;
        $record['amount_usd'] = $employee->salary;
        $record['description'] = "Monthly salary for " . ($employee->user->name ?? 'Employee');
        $record['category'] = 'Salaries';
        $record['subcategory'] = 'Base Salary';
        $record['reference_number'] = 'SAL-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT) . '-' . $salaryDate->format('Ym');
        $record['transaction_date'] = $salaryDate;
        $record['due_date'] = $salaryDate;
        $record['paid_date'] = $salaryDate;
        $record['related_entity_type'] = 'employee';
        $record['related_entity_id'] = $employee->id;
        $record['status'] = 'paid';
        $record['is_billable'] = false;
        $record['is_recurring'] = true;
        $record['recurring_frequency'] = 'monthly';
        $record['tax_amount'] = $employee->salary * 0.15;
        $record['tax_rate'] = 0.15;
        $record['payment_method'] = 'bank_transfer';
        $record['created_by'] = $users[array_rand($users)];
        $record['approval_notes'] = "Monthly salary payment for {$employee->job_title}";
        $record['metadata'] = json_encode([
            'created_via' => 'payroll',
            'fiscal_year' => $salaryDate->year,
            'quarter' => 'Q' . ceil($salaryDate->month / 3),
            'employee_id' => $employee->id,
            'department' => $employee->department ?? 'general'
        ]);
        $record['created_at'] = now();
        $record['updated_at'] = now();

        $records[] = $record;

        // Create occasional reimbursement expenses (reduced probability)
        if (rand(1, 100) <= 25) { // Reduced from 40% to 25%
            $reimbursementDate = now()->subDays(rand(7, 45)); // Reduced range
            $reimbursementAmount = rand(50, 500); // Reduced max amount
            $requiresApproval = $reimbursementAmount > 300;
            
            $record = $this->getRecordTemplate();
            $record['record_id'] = 'REIMB-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT) . '-' . $reimbursementDate->format('md');
            $record['project_id'] = $projects->random()->id;
            $record['type'] = 'expense';
            $record['amount'] = $reimbursementAmount;
            $record['currency'] = 'USD';
            $record['exchange_rate'] = 1.0000;
            $record['amount_usd'] = $reimbursementAmount;
            $record['description'] = $this->generateReimbursementDescription();
            $record['category'] = 'Travel';
            $record['subcategory'] = 'Employee Reimbursement';
            $record['reference_number'] = 'REIMB-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT) . '-' . $reimbursementDate->format('md');
            $record['transaction_date'] = $reimbursementDate;
            $record['due_date'] = $reimbursementDate->copy()->addDays(7);
            $record['paid_date'] = $reimbursementDate->copy()->addDays(rand(3, 10));
            $record['related_entity_type'] = 'employee';
            $record['related_entity_id'] = $employee->id;
            $record['status'] = 'paid';
            $record['is_billable'] = true;
            $record['is_recurring'] = false;
            $record['tax_amount'] = 0;
            $record['tax_rate'] = 0;
            $record['payment_method'] = 'bank_transfer';
            $record['created_by'] = $users[array_rand($users)];
            $record['approved_by'] = $requiresApproval ? $users[array_rand($users)] : null;
            $record['approved_at'] = $requiresApproval ? $reimbursementDate : null;
            $record['metadata'] = json_encode([
                'created_via' => 'expense_report',
                'fiscal_year' => $reimbursementDate->year,
                'quarter' => 'Q' . ceil($reimbursementDate->month / 3),
                'expense_report_id' => 'EXP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT)
            ]);
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Generate vendor expense description
     */
    private function generateVendorExpenseDescription(string $serviceType): string
    {
        $descriptions = [
            'IT Services' => 'Technical consulting and system maintenance services',
            'Legal' => 'Legal consultation and contract review services',
            'Design' => 'Professional services - ' . $serviceType,
            'Security' => 'Security services and system monitoring',
            'Training' => 'Employee training and development programs',
            'Consulting' => 'Business consulting and strategic planning services',
            'Professional Services' => 'Professional services - ' . $serviceType,
        ];

        return $descriptions[$serviceType] ?? 'Professional services - ' . $serviceType;
    }

    /**
     * Map service type to expense category
     */
    private function mapServiceTypeToCategory(string $serviceType): string
    {
        $mapping = [
            'IT Services' => 'Professional Services',
            'Legal' => 'Legal Fees',
            'Design' => 'Professional Services',
            'Security' => 'Security',
            'Training' => 'Training',
            'Consulting' => 'Professional Services',
        ];

        return $mapping[$serviceType] ?? 'Professional Services';
    }

    /**
     * Generate reimbursement description
     */
    private function generateReimbursementDescription(): string
    {
        $descriptions = [
            'Office supplies purchased for project',
            'Conference attendance - registration and travel',
            'Client meeting expenses - meals and transportation',
            'Training materials and certification fees',
            'Business travel accommodation and meals'
        ];

        return 'Employee reimbursement for business expenses';
    }
} 