<?php

namespace Database\Factories;

use App\Models\Employee\TimeEntry;
use App\Models\Employee\Employee;
use App\Models\Project\Project;
use App\Models\Project\ProjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ensure date is always in the past to avoid faker date issues
        $date = $this->faker->dateTimeBetween('-3 months', '-1 day');
        $hoursWorked = $this->faker->randomFloat(2, 0.5, 12.0);
        
        $statuses = ['draft', 'submitted', 'approved', 'rejected', 'locked'];
        $status = $this->faker->randomElement($statuses);
        
        $categories = [
            'Development', 'Testing', 'Design', 'Meeting', 'Documentation',
            'Research', 'Planning', 'Review', 'Training', 'Support',
            'Deployment', 'Bug Fix', 'Feature Development', 'Maintenance'
        ];
        
        $activities = [
            'Development' => [
                'Frontend development', 'Backend development', 'API development',
                'Database design', 'Code refactoring', 'Feature implementation'
            ],
            'Testing' => [
                'Unit testing', 'Integration testing', 'Manual testing',
                'Bug testing', 'Performance testing', 'User acceptance testing'
            ],
            'Design' => [
                'UI design', 'UX research', 'Wireframing', 'Prototyping',
                'Design review', 'Asset creation'
            ],
            'Meeting' => [
                'Team standup', 'Client meeting', 'Planning meeting',
                'Review meeting', 'Retrospective', 'Training session'
            ],
            'Documentation' => [
                'Technical documentation', 'User documentation', 'API documentation',
                'Process documentation', 'Code comments', 'Requirements documentation'
            ]
        ];
        
        $category = $this->faker->randomElement($categories);
        $activityList = $activities[$category] ?? ['General work'];
        $activity = $this->faker->randomElement($activityList);
        
        $submittedAt = null;
        $approvedAt = null;
        $approvedBy = null;
        $lockedAt = null;
        $lockedBy = null;
        
        // Set timestamps based on status - ensure proper chronological order
        if (in_array($status, ['submitted', 'approved', 'rejected', 'locked'])) {
            // Submitted date should be after the work date but before now
            $submittedAt = $this->faker->dateTimeBetween($date, '-1 hour');
        }
        
        if (in_array($status, ['approved', 'locked'])) {
            // Approved date should be after submitted date
            $approvedAt = $submittedAt ? 
                $this->faker->dateTimeBetween($submittedAt, 'now') : 
                $this->faker->dateTimeBetween($date, 'now');
            $approvedBy = User::inRandomOrder()->first()?->id;
        }
        
        if ($status === 'locked') {
            // Locked date should be after approved date
            $lockedAt = $approvedAt ? 
                $this->faker->dateTimeBetween($approvedAt, 'now') : 
                $this->faker->dateTimeBetween($date, 'now');
            $lockedBy = User::inRandomOrder()->first()?->id;
        }
        
        $hourlyRate = $this->faker->randomFloat(2, 40, 150);
        
        return [
            'employee_id' => Employee::inRandomOrder()->first()?->id ?? Employee::factory(),
            'project_id' => Project::inRandomOrder()->first()?->id ?? Project::factory(),
            'project_assignment_id' => ProjectAssignment::inRandomOrder()->first()?->id ?? ProjectAssignment::factory(),
            'date' => $date,
            'hours_worked' => $hoursWorked,
            'break_duration' => $this->faker->randomFloat(2, 0, 1.5),
            'description' => $activity . ': ' . $this->faker->sentence(),
            'task_category' => $category,
            'activity_type' => $activity,
            'status' => $status,
            'is_billable' => $this->faker->boolean(80),
            'hourly_rate' => $hourlyRate,
            'billable_amount' => $hoursWorked * $hourlyRate,
            'location' => $this->faker->randomElement(['office', 'remote', 'client-site', 'home']),
            'tags' => $this->generateTags($category),
            'submitted_at' => $submittedAt,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'rejection_reason' => $status === 'rejected' ? $this->faker->sentence() : null,
            'locked_at' => $lockedAt,
            'locked_by' => $lockedBy,
            'created_by' => User::inRandomOrder()->first()?->id,
            'updated_by' => User::inRandomOrder()->first()?->id,
            'metadata' => $this->faker->optional(0.3)->randomElements([
                'client_reference' => $this->faker->uuid,
                'external_task_id' => $this->faker->numerify('TASK-####'),
                'notes' => $this->faker->sentence(),
            ]),
        ];
    }

    /**
     * Generate relevant tags based on category
     */
    private function generateTags(string $category): array
    {
        $tagSets = [
            'Development' => ['frontend', 'backend', 'api', 'database', 'feature', 'refactor'],
            'Testing' => ['unit-test', 'integration', 'manual', 'automation', 'bug-fix', 'qa'],
            'Design' => ['ui', 'ux', 'wireframe', 'prototype', 'mockup', 'user-research'],
            'Meeting' => ['standup', 'planning', 'review', 'client', 'team', 'training'],
            'Documentation' => ['technical', 'user-guide', 'api-docs', 'process', 'requirements'],
            'Research' => ['investigation', 'analysis', 'poc', 'feasibility', 'technology'],
            'Planning' => ['estimation', 'roadmap', 'architecture', 'strategy', 'requirements'],
            'Review' => ['code-review', 'design-review', 'peer-review', 'quality-check'],
            'Training' => ['learning', 'workshop', 'certification', 'knowledge-transfer'],
            'Support' => ['customer-support', 'troubleshooting', 'maintenance', 'hotfix']
        ];

        $categoryTags = $tagSets[$category] ?? ['general'];
        $numTags = min($this->faker->numberBetween(1, 3), count($categoryTags));
        
        return array_slice($this->faker->randomElements($categoryTags, $numTags, false), 0, $numTags);
    }

    /**
     * Indicate that the time entry is submitted.
     */
    public function submitted(): static
    {
        return $this->state(function (array $attributes) {
            $workDate = $attributes['date'] instanceof \DateTime ? $attributes['date'] : new \DateTime($attributes['date']);
            $submittedAt = $this->faker->dateTimeBetween($workDate, 'now');
            
            return [
                'status' => 'submitted',
                'submitted_at' => $submittedAt,
            ];
        });
    }

    /**
     * Indicate that the time entry is approved.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $workDate = $attributes['date'] instanceof \DateTime ? $attributes['date'] : new \DateTime($attributes['date']);
            $submittedAt = $this->faker->dateTimeBetween($workDate, 'now');
            $approvedAt = $this->faker->dateTimeBetween($submittedAt, 'now');
            
            return [
                'status' => 'approved',
                'submitted_at' => $submittedAt,
                'approved_at' => $approvedAt,
                'approved_by' => User::inRandomOrder()->first()?->id,
            ];
        });
    }

    /**
     * Indicate that the time entry is rejected.
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            $workDate = $attributes['date'] instanceof \DateTime ? $attributes['date'] : new \DateTime($attributes['date']);
            $submittedAt = $this->faker->dateTimeBetween($workDate, 'now');
            
            return [
                'status' => 'rejected',
                'submitted_at' => $submittedAt,
                'rejection_reason' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the time entry is billable.
     */
    public function billable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => true,
        ]);
    }

    /**
     * Indicate that the time entry includes overtime.
     */
    public function overtime(): static
    {
        return $this->state(fn (array $attributes) => [
            'hours_worked' => $this->faker->randomFloat(2, 8.5, 12.0),
        ]);
    }

    /**
     * Indicate that the time entry is for remote work.
     */
    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'remote',
        ]);
    }
} 