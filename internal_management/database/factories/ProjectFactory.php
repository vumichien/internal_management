<?php

namespace Database\Factories;

use App\Models\Project\Project;
use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', '+3 months');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 years');
        $actualEndDate = null;
        
        $statuses = ['planned', 'active', 'on-hold', 'completed', 'cancelled'];
        $status = $this->faker->randomElement($statuses);
        
        // If project is completed, set actual end date
        if ($status === 'completed') {
            $actualEndDate = $this->faker->dateTimeBetween($startDate, $endDate);
        }
        
        $budget = $this->faker->numberBetween(10000, 500000);
        $actualCost = $status === 'completed' 
            ? $this->faker->numberBetween($budget * 0.7, $budget * 1.3)
            : ($status === 'active' ? $this->faker->numberBetween(0, $budget * 0.8) : 0);
        
        $estimatedHours = $this->faker->numberBetween(100, 2000);
        $actualHours = $status === 'completed' 
            ? $this->faker->numberBetween($estimatedHours * 0.8, $estimatedHours * 1.4)
            : ($status === 'active' ? $this->faker->numberBetween(0, $estimatedHours * 0.9) : 0);
        
        $completionPercentage = match($status) {
            'planned' => 0,
            'active' => $this->faker->numberBetween(10, 90),
            'on-hold' => $this->faker->numberBetween(20, 70),
            'completed' => 100,
            'cancelled' => $this->faker->numberBetween(0, 50),
            default => 0
        };
        
        $projectTypes = ['web-development', 'mobile-app', 'consulting', 'maintenance', 'integration', 'research', 'design'];
        $categories = ['Software Development', 'Consulting', 'Design', 'Marketing', 'Research', 'Support'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $riskLevels = ['low', 'medium', 'high'];
        $billingTypes = ['fixed', 'hourly', 'milestone'];
        $currencies = ['USD', 'EUR', 'GBP', 'CAD'];
        
        $projectNames = [
            'E-commerce Platform Redesign',
            'Mobile App Development',
            'Customer Portal Implementation',
            'Data Migration Project',
            'API Integration',
            'Website Optimization',
            'CRM System Upgrade',
            'Security Audit',
            'Performance Enhancement',
            'User Experience Improvement',
            'Database Modernization',
            'Cloud Migration',
            'Marketing Campaign',
            'Brand Identity Refresh',
            'Training Program Development'
        ];
        
        return [
            'name' => $this->faker->randomElement($projectNames) . ' - ' . $this->faker->company(),
            'description' => $this->faker->paragraphs(2, true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'actual_end_date' => $actualEndDate,
            'status' => $status,
            'priority' => $this->faker->randomElement($priorities),
            'budget' => $budget,
            'actual_cost' => $actualCost,
            'estimated_hours' => $estimatedHours,
            'actual_hours' => $actualHours,
            'completion_percentage' => $completionPercentage,
            'customer_id' => Customer::factory(),
            'project_manager_id' => Employee::factory(),
            'category' => $this->faker->randomElement($categories),
            'type' => $this->faker->randomElement($projectTypes),
            'billing_type' => $this->faker->randomElement($billingTypes),
            'hourly_rate' => $this->faker->randomFloat(2, 50, 200),
            'is_billable' => $this->faker->boolean(85),
            'currency' => $this->faker->randomElement($currencies),
            'risk_level' => $this->faker->randomElement($riskLevels),
            'custom_attributes' => [
                'client_requirements' => $this->faker->sentences(3),
                'technical_stack' => $this->faker->randomElements(['Laravel', 'React', 'Vue.js', 'MySQL', 'PostgreSQL', 'AWS', 'Docker'], 3),
                'deliverables' => $this->faker->sentences(2),
                'success_criteria' => $this->faker->sentences(2)
            ],
            'milestones' => [
                [
                    'name' => 'Project Kickoff',
                    'date' => $startDate->format('Y-m-d'),
                    'status' => 'completed'
                ],
                [
                    'name' => 'Phase 1 Completion',
                    'date' => $this->faker->dateTimeBetween($startDate, $endDate ?: '+1 year')->format('Y-m-d'),
                    'status' => $status === 'completed' ? 'completed' : 'pending'
                ]
            ],
            'deliverables' => [
                'Documentation',
                'Source Code',
                'Testing Reports',
                'Deployment Guide'
            ],
            'requirements' => $this->faker->optional(0.7)->paragraph(),
            'notes' => $this->faker->optional(0.6)->paragraph(),
            'archived_at' => $status === 'completed' && $this->faker->boolean(20) ? 
                $this->faker->dateTimeBetween($actualEndDate ?? $endDate ?? $startDate, 'now') : null,
            'is_archived' => false,
        ];
    }

    /**
     * Indicate that the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'completion_percentage' => $this->faker->numberBetween(10, 90),
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completion_percentage' => 100,
            'actual_end_date' => $this->faker->dateTimeBetween($attributes['start_date'], $attributes['end_date']),
        ]);
    }

    /**
     * Indicate that the project is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the project is archived.
     */
    public function archived(): static
    {
        return $this->state(function (array $attributes) {
            $endDate = $attributes['actual_end_date'] ?? $attributes['end_date'] ?? $attributes['start_date'];
            $minDate = new \DateTime('-1 year');
            $archiveFromDate = $endDate > $minDate ? $endDate : $minDate;
            
            return [
                'is_archived' => true,
                'archived_at' => $this->faker->dateTimeBetween($archiveFromDate, 'now'),
            ];
        });
    }
} 