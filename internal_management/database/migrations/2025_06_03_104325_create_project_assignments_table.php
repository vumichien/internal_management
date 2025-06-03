<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();
            
            // Unique assignment identifier
            $table->string('assignment_id')->unique();
            
            // Foreign key relationships
            $table->foreignId('project_id')->constrained('projects')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onUpdate('cascade')->onDelete('cascade');
            
            // Assignment details
            $table->string('role_on_project'); // Developer, Designer, Project Manager, QA, etc.
            $table->decimal('allocation_percentage', 5, 2); // 0.00 to 100.00
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Override employee's default rate if needed
            
            // Assignment timeline
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            // Assignment status and management
            $table->enum('status', ['active', 'inactive', 'completed', 'cancelled', 'on-hold'])->default('active');
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_primary_assignment')->default(false); // Is this the employee's primary project?
            
            // Performance and tracking
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0); // 0.00 to 100.00
            
            // Assignment metadata
            $table->text('responsibilities')->nullable(); // Specific responsibilities on this project
            $table->text('notes')->nullable();
            $table->json('skills_required')->nullable(); // Skills needed for this assignment
            $table->json('deliverables')->nullable(); // Expected deliverables
            
            // Approval and management
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Performance tracking
            $table->decimal('performance_rating', 3, 2)->nullable(); // 0.00 to 5.00 scale
            $table->date('last_performance_review')->nullable();
            $table->text('performance_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('assignment_id');
            $table->index('project_id');
            $table->index('employee_id');
            $table->index('status');
            $table->index('is_billable');
            $table->index('is_primary_assignment');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('assigned_by');
            $table->index('approved_by');
            $table->index(['project_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index(['project_id', 'employee_id']);
            $table->index(['start_date', 'end_date']);
            $table->index('deleted_at');
            
            // Unique constraint to prevent overlapping assignments for the same employee on the same project
            // Note: This is a simplified constraint. In practice, you might want to handle overlapping date ranges more carefully
            $table->unique(['project_id', 'employee_id', 'start_date'], 'unique_project_employee_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};
