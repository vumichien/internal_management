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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            
            // Unique customer identifier
            $table->string('customer_id')->unique();
            
            // Company information
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Address information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('US');
            
            // Business information
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable(); // small, medium, large, enterprise
            $table->string('tax_id')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            
            // Relationship management
            $table->enum('status', ['active', 'inactive', 'prospect', 'former'])->default('prospect');
            $table->enum('priority', ['low', 'medium', 'high', 'vip'])->default('medium');
            $table->date('first_contact_date')->nullable();
            $table->date('last_contact_date')->nullable();
            
            // Financial information
            $table->string('preferred_currency', 3)->default('USD');
            $table->string('payment_terms')->nullable(); // Net 30, Net 60, etc.
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            
            // Additional contacts (JSON for flexibility)
            $table->json('additional_contacts')->nullable();
            
            // Communication preferences
            $table->json('communication_preferences')->nullable();
            
            // Notes and additional information
            $table->text('notes')->nullable();
            $table->text('requirements')->nullable();
            
            // Source tracking
            $table->string('lead_source')->nullable(); // referral, website, marketing, etc.
            $table->string('assigned_sales_rep')->nullable();
            
            // Contract information
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->boolean('auto_renewal')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('customer_id');
            $table->index('company_name');
            $table->index('email');
            $table->index('status');
            $table->index('priority');
            $table->index('industry');
            $table->index('company_size');
            $table->index('lead_source');
            $table->index('assigned_sales_rep');
            $table->index(['status', 'priority']);
            $table->index(['industry', 'status']);
            $table->index('first_contact_date');
            $table->index('last_contact_date');
            $table->index('contract_end_date');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
}; 