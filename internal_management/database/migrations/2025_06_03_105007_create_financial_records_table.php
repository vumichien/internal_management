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
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            
            // Unique record identifier
            $table->string('record_id')->unique();
            
            // Project relationship (required)
            $table->foreignId('project_id')->constrained('projects')->onUpdate('cascade')->onDelete('cascade');
            
            // Financial record details
            $table->enum('type', ['revenue', 'expense', 'invoice', 'payment', 'refund', 'adjustment'])->index();
            $table->decimal('amount', 12, 2); // Support up to 999,999,999.99
            $table->string('currency', 3)->default('USD'); // ISO 4217 currency codes
            $table->decimal('exchange_rate', 10, 6)->default(1.000000); // For multi-currency support
            $table->decimal('amount_usd', 12, 2)->nullable(); // Converted amount for reporting
            
            // Record metadata
            $table->text('description');
            $table->string('category')->index(); // Categorization for reporting
            $table->string('subcategory')->nullable()->index();
            $table->string('reference_number')->nullable()->index(); // Invoice number, receipt number, etc.
            $table->string('external_reference')->nullable(); // Reference to external systems
            
            // Date tracking
            $table->date('transaction_date'); // When the transaction occurred
            $table->date('due_date')->nullable(); // For invoices/payments
            $table->date('paid_date')->nullable(); // When payment was received/made
            
            // Polymorphic relationship to related entities
            $table->string('related_entity_type')->nullable(); // customer, vendor, employee, etc.
            $table->unsignedBigInteger('related_entity_id')->nullable();
            
            // Status and workflow
            $table->enum('status', ['draft', 'pending', 'approved', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft')->index();
            $table->boolean('is_billable')->default(true)->index();
            $table->boolean('is_recurring')->default(false)->index();
            $table->string('recurring_frequency')->nullable(); // monthly, quarterly, yearly
            $table->date('next_occurrence')->nullable();
            
            // Tax and accounting
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0); // Percentage as decimal (e.g., 0.0825 for 8.25%)
            $table->string('tax_type')->nullable(); // VAT, GST, Sales Tax, etc.
            $table->string('account_code')->nullable(); // Chart of accounts code
            
            // Approval workflow
            $table->foreignId('created_by')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Payment tracking
            $table->string('payment_method')->nullable(); // Credit Card, Bank Transfer, Cash, etc.
            $table->string('payment_reference')->nullable(); // Transaction ID, check number, etc.
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            
            // Document management
            $table->json('attachments')->nullable(); // File paths/URLs for receipts, invoices, etc.
            $table->json('metadata')->nullable(); // Additional flexible data
            
            // Integration and sync
            $table->boolean('synced_to_accounting')->default(false);
            $table->timestamp('accounting_sync_at')->nullable();
            $table->string('accounting_system_id')->nullable(); // ID in external accounting system
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('record_id');
            $table->index('project_id');
            $table->index('external_reference');
            $table->index('transaction_date');
            $table->index('due_date');
            $table->index('paid_date');
            $table->index('currency');
            $table->index('created_by');
            $table->index('approved_by');
            $table->index('payment_method');
            $table->index('account_code');
            $table->index(['related_entity_type', 'related_entity_id']);
            $table->index(['project_id', 'type']);
            $table->index(['project_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['transaction_date', 'type']);
            $table->index(['due_date', 'status']);
            $table->index(['category', 'type']);
            $table->index('synced_to_accounting');
            $table->index('accounting_sync_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
