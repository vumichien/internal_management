<?php

namespace Database\Factories;

use App\Models\Vendor\Vendor;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor\Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contractStartDate = $this->faker->dateTimeBetween('-3 years', '-1 month');
        $contractEndDate = $this->faker->dateTimeBetween($contractStartDate, '+2 years');
        $insuranceExpiryDate = $this->faker->dateTimeBetween('now', '+1 year');
        
        $vendorTypes = ['supplier', 'contractor', 'consultant', 'service_provider', 'partner'];
        $serviceTypes = [
            'IT Services', 'Consulting', 'Marketing', 'Legal', 'Accounting', 
            'Maintenance', 'Security', 'Cleaning', 'Catering', 'Transportation',
            'Software Development', 'Design', 'Training', 'Support'
        ];
        $industries = [
            'Technology', 'Healthcare', 'Finance', 'Education', 'Manufacturing',
            'Retail', 'Construction', 'Transportation', 'Energy', 'Media'
        ];
        $statuses = ['active', 'pending', 'suspended', 'terminated'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $paymentTerms = ['net-15', 'net-30', 'net-45', 'net-60', 'immediate', 'on-delivery'];
        $currencies = ['USD', 'EUR', 'GBP', 'CAD'];
        
        $companyNames = [
            'TechSolutions Inc.', 'Global Consulting Group', 'Premier Services LLC',
            'Innovation Partners', 'Excellence Corp', 'Strategic Advisors',
            'Quality Systems', 'Professional Services Co.', 'Elite Contractors',
            'Advanced Solutions', 'Reliable Partners', 'Expert Consultants'
        ];
        
        return [
            'company_name' => $this->faker->randomElement($companyNames),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->optional(0.7)->url(),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'vendor_type' => $this->faker->randomElement($vendorTypes),
            'service_type' => $this->faker->randomElement($serviceTypes),
            'industry' => $this->faker->randomElement($industries),
            'payment_terms' => $this->faker->randomElement($paymentTerms),
            'tax_id' => $this->faker->numerify('##-#######'),
            'business_license' => $this->faker->optional(0.8)->numerify('BL-######'),
            'status' => $this->faker->randomElement($statuses),
            'priority' => $this->faker->randomElement($priorities),
            'credit_limit' => $this->faker->numberBetween(5000, 100000),
            'outstanding_balance' => $this->faker->numberBetween(0, 25000),
            'performance_rating' => $this->faker->randomFloat(1, 1.0, 5.0),
            'delivery_success_rate' => $this->faker->numberBetween(70, 100),
            'average_delivery_time' => $this->faker->numberBetween(1, 30),
            'last_performance_review' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'first_contact_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            'last_contact_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'contract_start_date' => $contractStartDate,
            'contract_end_date' => $contractEndDate,
            'auto_renewal' => $this->faker->boolean(60),
            'insurance_verified' => $this->faker->boolean(80),
            'insurance_expiry_date' => $insuranceExpiryDate,
            'background_check_completed' => $this->faker->boolean(70),
            'background_check_date' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'assigned_procurement_rep' => null, // Will be set after employees are created
            'preferred_currency' => $this->faker->randomElement($currencies),
            'bank_account_info' => json_encode([
                'bank_name' => $this->faker->company() . ' Bank',
                'account_number' => $this->faker->bankAccountNumber(),
                'routing_number' => $this->faker->numerify('#########'),
                'swift_code' => $this->faker->optional(0.5)->regexify('[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?')
            ]),
            'services_provided' => $this->generateServicesProvided(),
            'additional_contacts' => $this->generateAdditionalContacts(),
            'communication_preferences' => $this->generateCommunicationPreferences(),
            'notes' => $this->faker->optional(0.5)->paragraph(),
            'lead_source' => $this->faker->randomElement(['referral', 'website', 'trade_show', 'cold_call']),
        ];
    }

    /**
     * Generate realistic services provided based on vendor type and service type
     */
    private function generateServicesProvided(): array
    {
        $serviceCategories = [
            'IT Services' => [
                'Software Development', 'System Administration', 'Network Management',
                'Cybersecurity', 'Cloud Services', 'Technical Support'
            ],
            'Consulting' => [
                'Business Strategy', 'Process Improvement', 'Change Management',
                'Project Management', 'Risk Assessment', 'Compliance Consulting'
            ],
            'Marketing' => [
                'Digital Marketing', 'Content Creation', 'SEO/SEM', 'Social Media Management',
                'Brand Development', 'Market Research'
            ],
            'Legal' => [
                'Contract Review', 'Compliance', 'Intellectual Property', 'Employment Law',
                'Corporate Law', 'Litigation Support'
            ],
            'Accounting' => [
                'Bookkeeping', 'Tax Preparation', 'Financial Auditing', 'Payroll Services',
                'Financial Planning', 'Budget Analysis'
            ]
        ];

        $defaultServices = ['General Services', 'Consulting', 'Support', 'Maintenance'];
        $maxServices = count($defaultServices);
        $numServices = $this->faker->numberBetween(2, min(5, $maxServices));
        
        return $this->faker->randomElements($defaultServices, $numServices);
    }

    /**
     * Generate additional contacts array.
     */
    private function generateAdditionalContacts(): array
    {
        $contactCount = $this->faker->numberBetween(0, 3);
        $contacts = [];

        for ($i = 0; $i < $contactCount; $i++) {
            $contacts[] = [
                'name' => $this->faker->name(),
                'title' => $this->faker->jobTitle(),
                'email' => $this->faker->email(),
                'phone' => $this->faker->phoneNumber(),
                'department' => $this->faker->randomElement([
                    'Sales', 'Support', 'Billing', 'Technical', 'Management'
                ]),
                'is_primary' => $i === 0,
            ];
        }

        return $contacts;
    }

    /**
     * Generate communication preferences array.
     */
    private function generateCommunicationPreferences(): array
    {
        return [
            'preferred_method' => $this->faker->randomElement(['email', 'phone', 'in_person', 'video_call']),
            'best_time' => $this->faker->randomElement(['morning', 'afternoon', 'evening']),
            'timezone' => $this->faker->timezone(),
            'frequency' => $this->faker->randomElement(['weekly', 'bi_weekly', 'monthly', 'quarterly']),
            'newsletter' => $this->faker->boolean(60),
            'marketing_emails' => $this->faker->boolean(40),
            'phone_calls' => $this->faker->boolean(70),
        ];
    }

    /**
     * Indicate that the vendor is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the vendor is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the vendor is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the vendor has excellent performance.
     */
    public function excellentPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'performance_rating' => $this->faker->randomFloat(1, 4.5, 5.0),
            'delivery_success_rate' => $this->faker->numberBetween(95, 100),
            'average_delivery_time' => $this->faker->numberBetween(1, 5),
        ]);
    }
} 