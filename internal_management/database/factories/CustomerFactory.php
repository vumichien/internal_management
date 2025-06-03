<?php

namespace Database\Factories;

use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $industries = [
            'Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail',
            'Education', 'Real Estate', 'Consulting', 'Marketing', 'Legal',
            'Construction', 'Transportation', 'Energy', 'Entertainment', 'Non-profit'
        ];

        $companySizes = ['startup', 'small', 'medium', 'large', 'enterprise'];
        $statuses = ['prospect', 'active', 'inactive', 'former'];
        $priorities = ['low', 'medium', 'high', 'vip'];
        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];
        $paymentTerms = ['net_15', 'net_30', 'net_45', 'net_60', 'immediate', 'custom'];
        $leadSources = ['website', 'referral', 'cold_call', 'trade_show', 'social_media', 'advertising', 'partner'];

        $firstContactDate = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $lastContactDate = $this->faker->dateTimeBetween($firstContactDate, 'now');
        
        $contractStartDate = $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now');
        $contractEndDate = $contractStartDate ? 
            $this->faker->dateTimeBetween($contractStartDate, '+2 years') : null;

        return [
            'company_name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->optional(0.8)->url(),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'industry' => $this->faker->randomElement($industries),
            'company_size' => $this->faker->randomElement($companySizes),
            'tax_id' => $this->faker->optional(0.8)->numerify('##-#######'),
            'annual_revenue' => $this->faker->optional(0.6)->randomFloat(2, 100000, 50000000),
            'status' => $this->faker->randomElement($statuses),
            'priority' => $this->faker->randomElement($priorities),
            'first_contact_date' => $firstContactDate,
            'last_contact_date' => $lastContactDate,
            'preferred_currency' => $this->faker->randomElement($currencies),
            'payment_terms' => $this->faker->randomElement($paymentTerms),
            'credit_limit' => $this->faker->optional(0.7)->randomFloat(2, 10000, 500000),
            'outstanding_balance' => $this->faker->randomFloat(2, 0, 50000),
            'additional_contacts' => $this->generateAdditionalContacts(),
            'communication_preferences' => $this->generateCommunicationPreferences(),
            'notes' => $this->faker->optional(0.6)->paragraph(),
            'requirements' => $this->faker->optional(0.5)->paragraph(),
            'lead_source' => $this->faker->randomElement($leadSources),
            'assigned_sales_rep' => null, // Will be set after employees are created
            'contract_start_date' => $contractStartDate,
            'contract_end_date' => $contractEndDate,
            'auto_renewal' => $this->faker->boolean(30),
        ];
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
                    'IT', 'Finance', 'Operations', 'HR', 'Marketing', 'Sales'
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
            'newsletter' => $this->faker->boolean(70),
            'marketing_emails' => $this->faker->boolean(50),
            'phone_calls' => $this->faker->boolean(60),
        ];
    }

    /**
     * Indicate that the customer is a prospect.
     */
    public function prospect(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'prospect',
            'contract_start_date' => null,
            'contract_end_date' => null,
            'outstanding_balance' => 0,
        ]);
    }

    /**
     * Indicate that the customer is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'contract_start_date' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
        ]);
    }

    /**
     * Indicate that the customer is VIP.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'vip',
            'status' => 'active',
            'annual_revenue' => $this->faker->randomFloat(2, 1000000, 50000000),
            'credit_limit' => $this->faker->randomFloat(2, 100000, 1000000),
        ]);
    }

    /**
     * Indicate that the customer is a large enterprise.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_size' => 'enterprise',
            'annual_revenue' => $this->faker->randomFloat(2, 10000000, 100000000),
            'credit_limit' => $this->faker->randomFloat(2, 500000, 2000000),
            'priority' => $this->faker->randomElement(['high', 'vip']),
        ]);
    }

    /**
     * Indicate that the customer has an expired contract.
     */
    public function expiredContract(): static
    {
        $contractStart = $this->faker->dateTimeBetween('-2 years', '-1 year');
        $contractEnd = $this->faker->dateTimeBetween($contractStart, '-1 month');

        return $this->state(fn (array $attributes) => [
            'contract_start_date' => $contractStart,
            'contract_end_date' => $contractEnd,
            'status' => 'former',
        ]);
    }

    /**
     * Indicate that the customer has outstanding balance.
     */
    public function withOutstandingBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'outstanding_balance' => $this->faker->randomFloat(2, 1000, 25000),
            'status' => 'active',
        ]);
    }
}
