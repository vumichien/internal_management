<?php

namespace Database\Factories;

use App\Models\Financial\FinancialRecord;
use App\Models\Project\Project;
use App\Models\Customer\Customer;
use App\Models\Vendor\Vendor;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Financial\FinancialRecord>
 */
class FinancialRecordFactory extends Factory
{
    protected $model = FinancialRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['revenue', 'expense']);
        $amount = $this->faker->randomFloat(2, 100, 50000);
        $currency = $this->faker->randomElement(['USD', 'EUR', 'CAD', 'GBP']);
        $status = $this->faker->randomElement(['draft', 'pending', 'approved', 'paid', 'overdue', 'cancelled']);
        $category = $this->getRandomCategory($type);
        $paymentMethod = $this->faker->randomElement(['credit_card', 'bank_transfer', 'cash', 'check', 'wire_transfer']);
        
        // Determine related entity based on type
        $relatedEntityType = null;
        $relatedEntityId = null;
        
        if ($type === 'revenue') {
            $relatedEntityType = 'customer';
            $relatedEntityId = Customer::factory();
        } else {
            $entityTypes = ['vendor', 'employee'];
            $relatedEntityType = $this->faker->randomElement($entityTypes);
            $relatedEntityId = $relatedEntityType === 'vendor' ? Vendor::factory() : Employee::factory();
        }
        
        $date = $this->faker->dateTimeBetween('-1 year', 'now');
        $dueDate = $this->faker->optional(0.8)->dateTimeBetween($date, '+3 months');
        $paidDate = $status === 'paid' ? $this->faker->dateTimeBetween($date, 'now') : null;
        
        // Ensure discount amounts are never null (use 0 as default)
        $discountAmount = $this->faker->boolean(20) ? $this->faker->randomFloat(2, 0, $amount * 0.1) : 0;
        $discountPercentage = $this->faker->boolean(20) ? $this->faker->randomFloat(2, 0, 10) : 0;
        
        return [
            'record_id' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{6}'),
            'project_id' => Project::inRandomOrder()->first()?->id ?? Project::factory(),
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'exchange_rate' => $currency !== 'USD' ? $this->faker->randomFloat(4, 0.5, 2.0) : 1.0000,
            'amount_usd' => $currency !== 'USD' ? $amount * $this->faker->randomFloat(4, 0.5, 2.0) : $amount,
            'description' => $this->generateDescription($type, $category),
            'category' => $category,
            'subcategory' => $this->generateSubcategory($category),
            'reference_number' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'external_reference' => $this->faker->optional(0.3)->regexify('EXT-[0-9]{6}'),
            'transaction_date' => $date,
            'due_date' => $dueDate,
            'paid_date' => $paidDate,
            'related_entity_type' => $relatedEntityType,
            'related_entity_id' => $relatedEntityType === 'customer' ? 
                (Customer::inRandomOrder()->first()?->id ?? Customer::factory()) :
                ($relatedEntityType === 'vendor' ? 
                    (Vendor::inRandomOrder()->first()?->id ?? Vendor::factory()) :
                    (Employee::inRandomOrder()->first()?->id ?? Employee::factory())),
            'status' => $status,
            'is_billable' => $type === 'expense' ? $this->faker->boolean(60) : true,
            'is_recurring' => $this->faker->boolean(20),
            'recurring_frequency' => $this->faker->optional(0.2)->randomElement(['monthly', 'quarterly', 'yearly']),
            'next_occurrence' => $this->faker->optional(0.2)->dateTimeBetween('now', '+1 year'),
            'tax_amount' => $amount * $this->faker->randomFloat(3, 0.05, 0.15), // Never null, always a calculated value
            'tax_rate' => $this->faker->randomFloat(4, 0.05, 0.15), // Fixed: Generate values between 0.05 and 0.15 (5% to 15% as decimal)
            'tax_type' => $this->faker->optional(0.7)->randomElement(['VAT', 'GST', 'Sales Tax']),
            'account_code' => $this->faker->optional(0.5)->regexify('[0-9]{4}'),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'approved_by' => $amount > 5000 && $status !== 'pending' ? (User::inRandomOrder()->first()?->id ?? User::factory()) : null,
            'approved_at' => $amount > 5000 && $status !== 'pending' ? $this->faker->dateTimeBetween($date, 'now') : null,
            'approval_notes' => $amount > 5000 ? $this->faker->optional(0.6)->sentence() : null,
            'payment_method' => $paymentMethod,
            'payment_reference' => $status === 'paid' ? $this->faker->regexify('[A-Z0-9]{8}') : null,
            'discount_amount' => $discountAmount, // Never null, always 0 or a positive value
            'discount_percentage' => $discountPercentage, // Never null, always 0 or a positive value
            'attachments' => $this->faker->optional(0.3)->randomElements([
                'receipts/receipt_001.pdf',
                'invoices/invoice_002.pdf',
                'contracts/contract_003.pdf'
            ], rand(1, 2)),
            'metadata' => $this->generateMetadata($type, $category),
            'synced_to_accounting' => $this->faker->boolean(30),
            'accounting_sync_at' => $this->faker->optional(0.3)->dateTimeBetween($date, 'now'),
            'accounting_system_id' => $this->faker->optional(0.3)->regexify('ACC-[0-9]{8}'),
        ];
    }

    /**
     * Get random category based on type
     */
    private function getRandomCategory(string $type): string
    {
        $revenueCategories = [
            'Project Revenue', 'Consulting Fees', 'License Fees', 'Maintenance Fees',
            'Training Revenue', 'Support Revenue', 'Subscription Revenue', 'One-time Payment'
        ];
        
        $expenseCategories = [
            'Salaries', 'Office Rent', 'Utilities', 'Software Licenses', 'Hardware',
            'Travel', 'Marketing', 'Professional Services', 'Insurance', 'Training'
        ];
        
        return $type === 'revenue' 
            ? $this->faker->randomElement($revenueCategories)
            : $this->faker->randomElement($expenseCategories);
    }

    /**
     * Generate description based on type and category
     */
    private function generateDescription(string $type, string $category): string
    {
        $descriptions = [
            'revenue' => [
                'Project Revenue' => 'Payment for project deliverables and milestones',
                'Consulting Fees' => 'Professional consulting services provided',
                'License Fees' => 'Software license and usage fees',
                'Maintenance Fees' => 'Ongoing maintenance and support services',
                'Training Revenue' => 'Training and educational services',
                'Support Revenue' => 'Technical support and assistance',
                'Subscription Revenue' => 'Monthly/annual subscription payments',
                'One-time Payment' => 'One-time service or product payment'
            ],
            'expense' => [
                'Salaries' => 'Employee salary and compensation',
                'Office Rent' => 'Monthly office space rental',
                'Utilities' => 'Electricity, water, and internet services',
                'Software Licenses' => 'Software tools and platform licenses',
                'Hardware' => 'Computer equipment and hardware purchases',
                'Travel' => 'Business travel and accommodation expenses',
                'Marketing' => 'Marketing campaigns and promotional activities',
                'Professional Services' => 'Legal, accounting, and consulting services',
                'Insurance' => 'Business insurance premiums',
                'Training' => 'Employee training and development programs'
            ]
        ];

        $categoryDescriptions = $descriptions[$type] ?? [];
        return $categoryDescriptions[$category] ?? $this->faker->sentence();
    }

    /**
     * Generate subcategory based on category
     */
    private function generateSubcategory(string $category): ?string
    {
        $subcategories = [
            'Project Revenue' => ['Milestone Payment', 'Final Payment', 'Retainer', 'Change Order'],
            'Consulting Fees' => ['Strategy Consulting', 'Technical Consulting', 'Process Consulting'],
            'Salaries' => ['Base Salary', 'Overtime', 'Bonus', 'Commission'],
            'Software Licenses' => ['Development Tools', 'Productivity Software', 'Security Software'],
            'Travel' => ['Flights', 'Hotels', 'Meals', 'Transportation'],
            'Marketing' => ['Digital Advertising', 'Print Materials', 'Events', 'Content Creation']
        ];

        $categorySubcategories = $subcategories[$category] ?? null;
        return $categorySubcategories ? $this->faker->randomElement($categorySubcategories) : null;
    }

    /**
     * Generate metadata based on type and category
     */
    private function generateMetadata(string $type, string $category): array
    {
        $baseMetadata = [
            'created_via' => $this->faker->randomElement(['manual', 'import', 'api', 'recurring']),
            'fiscal_year' => date('Y'),
            'quarter' => 'Q' . ceil(date('n') / 3)
        ];

        if ($type === 'revenue') {
            $baseMetadata['client_po_number'] = $this->faker->optional(0.6)->regexify('PO-[0-9]{6}');
            $baseMetadata['contract_reference'] = $this->faker->optional(0.4)->regexify('CTR-[0-9]{4}');
        } else {
            $baseMetadata['vendor_invoice'] = $this->faker->optional(0.7)->regexify('VI-[0-9]{6}');
            $baseMetadata['expense_report_id'] = $this->faker->optional(0.3)->regexify('EXP-[0-9]{4}');
        }

        return $baseMetadata;
    }

    /**
     * Indicate that the financial record is revenue.
     */
    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'revenue',
            'related_entity_type' => 'customer',
            'related_entity_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory(),
        ]);
    }

    /**
     * Indicate that the financial record is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'related_entity_type' => $this->faker->randomElement(['vendor', 'employee']),
        ]);
    }

    /**
     * Indicate that the financial record is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_date' => $this->faker->dateTimeBetween($attributes['transaction_date'], 'now'),
            'payment_reference' => $this->faker->regexify('[A-Z0-9]{8}'),
        ]);
    }

    /**
     * Indicate that the financial record is recurring.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'next_occurrence' => $this->faker->dateTimeBetween('now', '+1 year'),
        ]);
    }

    /**
     * Indicate that the financial record requires approval.
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 5000, 50000),
            'approved_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'approved_at' => $this->faker->dateTimeBetween($attributes['transaction_date'], 'now'),
            'approval_notes' => $this->faker->sentence(),
        ]);
    }
} 