<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
        $customerId = $this->route('customer') ? $this->route('customer')->id : null;

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
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[\+]?[1-9][\d]{0,15}$/', // International phone format
            ],
            'website' => [
                'nullable',
                'url',
                'max:255',
            ],
            'industry' => [
                'nullable',
                'string',
                'max:100',
            ],
            'company_size' => [
                'nullable',
                'string',
                Rule::in(['startup', 'small', 'medium', 'large', 'enterprise']),
            ],
            'annual_revenue' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
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
                Rule::in(['prospect', 'active', 'inactive', 'former']),
            ],
            'priority' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'vip']),
            ],
            'source' => [
                'nullable',
                'string',
                'max:100',
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
            'billing_contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'billing_contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'billing_contact_phone' => [
                'nullable',
                'string',
                'regex:/^[\+]?[1-9][\d]{0,15}$/',
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
            'auto_renewal' => [
                'nullable',
                'boolean',
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
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already associated with another customer.',
            'phone.regex' => 'Please enter a valid phone number.',
            'billing_contact_phone.regex' => 'Please enter a valid billing contact phone number.',
            'additional_contacts.*.phone.regex' => 'Please enter a valid phone number for additional contact.',
            'last_contact_date.after_or_equal' => 'Last contact date must be on or after first contact date.',
            'contract_end_date.after' => 'Contract end date must be after contract start date.',
            'outstanding_balance.max' => 'Outstanding balance exceeds maximum allowed amount.',
            'credit_limit.max' => 'Credit limit exceeds maximum allowed amount.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'company_size' => 'company size',
            'annual_revenue' => 'annual revenue',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
            'credit_limit' => 'credit limit',
            'outstanding_balance' => 'outstanding balance',
            'payment_terms' => 'payment terms',
            'tax_id' => 'tax ID',
            'billing_contact_name' => 'billing contact name',
            'billing_contact_email' => 'billing contact email',
            'billing_contact_phone' => 'billing contact phone',
            'first_contact_date' => 'first contact date',
            'last_contact_date' => 'last contact date',
            'contract_start_date' => 'contract start date',
            'contract_end_date' => 'contract end date',
            'auto_renewal' => 'auto renewal',
            'additional_contacts' => 'additional contacts',
            'communication_preferences' => 'communication preferences',
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

            // Custom validation: Active customers should have contact information
            if ($this->status === 'active' && !$this->phone && !$this->email) {
                $validator->errors()->add('phone', 'Active customers must have either phone or email contact information.');
            }

            // Custom validation: VIP customers should have complete address information
            if ($this->priority === 'vip' && (!$this->address_line_1 || !$this->city || !$this->country)) {
                $validator->errors()->add('address_line_1', 'VIP customers should have complete address information.');
            }

            // Custom validation: Contract dates should be provided for active customers
            if ($this->status === 'active' && !$this->contract_start_date) {
                $validator->errors()->add('contract_start_date', 'Contract start date is recommended for active customers.');
            }

            // Custom validation: Former customers should have last contact date
            if ($this->status === 'former' && !$this->last_contact_date) {
                $validator->errors()->add('last_contact_date', 'Last contact date is required for former customers.');
            }
        });
    }
} 