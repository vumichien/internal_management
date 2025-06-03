<?php

namespace Database\Factories;

use App\Models\Project\ProjectAssignment;
use App\Models\Project\Project;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project\ProjectAssignment>
 */
class ProjectAssignmentFactory extends Factory
{
    protected $model = ProjectAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a start date that's reasonable for project assignments
        $startDate = $this->faker->dateTimeBetween('-2 years', '+3 months');
        
        // Generate end date that's always after start date, or null for ongoing assignments
        $endDate = $this->faker->optional(0.7)->dateTimeBetween(
            $startDate, // Use start date directly
            '+2 years'
        );
        
        $roles = [
            'Project Manager', 'Lead Developer', 'Senior Developer', 'Developer',
            'Designer', 'QA Engineer', 'DevOps Engineer', 'Business Analyst',
            'Technical Writer', 'Consultant', 'Architect', 'Scrum Master'
        ];
        
        $statuses = ['active', 'completed', 'on-hold', 'cancelled'];
        $billableTypes = ['billable', 'non-billable', 'internal'];
        
        // Allocation percentage based on role
        $role = $this->faker->randomElement($roles);
        $allocationPercentage = match($role) {
            'Project Manager' => $this->faker->numberBetween(20, 50),
            'Lead Developer', 'Architect' => $this->faker->numberBetween(60, 100),
            'Senior Developer', 'Developer' => $this->faker->numberBetween(40, 100),
            'Designer', 'QA Engineer' => $this->faker->numberBetween(30, 80),
            'Consultant', 'Business Analyst' => $this->faker->numberBetween(20, 60),
            default => $this->faker->numberBetween(25, 75)
        };
        
        $hourlyRate = match($role) {
            'Project Manager', 'Architect' => $this->faker->randomFloat(2, 80, 150),
            'Lead Developer' => $this->faker->randomFloat(2, 70, 120),
            'Senior Developer' => $this->faker->randomFloat(2, 60, 100),
            'Developer' => $this->faker->randomFloat(2, 40, 80),
            'Designer' => $this->faker->randomFloat(2, 50, 90),
            'QA Engineer' => $this->faker->randomFloat(2, 45, 75),
            'Consultant' => $this->faker->randomFloat(2, 60, 120),
            default => $this->faker->randomFloat(2, 35, 85)
        };
        
        $isActive = $this->faker->boolean(80);
        $status = $isActive ? 'active' : $this->faker->randomElement(['completed', 'on-hold', 'cancelled']);
        
        $estimatedHours = $this->faker->numberBetween(20, 200);
        $actualHours = $status === 'completed' 
            ? $this->faker->numberBetween($estimatedHours * 0.8, $estimatedHours * 1.2)
            : ($status === 'active' ? $this->faker->numberBetween(0, $estimatedHours * 0.8) : 0);
        
        $completionPercentage = match($status) {
            'active' => $this->faker->numberBetween(10, 90),
            'completed' => 100,
            'on-hold' => $this->faker->numberBetween(20, 70),
            'cancelled' => $this->faker->numberBetween(0, 50),
            default => 0
        };
        
        // Generate actual end date only for completed assignments, ensuring it's after start date
        $actualEndDate = null;
        if ($status === 'completed') {
            $maxEndDate = $endDate ?: new \DateTime();
            $actualEndDate = $this->faker->dateTimeBetween($startDate, $maxEndDate);
        }
        
