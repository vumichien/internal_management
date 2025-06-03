<?php

namespace Database\Seeders;

use App\Models\Vendor\Vendor;
use App\Models\Employee\Employee;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get procurement representative
        $procurementRep = Employee::where('department', 'Operations')->first() 
            ?? Employee::first();

        // Create specific strategic vendors
        Vendor::firstOrCreate(
            ['email' => 'contact@cloudservices.com'],
            [
                'company_name' => 'CloudServices Pro',
                'contact_person' => 'Alex Thompson',
                'phone' => '+1-555-4000',
                'website' => 'https://cloudservices.com',
                'address_line_1' => '300 Cloud Avenue',
                'city' => 'Seattle',
                'state' => 'WA',
                'postal_code' => '98101',
                'country' => 'United States',
                'vendor_type' => 'service_provider',
                'service_type' => 'IT Services',
                'industry' => 'Technology',
                'payment_terms' => 'net-30',
                'tax_id' => '11-2233445',
                'business_license' => 'BL-123456',
                'status' => 'active',
                'priority' => 'high',
                'credit_limit' => 50000,
                'outstanding_balance' => 12000,
                'performance_rating' => 4.8,
                'delivery_success_rate' => 98,
                'average_delivery_time' => 2,
                'last_performance_review' => now()->subMonths(3),
                'first_contact_date' => now()->subYear(2),
                'last_contact_date' => now()->subDays(5),
                'contract_start_date' => now()->subYear(),
                'contract_end_date' => now()->addYears(2),
                'auto_renewal' => true,
                'insurance_verified' => true,
                'insurance_expiry_date' => now()->addMonths(8),
                'background_check_completed' => true,
                'background_check_date' => now()->subMonths(6),
                'assigned_procurement_rep' => $procurementRep?->id,
                'preferred_currency' => 'USD',
                'bank_account_info' => json_encode([
                    'bank_name' => 'First National Bank',
                    'account_number' => '1234567890',
                    'routing_number' => '123456789',
                    'swift_code' => 'FNBKUS33'
                ]),
                'services_provided' => json_encode([
                    'Cloud Infrastructure',
                    'DevOps Services',
                    'System Monitoring',
                    'Technical Support'
                ]),
                'additional_contacts' => json_encode([
                    [
                        'name' => 'Sarah Johnson',
                        'title' => 'Technical Account Manager',
                        'email' => 'sarah.johnson@cloudservices.com',
                        'phone' => '+1-555-4001',
                        'department' => 'Technical',
                        'is_primary' => true
                    ]
                ]),
                'communication_preferences' => json_encode([
                    'preferred_method' => 'email',
                    'best_time' => 'morning',
                    'timezone' => 'America/Los_Angeles',
                    'frequency' => 'weekly'
                ]),
                'lead_source' => 'referral',
                'notes' => 'Primary cloud infrastructure provider. Excellent uptime and support response times.'
            ]
        );

        Vendor::firstOrCreate(
            ['email' => 'billing@legalexperts.com'],
            [
                'company_name' => 'Legal Experts LLP',
                'contact_person' => 'Patricia Williams',
                'phone' => '+1-555-5000',
                'website' => 'https://legalexperts.com',
                'address_line_1' => '150 Law Street',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60601',
                'country' => 'United States',
                'vendor_type' => 'consultant',
                'service_type' => 'Legal',
                'industry' => 'Legal',
                'payment_terms' => 'net-15',
                'tax_id' => '22-3344556',
                'business_license' => 'BL-789012',
                'status' => 'active',
                'priority' => 'medium',
                'credit_limit' => 25000,
                'outstanding_balance' => 5500,
                'performance_rating' => 4.5,
                'delivery_success_rate' => 95,
                'average_delivery_time' => 5,
                'last_performance_review' => now()->subMonths(6),
                'first_contact_date' => now()->subYear(2),
                'last_contact_date' => now()->subDays(10),
                'contract_start_date' => now()->subMonths(18),
                'contract_end_date' => now()->addMonths(6),
                'auto_renewal' => false,
                'insurance_verified' => true,
                'insurance_expiry_date' => now()->addMonths(10),
                'background_check_completed' => true,
                'background_check_date' => now()->subMonths(8),
                'assigned_procurement_rep' => $procurementRep?->id,
                'preferred_currency' => 'USD',
                'bank_account_info' => json_encode([
                    'bank_name' => 'Chicago Trust Bank',
                    'account_number' => '9876543210',
                    'routing_number' => '987654321',
                    'swift_code' => 'CTBKUS44'
                ]),
                'services_provided' => json_encode([
                    'Contract Review',
                    'Compliance Consulting',
                    'Intellectual Property',
                    'Employment Law'
                ]),
                'additional_contacts' => json_encode([
                    [
                        'name' => 'Michael Chen',
                        'title' => 'Senior Partner',
                        'email' => 'michael.chen@legalexperts.com',
                        'phone' => '+1-555-5001',
                        'department' => 'Legal',
                        'is_primary' => true
                    ]
                ]),
                'communication_preferences' => json_encode([
                    'preferred_method' => 'phone',
                    'best_time' => 'afternoon',
                    'timezone' => 'America/Chicago',
                    'frequency' => 'monthly'
                ]),
                'lead_source' => 'referral',
                'notes' => 'Specialized in technology law and intellectual property. Responsive and knowledgeable team.'
            ]
        );

        Vendor::firstOrCreate(
            ['email' => 'accounts@officesupplies.com'],
            [
                'company_name' => 'Office Supplies Direct',
                'contact_person' => 'James Rodriguez',
                'phone' => '+1-555-6000',
                'website' => 'https://officesupplies.com',
                'address_line_1' => '500 Supply Chain Drive',
                'city' => 'Dallas',
                'state' => 'TX',
                'postal_code' => '75201',
                'country' => 'United States',
                'vendor_type' => 'supplier',
                'service_type' => 'Office Supplies',
                'industry' => 'Retail',
                'payment_terms' => 'net-30',
                'tax_id' => '33-4455667',
                'business_license' => 'BL-345678',
                'status' => 'active',
                'priority' => 'low',
                'credit_limit' => 10000,
                'outstanding_balance' => 1200,
                'performance_rating' => 4.2,
                'delivery_success_rate' => 92,
                'average_delivery_time' => 3,
                'last_performance_review' => now()->subMonths(12),
                'first_contact_date' => now()->subYears(3),
                'last_contact_date' => now()->subDays(15),
                'contract_start_date' => now()->subYears(2),
                'contract_end_date' => now()->addYear(),
                'auto_renewal' => true,
                'insurance_verified' => true,
                'insurance_expiry_date' => now()->addMonths(6),
                'background_check_completed' => false,
                'background_check_date' => null,
                'assigned_procurement_rep' => $procurementRep?->id,
                'preferred_currency' => 'USD',
                'bank_account_info' => json_encode([
                    'bank_name' => 'Texas Commerce Bank',
                    'account_number' => '5555666677',
                    'routing_number' => '555666777',
                    'swift_code' => null
                ]),
                'services_provided' => json_encode([
                    'Office Furniture',
                    'Stationery',
                    'Computer Accessories',
                    'Cleaning Supplies'
                ]),
                'additional_contacts' => json_encode([
                    [
                        'name' => 'Maria Gonzalez',
                        'title' => 'Account Manager',
                        'email' => 'maria.gonzalez@officesupplies.com',
                        'phone' => '+1-555-6001',
                        'department' => 'Sales',
                        'is_primary' => true
                    ]
                ]),
                'communication_preferences' => json_encode([
                    'preferred_method' => 'email',
                    'best_time' => 'morning',
                    'timezone' => 'America/Chicago',
                    'frequency' => 'monthly'
                ]),
                'lead_source' => 'website',
                'notes' => 'Reliable supplier for general office needs. Competitive pricing and bulk discounts available.'
            ]
        );

        // Create additional random vendors
        Vendor::factory(15)->create();

        // Create some pending vendors (in evaluation)
        Vendor::factory(3)->create([
            'status' => 'pending',
            'contract_start_date' => null,
            'contract_end_date' => null,
            'outstanding_balance' => 0,
        ]);

        // Create some suspended vendors
        Vendor::factory(2)->create([
            'status' => 'suspended',
            'performance_rating' => 2.5,
            'delivery_success_rate' => 75,
        ]);

        $this->command->info('Created vendors: 3 specific vendors + 15 active + 3 pending + 2 suspended');
    }
} 