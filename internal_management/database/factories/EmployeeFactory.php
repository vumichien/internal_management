<?php

namespace Database\Factories;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hireDate = $this->faker->dateTimeBetween('-5 years', '-1 month');
        $isTerminated = $this->faker->boolean(10); // 10% chance of being terminated
        $terminationDate = $isTerminated ? $this->faker->dateTimeBetween($hireDate, 'now') : null;
        
        $departments = ['Engineering', 'Marketing', 'Sales', 'HR', 'Finance', 'Operations', 'Design', 'Support'];
        $jobTitles = [
            'Engineering' => ['Software Engineer', 'Senior Developer', 'Tech Lead', 'DevOps Engineer', 'QA Engineer'],
            'Marketing' => ['Marketing Manager', 'Content Creator', 'SEO Specialist', 'Social Media Manager'],
            'Sales' => ['Sales Representative', 'Account Manager', 'Sales Director', 'Business Development'],
            'HR' => ['HR Manager', 'Recruiter', 'HR Coordinator', 'Training Specialist'],
            'Finance' => ['Financial Analyst', 'Accountant', 'Finance Manager', 'Controller'],
            'Operations' => ['Operations Manager', 'Project Coordinator', 'Process Analyst'],
            'Design' => ['UI/UX Designer', 'Graphic Designer', 'Product Designer'],
            'Support' => ['Customer Support', 'Technical Support', 'Support Manager']
        ];
        
        $department = $this->faker->randomElement($departments);
        $jobTitle = $this->faker->randomElement($jobTitles[$department]);
        
        $employmentTypes = ['full-time', 'part-time', 'contractor', 'intern'];
        $statuses = $isTerminated ? ['terminated'] : ['active', 'on-leave'];
        
        return [
            'user_id' => User::factory(),
            'job_title' => $jobTitle,
            'department' => $department,
            'hire_date' => $hireDate,
            'termination_date' => $terminationDate,
            'salary' => $this->faker->numberBetween(40000, 150000),
            'employment_type' => $this->faker->randomElement($employmentTypes),
            'status' => $this->faker->randomElement($statuses),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->randomElement(['spouse', 'parent', 'sibling', 'friend']),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'benefits' => [
                'health_insurance' => $this->faker->boolean(80),
                'dental_insurance' => $this->faker->boolean(70),
                'vision_insurance' => $this->faker->boolean(60),
                'retirement_plan' => $this->faker->boolean(85),
                'paid_time_off' => $this->faker->numberBetween(10, 30),
                'sick_leave' => $this->faker->numberBetween(5, 15)
            ],
            'skills' => $this->generateSkills($department),
            'performance_rating' => $this->faker->randomFloat(1, 1.0, 5.0),
            'last_review_date' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'next_review_date' => $this->faker->optional(0.8)->dateTimeBetween('now', '+1 year'),
            'notes' => $this->faker->optional(0.4)->paragraph(),
        ];
    }

    /**
     * Generate skills based on department
     */
    private function generateSkills(string $department): array
    {
        $skillSets = [
            'Engineering' => ['PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Python', 'Docker', 'AWS', 'MySQL', 'Git'],
            'Marketing' => ['SEO', 'Google Analytics', 'Content Marketing', 'Social Media', 'Email Marketing', 'PPC', 'Copywriting'],
            'Sales' => ['CRM', 'Lead Generation', 'Negotiation', 'Presentation', 'Customer Relations', 'Sales Forecasting'],
            'HR' => ['Recruitment', 'Employee Relations', 'Performance Management', 'Training', 'Compliance', 'HRIS'],
            'Finance' => ['Financial Analysis', 'Budgeting', 'Forecasting', 'Excel', 'QuickBooks', 'Tax Preparation'],
            'Operations' => ['Project Management', 'Process Improvement', 'Supply Chain', 'Quality Assurance', 'Lean Six Sigma'],
            'Design' => ['Adobe Creative Suite', 'Figma', 'Sketch', 'UI/UX Design', 'Prototyping', 'User Research'],
            'Support' => ['Customer Service', 'Technical Troubleshooting', 'Help Desk', 'Documentation', 'Training']
        ];

        $departmentSkills = $skillSets[$department] ?? ['Communication', 'Problem Solving', 'Time Management'];
        $maxSkills = count($departmentSkills);
        $numSkills = $this->faker->numberBetween(3, min(7, $maxSkills));
        
        return $this->faker->randomElements($departmentSkills, $numSkills);
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'terminated',
            'termination_date' => $this->faker->dateTimeBetween($attributes['hire_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the employee is on leave.
     */
    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on-leave',
        ]);
    }

    /**
     * Indicate that the employee is a manager.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'job_title' => $attributes['department'] . ' Manager',
        ]);
    }
} 