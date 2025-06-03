<?php

namespace Database\Seeders;

use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some specific high-value customers
        $salesRep = Employee::where('department', 'Sales')->first();
        
        Customer::firstOrCreate(
            ['email' => 'contact@techcorp.com'],
            [
                'company_name' => 'TechCorp Solutions',
                'contact_person' => 'Michael Johnson',
                'phone' => '+1-555-1000',
                'website' => 'https://techcorp.com',
                'address_line_1' => '100 Technology Plaza',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postal_code' => '94105',
                'country' => 'United States',
                'industry' => 'Technology',
                'company_size' => 'large',
                'annual_revenue' => 50000000,
                'status' => 'active',
                'priority' => 'high',
                'lead_source' => 'referral',
                'assigned_sales_rep' => $salesRep?->id,
                'tax_id' => '12-3456789',
                'credit_limit' => 100000,
                'outstanding_balance' => 15000,
                'payment_terms' => 'net-30',
                'preferred_currency' => 'USD',
                'first_contact_date' => now()->subYears(2),
                'last_contact_date' => now()->subDays(5),
                'contract_start_date' => now()->subYear(),
                'contract_end_date' => now()->addYear(),
                'additional_contacts' => [
                    [
                        'name' => 'Sarah Wilson',
                        'title' => 'CTO',
                        'email' => 'sarah.wilson@techcorp.com',
                        'phone' => '+1-555-1001'
                    ],
                    [
                        'name' => 'David Chen',
                        'title' => 'Project Manager',
                        'email' => 'david.chen@techcorp.com',
                        'phone' => '+1-555-1002'
                    ]
                ],
                'communication_preferences' => [
                    'preferred_method' => 'email',
                    'meeting_frequency' => 'weekly',
                    'reporting_format' => 'detailed'
                ],
                'notes' => 'Long-term strategic partner. Prefers detailed technical documentation and regular progress updates.'
            ]
        );

        Customer::firstOrCreate(
            ['email' => 'info@globalretail.com'],
            [
                'company_name' => 'Global Retail Inc.',
                'contact_person' => 'Jennifer Martinez',
                'phone' => '+1-555-2000',
                'website' => 'https://globalretail.com',
                'address_line_1' => '500 Commerce Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'United States',
                'industry' => 'Retail',
                'company_size' => 'large',
                'annual_revenue' => 75000000,
                'status' => 'active',
                'priority' => 'high',
                'lead_source' => 'website',
                'assigned_sales_rep' => $salesRep?->id,
                'tax_id' => '98-7654321',
                'credit_limit' => 150000,
                'outstanding_balance' => 25000,
                'payment_terms' => 'net-45',
                'preferred_currency' => 'USD',
                'first_contact_date' => now()->subMonths(18),
                'last_contact_date' => now()->subDays(2),
                'contract_start_date' => now()->subMonths(12),
                'contract_end_date' => now()->addMonths(12),
                'additional_contacts' => [
                    [
                        'name' => 'Robert Kim',
                        'title' => 'IT Director',
                        'email' => 'robert.kim@globalretail.com',
                        'phone' => '+1-555-2001'
                    ]
                ],
                'communication_preferences' => [
                    'preferred_method' => 'phone',
                    'meeting_frequency' => 'bi-weekly',
                    'reporting_format' => 'summary'
                ],
                'notes' => 'Fast-growing retail chain. Requires scalable solutions and quick turnaround times.'
            ]
        );

        Customer::firstOrCreate(
            ['email' => 'contact@healthplus.org'],
            [
                'company_name' => 'HealthPlus Medical Center',
                'contact_person' => 'Dr. Amanda Foster',
                'phone' => '+1-555-3000',
                'website' => 'https://healthplus.org',
                'address_line_1' => '200 Medical Drive',
                'city' => 'Boston',
                'state' => 'MA',
                'postal_code' => '02101',
                'country' => 'United States',
                'industry' => 'Healthcare',
                'company_size' => 'medium',
                'annual_revenue' => 25000000,
                'status' => 'active',
                'priority' => 'medium',
                'lead_source' => 'conference',
                'assigned_sales_rep' => $salesRep?->id,
                'tax_id' => '55-1234567',
                'credit_limit' => 75000,
                'outstanding_balance' => 8000,
                'payment_terms' => 'net-30',
                'preferred_currency' => 'USD',
                'first_contact_date' => now()->subMonths(8),
                'last_contact_date' => now()->subWeek(),
                'contract_start_date' => now()->subMonths(6),
                'contract_end_date' => now()->addMonths(18),
                'additional_contacts' => [
                    [
                        'name' => 'Mark Thompson',
                        'title' => 'IT Manager',
                        'email' => 'mark.thompson@healthplus.org',
                        'phone' => '+1-555-3001'
                    ]
                ],
                'communication_preferences' => [
                    'preferred_method' => 'email',
                    'meeting_frequency' => 'monthly',
                    'reporting_format' => 'detailed'
                ],
                'notes' => 'Healthcare organization with strict compliance requirements. HIPAA compliance is essential.'
            ]
        );

        // Create additional random customers
        Customer::factory(20)->create();

        // Create some prospects (potential customers)
        Customer::factory(8)->create([
            'status' => 'prospect',
            'outstanding_balance' => 0,
            'contract_start_date' => null,
            'contract_end_date' => null,
        ]);

        // Create some inactive customers
        Customer::factory(3)->create([
            'status' => 'inactive',
            'contract_end_date' => now()->subMonths(rand(1, 12)),
        ]);

        $this->command->info('Created customers: 3 specific customers + 20 active + 8 prospects + 3 inactive');
    }
} 