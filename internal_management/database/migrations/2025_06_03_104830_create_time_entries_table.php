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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            
            // Unique entry identifier
            $table->string('entry_id')->unique();
            
            // Foreign key relationships
            $table->foreignId('employee_id')->constrained('employees')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('project_assignment_id')->nullable()->constrained('project_assignments')->onUpdate('cascade')->onDelete('set null');
            
            // Time tracking details
            $table->date('date');
            $table->decimal('hours_worked', 5, 2); // Up to 999.99 hours
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('break_duration', 4, 2)->default(0); // Break time in hours
            
            // Entry details
            $table->text('description');
            $table->string('task_category')->nullable(); // Development, Testing, Meeting, etc.
            $table->string('activity_type')->nullable(); // Coding, Review, Documentation, etc.
            $table->json('tags')->nullable(); // Flexible tagging system
            
            // Billing and rates
            $table->boolean('is_billable')->default(true);
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Override rate for this entry
            $table->decimal('billable_amount', 10, 2)->nullable(); // Calculated billable amount
            
            // Status and approval workflow
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'locked'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Location and context
            $table->string('location')->nullable(); // Office, Remote, Client Site, etc.
            $table->json('metadata')->nullable(); // Additional flexible data
            
            // Tracking and audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            $table->timestamp('locked_at')->nullable(); // When entry was locked for payroll
            $table->foreignId('locked_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            
            // Integration fields
            $table->string('external_reference')->nullable(); // Reference to external systems
            $table->boolean('synced_to_payroll')->default(false);
            $table->timestamp('payroll_sync_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('entry_id');
            $table->index('employee_id');
            $table->index('project_id');
            $table->index('project_assignment_id');
            $table->index('date');
            $table->index('status');
            $table->index('is_billable');
            $table->index('task_category');
            $table->index('activity_type');
            $table->index('location');
            $table->index('created_by');
            $table->index('approved_by');
            $table->index('locked_by');
            $table->index(['employee_id', 'date']);
            $table->index(['project_id', 'date']);
            $table->index(['employee_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['date', 'status']);
            $table->index(['is_billable', 'status']);
            $table->index('submitted_at');
            $table->index('approved_at');
            $table->index('locked_at');
            $table->index('payroll_sync_at');
            $table->index('deleted_at');
            
            // Unique constraint to prevent duplicate entries for same employee, project, and date
            // Note: This allows multiple entries per day but prevents exact duplicates
            $table->unique(['employee_id', 'project_id', 'date', 'start_time'], 'unique_employee_project_date_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
