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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            
            // Unique vendor identifier
            $table->string('vendor_id')->unique();
            
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
            $table->string('service_type'); // Primary service category
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable(); // small, medium, large, enterprise
            $table->string('tax_id')->nullable();
            $table->string('business_license')->nullable();
            
            // Vendor classification
            $table->enum('vendor_type', ['supplier', 'contractor', 'consultant', 'service_provider', 'partner'])->default('supplier');
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended', 'terminated'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Financial information
            $table->string('preferred_currency', 3)->default('USD');
            $table->string('payment_terms')->nullable(); // Net 30, Net 60, etc.
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->string('bank_account_info')->nullable(); // Encrypted field for payment processing
            
            // Contract and relationship management
            $table->date('first_contact_date')->nullable();
            $table->date('last_contact_date')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->boolean('auto_renewal')->default(false);
            $table->string('assigned_procurement_rep')->nullable();
            
            // Performance tracking
            $table->decimal('performance_rating', 3, 2)->nullable(); // 0.00 to 5.00 scale
            $table->date('last_performance_review')->nullable();
            $table->integer('delivery_success_rate')->nullable(); // Percentage 0-100
            $table->decimal('average_delivery_time', 5, 2)->nullable(); // Days
            
            // Services and capabilities (JSON for flexibility)
            $table->json('services_provided')->nullable();
            $table->json('certifications')->nullable();
            $table->json('capabilities')->nullable();
            
            // Additional contacts (JSON for flexibility)
            $table->json('additional_contacts')->nullable();
            
            // Communication preferences
            $table->json('communication_preferences')->nullable();
            
            // Notes and additional information
            $table->text('notes')->nullable();
            $table->text('requirements')->nullable();
            $table->text('compliance_notes')->nullable();
            
            // Source tracking
            $table->string('lead_source')->nullable(); // referral, website, trade_show, etc.
            
            // Insurance and compliance
            $table->boolean('insurance_verified')->default(false);
            $table->date('insurance_expiry_date')->nullable();
            $table->boolean('background_check_completed')->default(false);
            $table->date('background_check_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('vendor_id');
            $table->index('company_name');
            $table->index('email');
            $table->index('service_type');
            $table->index('vendor_type');
            $table->index('status');
            $table->index('priority');
            $table->index('industry');
            $table->index('company_size');
            $table->index('assigned_procurement_rep');
            $table->index(['status', 'priority']);
            $table->index(['service_type', 'status']);
            $table->index(['vendor_type', 'status']);
            $table->index('first_contact_date');
            $table->index('last_contact_date');
            $table->index('contract_end_date');
            $table->index('insurance_expiry_date');
            $table->index('performance_rating');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
