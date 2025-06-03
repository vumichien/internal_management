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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            
            // Unique employee identifier
            $table->string('employee_id')->unique();
            
            // Relationship to users table
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            
            // Basic employment information
            $table->string('job_title');
            $table->string('department');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            
            // Compensation information
            $table->decimal('salary', 10, 2)->nullable();
            $table->enum('employment_type', ['full-time', 'part-time', 'contractor', 'intern'])->default('full-time');
            
            // Organizational hierarchy
            $table->foreignId('manager_id')->nullable()->constrained('employees')->onUpdate('cascade')->onDelete('set null');
            
            // Contact and personal information
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // Address information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('US');
            
            // Employment status and details
            $table->enum('status', ['active', 'inactive', 'terminated', 'on-leave'])->default('active');
            $table->text('notes')->nullable();
            
            // Benefits and HR information
            $table->json('benefits')->nullable(); // Flexible storage for benefit information
            $table->json('skills')->nullable(); // Employee skills and certifications
            
            // Performance and review information
            $table->date('last_review_date')->nullable();
            $table->date('next_review_date')->nullable();
            $table->decimal('performance_rating', 3, 2)->nullable(); // 0.00 to 5.00 scale
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('employee_id');
            $table->index('user_id');
            $table->index('department');
            $table->index('manager_id');
            $table->index('status');
            $table->index('employment_type');
            $table->index(['department', 'status']);
            $table->index(['manager_id', 'status']);
            $table->index('hire_date');
            $table->index('termination_date');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
}; 