        return [
            'project_id' => Project::factory(),
            'employee_id' => Employee::factory(),
            'role_on_project' => $role,
            'allocation_percentage' => $allocationPercentage,
            'hourly_rate' => $hourlyRate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'actual_end_date' => $actualEndDate,
            'status' => $status,
            'is_billable' => $this->faker->boolean(85),
            'is_primary_assignment' => $this->faker->boolean(30),
            'estimated_hours' => $estimatedHours,
            'actual_hours' => $actualHours,
            'completion_percentage' => $completionPercentage,
            'assigned_by' => User::factory(),
            'assigned_at' => $startDate > new \DateTime() ? $startDate : $this->faker->dateTimeBetween($startDate, 'now'),
            'approved_by' => $this->faker->optional(0.8)->randomElement([User::factory()]),
            'approved_at' => $this->faker->optional(0.8)->dateTimeBetween($startDate, $startDate > new \DateTime() ? $startDate : 'now'),
            'performance_rating' => $status === 'completed' ? $this->faker->randomFloat(2, 3.0, 5.0) : null,
            'last_performance_review' => $status === 'completed' ? $this->faker->dateTimeBetween($startDate, $actualEndDate ?: 'now') : null,
            'performance_notes' => $status === 'completed' ? $this->faker->optional(0.6)->paragraph() : null,
            'notes' => $this->faker->optional(0.4)->paragraph(),
            'skills_required' => $this->generateSkillsRequired($role),
            'responsibilities' => $this->generateResponsibilities($role),
            'deliverables' => $this->generateDeliverables($role),
        ];
    }

    /**
     * Generate skills required based on role
     */
    private function generateSkillsRequired(string $role): array
    {
        $skillSets = [
            'Project Manager' => ['Project Management', 'Agile', 'Scrum', 'Leadership', 'Communication', 'Risk Management'],
            'Lead Developer' => ['PHP', 'Laravel', 'JavaScript', 'Leadership', 'Code Review', 'Architecture'],
            'Senior Developer' => ['PHP', 'Laravel', 'JavaScript', 'MySQL', 'Git', 'Testing'],
            'Developer' => ['PHP', 'Laravel', 'JavaScript', 'HTML', 'CSS', 'Git'],
            'Designer' => ['UI/UX Design', 'Figma', 'Adobe Creative Suite', 'Prototyping', 'User Research'],
            'QA Engineer' => ['Testing', 'Automation', 'Bug Tracking', 'Test Planning', 'Quality Assurance'],
            'DevOps Engineer' => ['Docker', 'AWS', 'CI/CD', 'Linux', 'Monitoring', 'Infrastructure'],
            'Business Analyst' => ['Requirements Analysis', 'Documentation', 'Process Mapping', 'Stakeholder Management'],
            'Architect' => ['System Design', 'Architecture Patterns', 'Scalability', 'Performance', 'Security'],
            'Consultant' => ['Domain Expertise', 'Analysis', 'Recommendations', 'Communication', 'Problem Solving']
        ];

        $roleSkills = $skillSets[$role] ?? ['Communication', 'Problem Solving', 'Teamwork'];
        $maxSkills = count($roleSkills);
        $numSkills = min($this->faker->numberBetween(2, 5), $maxSkills);
        
        return array_slice($this->faker->randomElements($roleSkills, $numSkills, false), 0, $numSkills);
    }

    /**
     * Generate responsibilities based on role
     */
    private function generateResponsibilities(string $role): array
    {
        $responsibilitySets = [
            'Project Manager' => [
                'Oversee project timeline and deliverables',
                'Coordinate team activities and resources',
                'Manage stakeholder communication',
                'Monitor project budget and scope',
                'Conduct regular team meetings'
            ],
            'Lead Developer' => [
                'Lead technical development efforts',
                'Review code and ensure quality standards',
                'Mentor junior developers',
                'Make architectural decisions',
                'Coordinate with other teams'
            ],
            'Senior Developer' => [
                'Develop complex features and components',
                'Participate in code reviews',
                'Mentor junior team members',
                'Contribute to technical decisions',
                'Ensure code quality and best practices'
            ],
            'Developer' => [
                'Implement assigned features and bug fixes',
                'Write unit tests for developed code',
                'Participate in code reviews',
                'Follow coding standards and guidelines',
                'Collaborate with team members'
            ],
            'Designer' => [
                'Create user interface designs',
                'Develop user experience flows',
                'Create prototypes and mockups',
                'Collaborate with development team',
                'Conduct user research and testing'
            ],
            'QA Engineer' => [
                'Develop and execute test plans',
                'Identify and report bugs',
                'Perform regression testing',
                'Automate testing processes',
                'Ensure quality standards are met'
            ]
        ];

        $roleResponsibilities = $responsibilitySets[$role] ?? [
            'Complete assigned tasks on time',
            'Collaborate with team members',
            'Follow project guidelines and standards'
        ];
        
        $maxResponsibilities = count($roleResponsibilities);
        $numResponsibilities = min($this->faker->numberBetween(2, 4), $maxResponsibilities);
        return array_slice($this->faker->randomElements($roleResponsibilities, $numResponsibilities, false), 0, $numResponsibilities);
    }

    /**
     * Generate deliverables based on role
     */
    private function generateDeliverables(string $role): array
    {
        $deliverableSets = [
            'Project Manager' => [
                'Project plan and timeline',
                'Status reports and updates',
                'Risk assessment and mitigation plan',
                'Resource allocation plan',
                'Project closure documentation'
            ],
            'Lead Developer' => [
                'Technical architecture documentation',
                'Code review reports',
                'Development standards and guidelines',
                'Technical mentoring plan',
                'Integration testing results'
            ],
            'Senior Developer' => [
                'Feature implementation',
                'Code documentation',
                'Unit test coverage',
                'Technical specifications',
                'Code review participation'
            ],
            'Developer' => [
                'Feature implementation',
                'Unit tests',
                'Code documentation',
                'Bug fixes',
                'Development progress reports'
            ],
            'Designer' => [
                'UI/UX designs and mockups',
                'Design system components',
                'User journey maps',
                'Prototypes and wireframes',
                'Design documentation'
            ],
            'QA Engineer' => [
                'Test plans and test cases',
                'Bug reports and tracking',
                'Test automation scripts',
                'Quality metrics reports',
                'Testing documentation'
            ]
        ];

        $roleDeliverables = $deliverableSets[$role] ?? [
            'Task completion reports',
            'Progress updates',
            'Documentation updates'
        ];
        
        $maxDeliverables = count($roleDeliverables);
        $numDeliverables = min($this->faker->numberBetween(2, 4), $maxDeliverables);
        return array_slice($this->faker->randomElements($roleDeliverables, $numDeliverables, false), 0, $numDeliverables);
    }

    /**
     * Indicate that the assignment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the assignment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completion_percentage' => 100,
            'end_date' => $this->faker->dateTimeBetween($attributes['start_date'], 'now'),
            'actual_end_date' => $this->faker->dateTimeBetween($attributes['start_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the assignment is for a primary role.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary_assignment' => true,
            'allocation_percentage' => $this->faker->numberBetween(60, 100),
        ]);
    }

    /**
     * Indicate that the assignment is billable.
     */
    public function billable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => true,
        ]);
    }
} 