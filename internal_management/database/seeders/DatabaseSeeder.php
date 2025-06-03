<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info("🌱 Starting database seeding...");
        
        // Disable foreign key checks for seeding
        Schema::disableForeignKeyConstraints();
        
        try {
            // Seed in dependency order
            $this->seedInOrder();
            
            $this->command->info('✅ Database seeding completed successfully!');
            $this->displaySeededCounts();
            
        } catch (\Exception $e) {
            $this->command->error('❌ Database seeding failed: ' . $e->getMessage());
            throw $e;
        } finally {
            // Re-enable foreign key checks
            Schema::enableForeignKeyConstraints();
        }
    }
    
    /**
     * Seed tables in the correct dependency order.
     */
    private function seedInOrder(): void
    {
        $seeders = [
            UserSeeder::class => 'Users',
            EmployeeSeeder::class => 'Employees',
            CustomerSeeder::class => 'Customers', 
            VendorSeeder::class => 'Vendors',
            ProjectSeeder::class => 'Projects',
            ProjectAssignmentSeeder::class => 'Project Assignments',
            TimeEntrySeeder::class => 'Time Entries',
            FinancialRecordSeeder::class => 'Financial Records',
        ];
        
        foreach ($seeders as $seederClass => $description) {
            $this->command->info("🌱 Seeding {$description}...");
            $this->call($seederClass);
        }
    }
    
    /**
     * Display seeded record counts.
     */
    private function displaySeededCounts(): void
    {
        $counts = [
            'Users' => DB::table('users')->count(),
            'Employees' => DB::table('employees')->count(),
            'Customers' => DB::table('customers')->count(),
            'Vendors' => DB::table('vendors')->count(),
            'Projects' => DB::table('projects')->count(),
            'Project Assignments' => DB::table('project_assignments')->count(),
            'Time Entries' => DB::table('time_entries')->count(),
            'Financial Records' => DB::table('financial_records')->count(),
        ];
        
        $this->command->info("\n📊 Seeded Record Counts:");
        foreach ($counts as $table => $count) {
            $this->command->info("  {$table}: {$count}");
        }
    }
}

/*
 * Usage Examples:
 * 
 * Standard seeding:
 * php artisan db:seed
 * 
 * Specific seeder:
 * php artisan db:seed --class=UserSeeder
 * 
 * Fresh migration and seeding:
 * php artisan migrate:fresh --seed
 */
