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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            
            // Unique project identifier
            $table->string('project_id')->unique();
            
            // Basic project information
            $table->string('name');
            $table->text('description')->nullable();
            
            // Project timeline
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            // Project status
            $table->enum('status', ['planned', 'active', 'on-hold', 'completed', 'cancelled'])->default('planned');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Financial tracking
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            
            // Relationships
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('project_manager_id')->nullable()->constrained('employees')->onUpdate('cascade')->onDelete('set null');
            
            // Project categorization
            $table->string('category')->nullable();
            $table->string('type')->nullable(); // internal, client, research, etc.
            
            // Progress tracking
            $table->decimal('completion_percentage', 5, 2)->default(0); // 0.00 to 100.00
            
            // Billing information
            $table->enum('billing_type', ['fixed', 'hourly', 'milestone', 'retainer'])->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->boolean('is_billable')->default(true);
            
            // Project metadata (JSON for flexible storage)
            $table->json('custom_attributes')->nullable();
            $table->json('milestones')->nullable();
            $table->json('deliverables')->nullable();
            
            // Risk and quality management
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
            $table->text('notes')->nullable();
            $table->text('requirements')->nullable();
            
            // Archive and soft delete
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('project_id');
            $table->index('status');
            $table->index('priority');
            $table->index('customer_id');
            $table->index('project_manager_id');
            $table->index('category');
            $table->index('type');
            $table->index('billing_type');
            $table->index('is_billable');
            $table->index('is_archived');
            $table->index(['status', 'priority']);
            $table->index(['customer_id', 'status']);
            $table->index(['project_manager_id', 'status']);
            $table->index('start_date');
            $table->index('end_date');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
}; 