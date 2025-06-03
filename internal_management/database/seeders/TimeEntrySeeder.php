<?php

namespace Database\Seeders;

use App\Models\Employee\TimeEntry;
use App\Models\Project\ProjectAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Time Entries...');
        
        $assignments = ProjectAssignment::where('status', 'active')
            ->with(['employee', 'project'])
            ->get();
        $users = User::pluck('id')->toArray();

        if ($assignments->isEmpty()) {
            $this->command->warn('No active project assignments found. Skipping time entries.');
            return;
        }

        // Create time entries for the last 3 months for active assignments
        $startDate = Carbon::now()->subMonths(3)->startOfDay();
        $endDate = Carbon::now()->subDay()->endOfDay(); // Don't include today to avoid future dates

        $this->command->info("Creating time entries from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        // Use bulk insert for better performance
        $timeEntries = [];
        $batchSize = 500; // Process in batches to avoid memory issues

        foreach ($assignments as $assignment) {
            $entries = $this->generateTimeEntriesForAssignment($assignment, $startDate, $endDate, $users);
            $timeEntries = array_merge($timeEntries, $entries);
            
            // Insert in batches to avoid memory issues
            if (count($timeEntries) >= $batchSize) {
                $this->insertBatch($timeEntries);
                $timeEntries = [];
            }
        }

        // Insert remaining entries
        if (!empty($timeEntries)) {
            $this->insertBatch($timeEntries);
        }

        // Create additional random time entries using factory (much smaller number for performance)
        $this->command->info('Creating additional random time entries...');
        
        // Reduce the number of factory-generated entries for better performance
        TimeEntry::factory(25)->create();
        TimeEntry::factory(15)->approved()->create();
        TimeEntry::factory(20)->billable()->create();
        TimeEntry::factory(10)->overtime()->create();
        TimeEntry::factory(15)->remote()->create();

        $totalEntries = TimeEntry::count();
        $this->command->info("Created {$totalEntries} time entries total");
    }

    /**
     * Generate time entries for a specific assignment
     */
    private function generateTimeEntriesForAssignment(ProjectAssignment $assignment, Carbon $startDate, Carbon $endDate, array $users): array
    {
        $entries = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Skip weekends (assuming 5-day work week)
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            // 70% chance of working on any given day (reduced from 80% for performance)
            if (rand(1, 100) <= 70) {
                $hoursWorked = $this->calculateHoursForDay($assignment, $currentDate);
                
                if ($hoursWorked > 0) {
                    $category = $this->selectCategory($assignment->role_on_project);
                    $activity = $this->selectActivity($category);
                    $status = $this->getTimeEntryStatus($currentDate);
                    
                    $entry = [
                        'entry_id' => $this->generateEntryId(),
                        'employee_id' => $assignment->employee_id,
                        'project_id' => $assignment->project_id,
                        'project_assignment_id' => $assignment->id,
                        'date' => $currentDate->format('Y-m-d'),
                        'hours_worked' => $hoursWorked,
                        'description' => $this->generateDescription($activity, $assignment),
                        'task_category' => $category,
                        'activity_type' => $activity,
                        'status' => $status,
                        'is_billable' => $assignment->billable_type === 'billable',
                        'hourly_rate' => $assignment->hourly_rate,
                        'billable_amount' => $hoursWorked * $assignment->hourly_rate,
                        'break_duration' => max(0, rand(30, 60) / 60),
                        'location' => $this->selectLocation(),
                        'tags' => json_encode($this->generateTags($category, $assignment)),
                        'submitted_at' => $this->getSubmittedAt($currentDate, $status),
                        'approved_by' => $this->getApprovedBy($status, $users),
                        'approved_at' => $this->getApprovedAt($currentDate, $status),
                        'created_by' => $users[array_rand($users)],
                        'updated_by' => $assignment->employee->user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    $entries[] = $entry;
                }
            }

            $currentDate->addDay();
        }
        
        return $entries;
    }

    /**
     * Insert batch of time entries
     */
    private function insertBatch(array $entries): void
    {
        if (empty($entries)) {
            return;
        }
        
        DB::table('time_entries')->insert($entries);
        $this->command->info("Inserted batch of " . count($entries) . " time entries");
    }

    /**
     * Generate unique entry ID
     */
    private function generateEntryId(): string
    {
        static $counter = null;
        
        if ($counter === null) {
            $lastEntry = DB::table('time_entries')->orderBy('id', 'desc')->first();
            $counter = $lastEntry ? (int) substr($lastEntry->entry_id, 2) + 1 : 1;
        }
        
        return 'TE' . str_pad($counter++, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate hours worked for a specific day based on allocation
     */
    private function calculateHoursForDay(ProjectAssignment $assignment, Carbon $date): float
    {
        $baseHours = 8; // Standard work day
        $allocationFactor = $assignment->allocation_percentage / 100;
        
        // Calculate expected hours based on allocation
        $expectedHours = $baseHours * $allocationFactor;
        
        // Add some variation (±20%)
        $variation = $expectedHours * 0.2;
        $actualHours = $expectedHours + (rand(-100, 100) / 100) * $variation;
        
        // Ensure minimum 0.5 hours and maximum 12 hours
        return max(0.5, min(12, round($actualHours, 2)));
    }

    /**
     * Select category based on role
     */
    private function selectCategory(string $role): string
    {
        $categoryWeights = match($role) {
            'Project Manager' => [
                'Meeting' => 40,
                'Planning' => 30,
                'Documentation' => 20,
                'Review' => 10
            ],
            'Lead Developer', 'Senior Developer', 'Developer' => [
                'Development' => 60,
                'Testing' => 15,
                'Review' => 15,
                'Meeting' => 10
            ],
            'Designer' => [
                'Design' => 70,
                'Meeting' => 15,
                'Review' => 10,
                'Research' => 5
            ],
            'QA Engineer' => [
                'Testing' => 70,
                'Documentation' => 15,
                'Meeting' => 10,
                'Bug Fix' => 5
            ],
            default => [
                'Development' => 40,
                'Meeting' => 30,
                'Documentation' => 20,
                'Testing' => 10
            ]
        };

        return $this->weightedRandom($categoryWeights);
    }

    /**
     * Select activity based on category
     */
    private function selectActivity(string $category): string
    {
        $activities = [
            'Development' => ['Frontend development', 'Backend development', 'API development', 'Database design', 'Code refactoring'],
            'Testing' => ['Unit testing', 'Integration testing', 'Manual testing', 'Bug testing', 'Performance testing'],
            'Design' => ['UI design', 'UX research', 'Wireframing', 'Prototyping', 'Design review'],
            'Meeting' => ['Team standup', 'Client meeting', 'Planning meeting', 'Review meeting', 'Retrospective'],
            'Documentation' => ['Technical documentation', 'User documentation', 'API documentation', 'Process documentation'],
            'Planning' => ['Sprint planning', 'Task estimation', 'Architecture planning', 'Resource planning'],
            'Review' => ['Code review', 'Design review', 'Peer review', 'Quality check'],
            'Research' => ['Technology research', 'User research', 'Market research', 'Feasibility study'],
            'Bug Fix' => ['Bug investigation', 'Bug fixing', 'Regression testing', 'Hotfix deployment']
        ];

        $categoryActivities = $activities[$category] ?? ['General work'];
        return $categoryActivities[array_rand($categoryActivities)];
    }

    /**
     * Get time entry status based on date
     */
    private function getTimeEntryStatus(Carbon $date): string
    {
        $daysSinceEntry = Carbon::now()->diffInDays($date);
        
        if ($daysSinceEntry <= 7) {
            return 'draft'; // Recent entries are still drafts
        } elseif ($daysSinceEntry <= 14) {
            return rand(1, 100) <= 70 ? 'submitted' : 'draft';
        } elseif ($daysSinceEntry <= 30) {
            return rand(1, 100) <= 80 ? 'approved' : 'submitted';
        } else {
            return rand(1, 100) <= 90 ? 'approved' : 'locked';
        }
    }

    /**
     * Get submitted timestamp
     */
    private function getSubmittedAt(Carbon $date, string $status): ?string
    {
        if (in_array($status, ['submitted', 'approved', 'locked'])) {
            return $date->copy()->addDays(rand(1, 7))->format('Y-m-d H:i:s');
        }
        
        return null;
    }

    /**
     * Get approved by user
     */
    private function getApprovedBy(string $status, array $users): ?int
    {
        if (in_array($status, ['approved', 'locked'])) {
            return $users[array_rand($users)];
        }
        
        return null;
    }

    /**
     * Get approved timestamp
     */
    private function getApprovedAt(Carbon $date, string $status): ?string
    {
        if (in_array($status, ['approved', 'locked'])) {
            return $date->copy()->addDays(rand(2, 10))->format('Y-m-d H:i:s');
        }
        
        return null;
    }

    /**
     * Select work location
     */
    private function selectLocation(): string
    {
        $locations = ['office', 'remote', 'client-site', 'home'];
        $weights = [40, 35, 15, 10]; // Office most common, then remote
        
        $weightedLocations = array_combine($locations, $weights);
        return $this->weightedRandom($weightedLocations);
    }

    /**
     * Generate description for time entry
     */
    private function generateDescription(string $activity, ProjectAssignment $assignment): string
    {
        $projectName = $assignment->project->name ?? 'Project';
        $descriptions = [
            'Frontend development' => "Worked on frontend components for {$projectName}",
            'Backend development' => "Implemented backend functionality for {$projectName}",
            'API development' => "Developed API endpoints for {$projectName}",
            'Unit testing' => "Created unit tests for {$projectName} features",
            'Team standup' => "Daily standup meeting for {$projectName}",
            'Client meeting' => "Client meeting to discuss {$projectName} progress",
            'Code review' => "Reviewed code changes for {$projectName}",
            'Bug fixing' => "Fixed bugs reported in {$projectName}",
            'Documentation' => "Updated documentation for {$projectName}",
        ];

        return $descriptions[$activity] ?? "{$activity} work on {$projectName}";
    }

    /**
     * Generate tags for time entry
     */
    private function generateTags(string $category, ProjectAssignment $assignment): array
    {
        $baseTags = [strtolower($category)];
        
        if ($assignment->project->priority === 'high') {
            $baseTags[] = 'high-priority';
        }
        
        if ($assignment->is_billable) {
            $baseTags[] = 'billable';
        }
        
        return $baseTags;
    }

    /**
     * Weighted random selection
     */
    private function weightedRandom(array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($weights as $item => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $item;
            }
        }
        
        return array_key_first($weights);
    }
} 