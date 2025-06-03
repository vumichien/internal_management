<?php

namespace Database\Seeders;

use App\Models\Project\Project;
use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customers and project managers
        $techCorp = Customer::where('email', 'contact@techcorp.com')->first();
        $globalRetail = Customer::where('email', 'info@globalretail.com')->first();
        $healthPlus = Customer::where('email', 'contact@healthplus.org')->first();
        
        $projectManager = Employee::whereHas('user', function ($query) {
            $query->where('email', 'manager@company.com');
        })->first();
        
        $engineeringManagers = Employee::where('department', 'Engineering')
            ->whereNotNull('manager_id')
            ->get();

        // Create specific high-value projects
        if ($techCorp && $projectManager) {
            Project::firstOrCreate(
                ['name' => 'E-commerce Platform Redesign - TechCorp Solutions'],
                [
                    'description' => 'Complete redesign and modernization of TechCorp\'s e-commerce platform using Laravel and React. Includes payment integration, inventory management, and analytics dashboard.',
                    'start_date' => now()->subMonths(6),
                    'end_date' => now()->addMonths(6),
                    'status' => 'active',
                    'priority' => 'high',
                    'budget' => 250000,
                    'actual_cost' => 150000,
                    'estimated_hours' => 1500,
                    'actual_hours' => 900,
                    'completion_percentage' => 60,
                    'customer_id' => $techCorp->id,
                    'project_manager_id' => $projectManager->id,
                    'category' => 'Software Development',
                    'type' => 'web-development',
                    'billing_type' => 'fixed',
                    'hourly_rate' => 125.00,
                    'is_billable' => true,
                    'currency' => 'USD',
                    'risk_level' => 'medium',
                    'custom_attributes' => [
                        'client_requirements' => [
                            'Mobile-responsive design',
                            'Payment gateway integration',
                            'Real-time inventory tracking',
                            'Advanced analytics dashboard'
                        ],
                        'technical_stack' => ['Laravel', 'React', 'MySQL', 'Redis', 'AWS'],
                        'deliverables' => [
                            'Frontend application',
                            'Backend API',
                            'Admin dashboard',
                            'Documentation'
                        ],
                        'success_criteria' => [
                            'Page load time under 2 seconds',
                            '99.9% uptime',
                            'Mobile compatibility'
                        ]
                    ],
                    'milestones' => [
                        [
                            'name' => 'Project Kickoff',
                            'date' => now()->subMonths(6)->format('Y-m-d'),
                            'status' => 'completed'
                        ],
                        [
                            'name' => 'Phase 1 - Backend API',
                            'date' => now()->subMonths(3)->format('Y-m-d'),
                            'status' => 'completed'
                        ],
                        [
                            'name' => 'Phase 2 - Frontend',
                            'date' => now()->addMonths(2)->format('Y-m-d'),
                            'status' => 'pending'
                        ]
                    ],
                    'deliverables' => [
                        'Frontend application',
                        'Backend API',
                        'Admin dashboard',
                        'Documentation',
                        'Testing reports'
                    ],
                    'requirements' => 'Must be mobile-responsive and support high traffic loads. Integration with existing payment systems required.',
                    'notes' => 'Strategic project for long-term client. Regular weekly meetings scheduled. Client prefers detailed progress reports.'
                ]
            );
        }

        if ($globalRetail && $projectManager) {
            Project::firstOrCreate(
                ['name' => 'Inventory Management System - Global Retail Inc.'],
                [
                    'description' => 'Development of a comprehensive inventory management system with real-time tracking, automated reordering, and multi-location support.',
                    'start_date' => now()->subMonths(3),
                    'end_date' => now()->addMonths(9),
                    'status' => 'active',
                    'priority' => 'high',
                    'budget' => 180000,
                    'actual_cost' => 75000,
                    'estimated_hours' => 1200,
                    'actual_hours' => 450,
                    'completion_percentage' => 35,
                    'customer_id' => $globalRetail->id,
                    'project_manager_id' => $projectManager->id,
                    'category' => 'Software Development',
                    'type' => 'web-development',
                    'billing_type' => 'milestone',
                    'hourly_rate' => 110.00,
                    'is_billable' => true,
                    'currency' => 'USD',
                    'risk_level' => 'low',
                    'custom_attributes' => [
                        'client_requirements' => [
                            'Multi-location inventory tracking',
                            'Automated reorder points',
                            'Barcode scanning integration',
                            'Reporting and analytics'
                        ],
                        'technical_stack' => ['Laravel', 'Vue.js', 'PostgreSQL', 'Docker'],
                        'deliverables' => [
                            'Web application',
                            'Mobile app',
                            'API documentation',
                            'Training materials'
                        ],
                        'success_criteria' => [
                            'Real-time inventory accuracy',
                            'Reduced stockouts by 50%',
                            'User training completion'
                        ]
                    ],
                    'notes' => 'Fast-paced project with aggressive timeline. Client requires bi-weekly demos.'
                ]
            );
        }

        if ($healthPlus) {
            $healthPlusManager = $engineeringManagers->first() ?? $projectManager;
            Project::firstOrCreate(
                ['name' => 'Patient Portal Development - HealthPlus Medical Center'],
                [
                    'description' => 'HIPAA-compliant patient portal allowing appointment scheduling, medical record access, and secure messaging with healthcare providers.',
                    'start_date' => now()->subMonths(2),
                    'end_date' => now()->addMonths(10),
                    'status' => 'active',
                    'priority' => 'medium',
                    'budget' => 120000,
                    'actual_cost' => 35000,
                    'estimated_hours' => 800,
                    'actual_hours' => 200,
                    'completion_percentage' => 25,
                    'customer_id' => $healthPlus->id,
                    'project_manager_id' => $healthPlusManager->id,
                    'category' => 'Software Development',
                    'type' => 'web-development',
                    'billing_type' => 'hourly',
                    'hourly_rate' => 95.00,
                    'is_billable' => true,
                    'currency' => 'USD',
                    'risk_level' => 'high',
                    'custom_attributes' => [
                        'client_requirements' => [
                            'HIPAA compliance',
                            'Appointment scheduling',
                            'Secure messaging',
                            'Medical record access'
                        ],
                        'technical_stack' => ['Laravel', 'React', 'MySQL', 'AWS'],
                        'deliverables' => [
                            'Patient portal',
                            'Provider dashboard',
                            'Security documentation',
                            'Compliance audit'
                        ],
                        'success_criteria' => [
                            'HIPAA compliance certification',
                            'Security audit passed',
                            'User acceptance testing'
                        ]
                    ],
                    'notes' => 'High compliance requirements. Regular security reviews required. Client has strict data protection policies.'
                ]
            );
        }

        // Create completed project
        if ($techCorp && $projectManager) {
            Project::firstOrCreate(
                ['name' => 'API Integration Project - TechCorp Solutions'],
                [
                    'description' => 'Integration of third-party APIs for payment processing, shipping, and customer analytics.',
                    'start_date' => now()->subYear(),
                    'end_date' => now()->subMonths(8),
                    'actual_end_date' => now()->subMonths(8),
                    'status' => 'completed',
                    'priority' => 'medium',
                    'budget' => 75000,
                    'actual_cost' => 68000,
                    'estimated_hours' => 500,
                    'actual_hours' => 485,
                    'completion_percentage' => 100,
                    'customer_id' => $techCorp->id,
                    'project_manager_id' => $projectManager->id,
                    'category' => 'Software Development',
                    'type' => 'integration',
                    'billing_type' => 'fixed',
                    'hourly_rate' => 100.00,
                    'is_billable' => true,
                    'currency' => 'USD',
                    'risk_level' => 'low',
                    'custom_attributes' => [
                        'client_requirements' => [
                            'Payment gateway integration',
                            'Shipping API integration',
                            'Analytics API setup'
                        ],
                        'technical_stack' => ['Laravel', 'REST APIs', 'OAuth'],
                        'deliverables' => [
                            'API integrations',
                            'Documentation',
                            'Testing suite'
                        ],
                        'success_criteria' => [
                            'All APIs functional',
                            'Error handling implemented',
                            'Performance benchmarks met'
                        ]
                    ],
                    'notes' => 'Successfully completed project. Client very satisfied with results. Good reference for future API work.'
                ]
            );
        }

        // Create additional random projects
        // Project::factory(12)->create();

        // Create some completed projects
        // Project::factory(5)->completed()->create();

        // Create some high priority projects
        // Project::factory(3)->highPriority()->create();

        // Create some archived projects
        // Project::factory(2)->archived()->create();

        $this->command->info('Created projects: 4 specific projects (factory calls temporarily disabled)');
    }
} 