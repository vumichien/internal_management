<?php

namespace Database\Seeders;

use App\Models\Project\ProjectAssignment;
use App\Models\Project\Project;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();
        $employees = Employee::where('status', 'active')->get();
        $users = User::all();

        if ($projects->isEmpty() || $employees->isEmpty()) {
            $this->command->warn('No projects or employees found. Skipping project assignments.');
            return;
        }

        $this->command->info("Found {$projects->count()} projects and {$employees->count()} active employees");

        // Assign employees to each project
        foreach ($projects as $project) {
            $numAssignments = min(rand(2, 6), $employees->count()); // Each project gets 2-6 team members, but not more than available
            $assignedEmployees = $employees->random($numAssignments);
            
            foreach ($assignedEmployees as $index => $employee) {
                // Determine role based on employee's job title and position in assignment
                $role = $this->determineRole($employee, $index === 0);
                
                // Calculate allocation based on role and project status
                $allocation = $this->calculateAllocation($role, $project->status);
                
                // Set hourly rate based on role and employee department
                $hourlyRate = $this->calculateHourlyRate($role, $employee->department);
                
                $startDate = $project->start_date;
                $endDate = $project->status === 'completed' ? $project->actual_end_date : null;
                $actualEndDate = $project->status === 'completed' ? $project->actual_end_date : null;
                
                $estimatedHours = rand(40, 200);
                $actualHours = $project->status === 'completed' 
                    ? rand($estimatedHours * 0.8, $estimatedHours * 1.2)
                    : ($project->status === 'active' ? rand(0, $estimatedHours * 0.8) : 0);
                
                $completionPercentage = match($project->status) {
                    'active' => rand(10, 90),
                    'completed' => 100,
                    'on-hold' => rand(20, 70),
                    'cancelled' => rand(0, 50),
                    default => 0
                };
                
                ProjectAssignment::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'role_on_project' => $role,
                        'allocation_percentage' => $allocation,
                        'hourly_rate' => $hourlyRate,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'actual_end_date' => $actualEndDate,
                        'status' => $this->getAssignmentStatus($project->status),
                        'is_billable' => $project->is_billable,
                        'is_primary_assignment' => $index === 0, // First assignment is primary
                        'estimated_hours' => $estimatedHours,
                        'actual_hours' => $actualHours,
                        'completion_percentage' => $completionPercentage,
                        'assigned_by' => $users->random()->id,
                        'assigned_at' => $startDate,
                        'approved_by' => $users->random()->id,
                        'approved_at' => $startDate,
                        'performance_rating' => $project->status === 'completed' ? rand(30, 50) / 10 : null,
                        'last_performance_review' => $project->status === 'completed' ? $actualEndDate : null,
                        'performance_notes' => $project->status === 'completed' ? 'Assignment completed successfully.' : null,
                        'notes' => $this->generateAssignmentNotes($role, $project),
                        'skills_required' => json_encode($this->getSkillsForRole($role)),
                        'responsibilities' => json_encode($this->getResponsibilitiesForRole($role)),
                        'deliverables' => json_encode($this->getDeliverablesForRole($role)),
                    ]
                );
            }
        }

        // Create some additional random assignments (temporarily disabled)
        // ProjectAssignment::factory(25)->create();

        // Create some completed assignments (temporarily disabled)
        // ProjectAssignment::factory(15)->completed()->create();

        // Create some primary assignments (temporarily disabled)
        // ProjectAssignment::factory(8)->primary()->create();

        $this->command->info('Created project assignments for all projects (factory calls temporarily disabled)');
    }

    /**
     * Determine role based on employee and position
     */
    private function determineRole(Employee $employee, bool $isFirst): string
    {
        if ($isFirst && str_contains(strtolower($employee->job_title), 'manager')) {
            return 'Project Manager';
        }

        $jobTitle = strtolower($employee->job_title);
        
        if (str_contains($jobTitle, 'lead') || str_contains($jobTitle, 'senior')) {
            return match($employee->department) {
                'Engineering' => 'Lead Developer',
                'Design' => 'Lead Designer',
                'QA' => 'QA Lead',
                default => 'Senior ' . $employee->department . ' Specialist'
            };
        }

        return match($employee->department) {
            'Engineering' => 'Developer',
            'Design' => 'Designer',
            'QA' => 'QA Engineer',
            'Marketing' => 'Marketing Specialist',
            'Sales' => 'Business Analyst',
            default => $employee->job_title
        };
    }

    /**
     * Calculate allocation percentage based on role and project status
     */
    private function calculateAllocation(string $role, string $projectStatus): int
    {
        $baseAllocation = match($role) {
            'Project Manager' => rand(30, 50),
            'Lead Developer', 'Architect' => rand(70, 100),
            'Senior Developer', 'Developer' => rand(50, 100),
            'Designer', 'QA Engineer' => rand(40, 80),
            'Business Analyst', 'Consultant' => rand(25, 60),
            default => rand(30, 70)
        };

        // Adjust based on project status
        return match($projectStatus) {
            'planned' => max(10, $baseAllocation - 20),
            'active' => $baseAllocation,
            'on-hold' => max(5, $baseAllocation - 40),
            'completed' => 0,
            'cancelled' => 0,
            default => $baseAllocation
        };
    }

    /**
     * Calculate hourly rate based on role and department
     */
    private function calculateHourlyRate(string $role, string $department): float
    {
        $baseRate = match($department) {
            'Engineering' => 85,
            'Design' => 75,
            'Marketing' => 65,
            'Sales' => 70,
            'QA' => 70,
            default => 60
        };

        $multiplier = match($role) {
            'Project Manager', 'Architect' => 1.4,
            'Lead Developer', 'Lead Designer' => 1.3,
            'Senior Developer', 'Senior Designer' => 1.2,
            'Developer', 'Designer' => 1.0,
            'QA Engineer' => 0.9,
            default => 1.0
        };

        return round($baseRate * $multiplier, 2);
    }

    /**
     * Get assignment status based on project status
     */
    private function getAssignmentStatus(string $projectStatus): string
    {
        return match($projectStatus) {
            'planned' => 'active',
            'active' => 'active',
            'on-hold' => 'on-hold',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'active'
        };
    }

    /**
     * Generate assignment notes
     */
    private function generateAssignmentNotes(string $role, Project $project): ?string
    {
        $notes = [
            'Project Manager' => 'Responsible for overall project coordination and client communication.',
            'Lead Developer' => 'Technical lead for development team. Code review responsibilities.',
            'Senior Developer' => 'Mentor junior developers and handle complex features.',
            'Developer' => 'Focus on feature development and bug fixes.',
            'Designer' => 'Create user interfaces and user experience designs.',
            'QA Engineer' => 'Ensure quality through testing and validation.',
            'Business Analyst' => 'Gather requirements and facilitate stakeholder communication.',
        ];

        $baseNote = $notes[$role] ?? 'Contributing to project success in assigned role.';
        
        if ($project->priority === 'high') {
            $baseNote .= ' High priority project requiring close attention to deadlines.';
        }

        return $baseNote;
    }

    /**
     * Get skills required for role
     */
    private function getSkillsForRole(string $role): array
    {
        return match($role) {
            'Project Manager' => ['Project Management', 'Agile', 'Communication', 'Leadership'],
            'Lead Developer' => ['PHP', 'Laravel', 'Leadership', 'Code Review', 'Architecture'],
            'Senior Developer' => ['PHP', 'Laravel', 'JavaScript', 'MySQL', 'Mentoring'],
            'Developer' => ['PHP', 'Laravel', 'JavaScript', 'HTML', 'CSS'],
            'Designer' => ['UI/UX Design', 'Figma', 'Adobe Creative Suite', 'Prototyping'],
            'QA Engineer' => ['Testing', 'Automation', 'Bug Tracking', 'Quality Assurance'],
            'Business Analyst' => ['Requirements Analysis', 'Documentation', 'Stakeholder Management'],
            default => ['Communication', 'Problem Solving', 'Teamwork']
        };
    }

    /**
     * Get responsibilities for role
     */
    private function getResponsibilitiesForRole(string $role): array
    {
        return match($role) {
            'Project Manager' => [
                'Coordinate project activities',
                'Manage stakeholder communication',
                'Monitor project timeline and budget',
                'Conduct team meetings'
            ],
            'Lead Developer' => [
                'Lead technical development',
                'Review code quality',
                'Mentor team members',
                'Make architectural decisions'
            ],
            'Senior Developer' => [
                'Develop complex features',
                'Code review participation',
                'Mentor junior developers',
                'Technical decision support'
            ],
            'Developer' => [
                'Implement assigned features',
                'Write unit tests',
                'Participate in code reviews',
                'Follow coding standards'
            ],
            'Designer' => [
                'Create UI/UX designs',
                'Develop prototypes',
                'Collaborate with development team',
                'Conduct user research'
            ],
            'QA Engineer' => [
                'Develop test plans',
                'Execute testing procedures',
                'Report and track bugs',
                'Ensure quality standards'
            ],
            default => [
                'Complete assigned tasks',
                'Collaborate with team',
                'Follow project guidelines'
            ]
        };
    }

    /**
     * Get deliverables for role
     */
    private function getDeliverablesForRole(string $role): array
    {
        $deliverables = [
            'Project Manager' => [
                'Project plan and timeline',
                'Status reports',
                'Risk assessment',
                'Resource allocation plan'
            ],
            'Lead Developer' => [
                'Technical architecture',
                'Code review reports',
                'Development guidelines',
                'Technical documentation'
            ],
            'Senior Developer' => [
                'Feature implementation',
                'Code documentation',
                'Unit tests',
                'Technical specifications'
            ],
            'Developer' => [
                'Feature implementation',
                'Unit tests',
                'Code documentation',
                'Bug fixes'
            ],
            'Designer' => [
                'UI/UX designs',
                'Design system',
                'Prototypes',
                'Design documentation'
            ],
            'QA Engineer' => [
                'Test plans',
                'Test cases',
                'Bug reports',
                'Quality metrics'
            ],
            'Business Analyst' => [
                'Requirements documentation',
                'Process flows',
                'User stories',
                'Acceptance criteria'
            ]
        ];

        return $deliverables[$role] ?? ['Task completion', 'Progress reports'];
    }
} 