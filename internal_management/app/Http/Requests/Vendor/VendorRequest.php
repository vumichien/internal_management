<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization should be handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vendorId = $this->route('vendor') ? $this->route('vendor')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('vendors', 'email')->ignore($vendorId),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[\+]?[1-9][\d]{0,15}$/',
            ],
            'website' => [
                'nullable',
                'url',
                'max:255',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['supplier', 'contractor', 'consultant', 'service-provider', 'freelancer']),
            ],
            'service_type' => [
                'required',
                'string',
                'max:100',
            ],
            'address_line_1' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'country' => [
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['pending', 'active', 'inactive', 'suspended', 'terminated']),
            ],
            'priority' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'credit_limit' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'outstanding_balance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'payment_terms' => [
                'nullable',
                'string',
                'max:100',
            ],
            'tax_id' => [
                'nullable',
                'string',
                'max:50',
            ],
            'bank_account_info' => [
                'nullable',
                'string',
                'max:500',
            ],
            'performance_rating' => [
                'nullable',
                'numeric',
                'min:1',
                'max:5',
            ],
            'average_delivery_time' => [
                'nullable',
                'numeric',
                'min:0',
                'max:365',
            ],
            'first_contact_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'last_contact_date' => [
                'nullable',
                'date',
                'after_or_equal:first_contact_date',
                'before_or_equal:today',
            ],
            'contract_start_date' => [
                'nullable',
                'date',
            ],
            'contract_end_date' => [
                'nullable',
                'date',
                'after:contract_start_date',
            ],
            'last_performance_review' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'auto_renewal' => [
                'nullable',
                'boolean',
            ],
            'services_provided' => [
                'nullable',
                'array',
            ],
            'services_provided.*' => [
                'string',
                'max:255',
            ],
            'certifications' => [
                'nullable',
                'array',
            ],
            'certifications.*' => [
                'string',
                'max:255',
            ],
            'capabilities' => [
                'nullable',
                'array',
            ],
            'capabilities.*' => [
                'string',
                'max:255',
            ],
            'additional_contacts' => [
                'nullable',
                'array',
            ],
            'additional_contacts.*.name' => [
                'required_with:additional_contacts',
                'string',
                'max:255',
            ],
            'additional_contacts.*.email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'additional_contacts.*.phone' => [
                'nullable',
                'string',
                'regex:/^[\+]?[1-9][\d]{0,15}$/',
            ],
            'additional_contacts.*.role' => [
                'nullable',
                'string',
                'max:100',
            ],
            'communication_preferences' => [
                'nullable',
                'array',
            ],
            'communication_preferences.*' => [
                'string',
                Rule::in(['email', 'phone', 'sms', 'mail', 'in-person']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'requirements' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'compliance_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'lead_source' => [
                'nullable',
                'string',
                'max:100',
            ],
            'insurance_verified' => [
                'nullable',
                'boolean',
            ],
            'insurance_expiry_date' => [
                'nullable',
                'date',
                'after:today',
            ],
            'background_check_completed' => [
                'nullable',
                'boolean',
            ],
            'background_check_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already associated with another vendor.',
            'phone.regex' => 'Please enter a valid phone number.',
            'additional_contacts.*.phone.regex' => 'Please enter a valid phone number for additional contact.',
            'last_contact_date.after_or_equal' => 'Last contact date must be on or after first contact date.',
            'contract_end_date.after' => 'Contract end date must be after contract start date.',
            'performance_rating.min' => 'Performance rating must be between 1 and 5.',
            'performance_rating.max' => 'Performance rating must be between 1 and 5.',
            'average_delivery_time.max' => 'Average delivery time cannot exceed 365 days.',
            'insurance_expiry_date.after' => 'Insurance expiry date must be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'service_type' => 'service type',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
            'credit_limit' => 'credit limit',
            'outstanding_balance' => 'outstanding balance',
            'payment_terms' => 'payment terms',
            'tax_id' => 'tax ID',
            'bank_account_info' => 'bank account information',
            'performance_rating' => 'performance rating',
            'average_delivery_time' => 'average delivery time',
            'first_contact_date' => 'first contact date',
            'last_contact_date' => 'last contact date',
            'contract_start_date' => 'contract start date',
            'contract_end_date' => 'contract end date',
            'last_performance_review' => 'last performance review',
            'auto_renewal' => 'auto renewal',
            'services_provided' => 'services provided',
            'additional_contacts' => 'additional contacts',
            'communication_preferences' => 'communication preferences',
            'compliance_notes' => 'compliance notes',
            'lead_source' => 'lead source',
            'insurance_verified' => 'insurance verified',
            'insurance_expiry_date' => 'insurance expiry date',
            'background_check_completed' => 'background check completed',
            'background_check_date' => 'background check date',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: Outstanding balance should not exceed credit limit
            if ($this->outstanding_balance && $this->credit_limit && 
                $this->outstanding_balance > $this->credit_limit) {
                $validator->errors()->add('outstanding_balance', 'Outstanding balance cannot exceed credit limit.');
            }

            // Custom validation: Active vendors should have contact information
            if ($this->status === 'active' && !$this->phone && !$this->email) {
                $validator->errors()->add('phone', 'Active vendors must have either phone or email contact information.');
            }

            // Custom validation: Critical priority vendors should have insurance verification
            if ($this->priority === 'critical' && !$this->insurance_verified) {
                $validator->errors()->add('insurance_verified', 'Critical priority vendors should have verified insurance.');
            }

            // Custom validation: Background check date required if background check completed
            if ($this->background_check_completed && !$this->background_check_date) {
                $validator->errors()->add('background_check_date', 'Background check date is required when background check is completed.');
            }

            // Custom validation: Performance rating should be provided if last performance review exists
            if ($this->last_performance_review && !$this->performance_rating) {
                $validator->errors()->add('performance_rating', 'Performance rating is required when last performance review date is provided.');
            }

            // Custom validation: Terminated vendors should have last contact date
            if ($this->status === 'terminated' && !$this->last_contact_date) {
                $validator->errors()->add('last_contact_date', 'Last contact date is required for terminated vendors.');
            }
        });
    }
} 