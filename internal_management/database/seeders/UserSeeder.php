<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@company.com'],
            [
                'name' => 'System Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'phone' => '+1-555-0001',
                'bio' => 'System administrator with full access to all management functions.',
                'timezone' => 'UTC',
                'locale' => 'en',
                'is_verified' => true,
                'two_factor_enabled' => false,
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => [
                        'email' => true,
                        'browser' => true,
                        'mobile' => false
                    ],
                    'dashboard' => [
                        'default_view' => 'overview',
                        'widgets' => ['projects', 'tasks', 'team', 'financial']
                    ]
                ]
            ]
        );

        // Create manager user
        User::firstOrCreate(
            ['email' => 'manager@company.com'],
            [
                'name' => 'Project Manager',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'manager',
                'status' => 'active',
                'phone' => '+1-555-0002',
                'bio' => 'Experienced project manager overseeing multiple development teams.',
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'is_verified' => true,
                'two_factor_enabled' => true,
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => [
                        'email' => true,
                        'browser' => true,
                        'mobile' => true
                    ],
                    'dashboard' => [
                        'default_view' => 'projects',
                        'widgets' => ['projects', 'team', 'deadlines']
                    ]
                ]
            ]
        );

        // Create employee user
        User::firstOrCreate(
            ['email' => 'employee@company.com'],
            [
                'name' => 'John Developer',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'employee',
                'status' => 'active',
                'phone' => '+1-555-0003',
                'bio' => 'Full-stack developer specializing in Laravel and React applications.',
                'timezone' => 'America/Los_Angeles',
                'locale' => 'en',
                'is_verified' => true,
                'two_factor_enabled' => false,
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => [
                        'email' => true,
                        'browser' => false,
                        'mobile' => false
                    ],
                    'dashboard' => [
                        'default_view' => 'tasks',
                        'widgets' => ['tasks', 'time-tracking']
                    ]
                ]
            ]
        );

        // Create additional random users
        User::factory(15)->create();

        $this->command->info('Created users: 3 specific users + 15 random users');
    }
} 