<?php

namespace Database\Seeders;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users to create employee records for them
        $users = User::all();
        
        // Create employee records for specific users
        $adminUser = User::where('email', 'admin@company.com')->first();
        if ($adminUser) {
            Employee::firstOrCreate(
                ['user_id' => $adminUser->id],
                [
                    'job_title' => 'Chief Technology Officer',
                    'department' => 'Executive',
                    'hire_date' => now()->subYears(5),
                    'salary' => 150000,
                    'employment_type' => 'full-time',
                    'status' => 'active',
                    'emergency_contact_name' => 'Jane Administrator',
                    'emergency_contact_phone' => '+1-555-1001',
                    'emergency_contact_relationship' => 'spouse',
                    'address_line_1' => '123 Executive Drive',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'postal_code' => '94105',
                    'country' => 'United States',
                    'benefits' => [
                        'health_insurance' => true,
                        'dental_insurance' => true,
                        'vision_insurance' => true,
                        'retirement_plan' => true,
                        'paid_time_off' => 30,
                        'sick_leave' => 15
                    ],
                    'skills' => ['Leadership', 'Strategic Planning', 'Technology Management', 'Team Building'],
                    'performance_rating' => 5.0,
                    'last_review_date' => now()->subMonths(6),
                    'next_review_date' => now()->addMonths(6),
                ]
            );
        }

        $managerUser = User::where('email', 'manager@company.com')->first();
        if ($managerUser) {
            Employee::firstOrCreate(
                ['user_id' => $managerUser->id],
                [
                    'job_title' => 'Senior Project Manager',
                    'department' => 'Engineering',
                    'hire_date' => now()->subYears(3),
                    'salary' => 95000,
                    'employment_type' => 'full-time',
                    'status' => 'active',
                    'emergency_contact_name' => 'Sarah Manager',
                    'emergency_contact_phone' => '+1-555-2001',
                    'emergency_contact_relationship' => 'spouse',
                    'address_line_1' => '456 Project Lane',
                    'city' => 'New York',
                    'state' => 'NY',
                    'postal_code' => '10001',
                    'country' => 'United States',
                    'benefits' => [
                        'health_insurance' => true,
                        'dental_insurance' => true,
                        'vision_insurance' => true,
                        'retirement_plan' => true,
                        'paid_time_off' => 25,
                        'sick_leave' => 12
                    ],
                    'skills' => ['Project Management', 'Agile', 'Scrum', 'Team Leadership', 'Risk Management'],
                    'performance_rating' => 4.8,
                    'last_review_date' => now()->subMonths(4),
                    'next_review_date' => now()->addMonths(8),
                ]
            );
        }

        $employeeUser = User::where('email', 'employee@company.com')->first();
        if ($employeeUser) {
            Employee::firstOrCreate(
                ['user_id' => $employeeUser->id],
                [
                    'job_title' => 'Senior Full Stack Developer',
                    'department' => 'Engineering',
                    'hire_date' => now()->subYears(2),
                    'salary' => 85000,
                    'employment_type' => 'full-time',
                    'status' => 'active',
                    'emergency_contact_name' => 'Mary Developer',
                    'emergency_contact_phone' => '+1-555-3001',
                    'emergency_contact_relationship' => 'parent',
                    'address_line_1' => '789 Developer Street',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90210',
                    'country' => 'United States',
                    'benefits' => [
                        'health_insurance' => true,
                        'dental_insurance' => true,
                        'vision_insurance' => false,
                        'retirement_plan' => true,
                        'paid_time_off' => 20,
                        'sick_leave' => 10
                    ],
                    'skills' => ['PHP', 'Laravel', 'JavaScript', 'React', 'MySQL', 'Git', 'Docker'],
                    'performance_rating' => 4.5,
                    'last_review_date' => now()->subMonths(3),
                    'next_review_date' => now()->addMonths(9),
                ]
            );
        }

        // Create employee records for remaining users
        $usersWithoutEmployees = User::whereDoesntHave('employee')->get();
        foreach ($usersWithoutEmployees as $user) {
            Employee::factory()->create([
                'user_id' => $user->id,
            ]);
        }

        // Create some additional employees without user accounts (contractors, etc.)
        Employee::factory(5)->create([
            'user_id' => null,
            'employment_type' => 'contractor',
        ]);

        // Set up manager relationships
        $this->setupManagerRelationships();

        $this->command->info('Created employee records for all users plus additional contractors');
    }

    /**
     * Set up manager relationships between employees
     */
    private function setupManagerRelationships(): void
    {
        $cto = Employee::whereHas('user', function ($query) {
            $query->where('email', 'admin@company.com');
        })->first();

        $projectManager = Employee::whereHas('user', function ($query) {
            $query->where('email', 'manager@company.com');
        })->first();

        if ($cto && $projectManager) {
            // Project manager reports to CTO
            $projectManager->update(['manager_id' => $cto->id]);

            // Some employees report to project manager
            $employees = Employee::where('department', 'Engineering')
                ->where('id', '!=', $cto->id)
                ->where('id', '!=', $projectManager->id)
                ->limit(5)
                ->get();

            foreach ($employees as $employee) {
                $employee->update(['manager_id' => $projectManager->id]);
            }
        }

        // Set up other department managers
        $departments = ['Marketing', 'Sales', 'HR', 'Finance'];
        foreach ($departments as $department) {
            $manager = Employee::where('department', $department)
                ->whereNull('manager_id')
                ->first();
            
            if ($manager && $cto) {
                $manager->update(['manager_id' => $cto->id]);
                
                // Assign other employees in the department to this manager
                Employee::where('department', $department)
                    ->where('id', '!=', $manager->id)
                    ->whereNull('manager_id')
                    ->limit(3)
                    ->update(['manager_id' => $manager->id]);
            }
        }
    }
} 