<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
            'actual_end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['planning', 'active', 'on-hold', 'completed', 'cancelled']),
            ],
            'priority' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'budget' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'actual_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'estimated_hours' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'actual_hours' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'currency' => [
                'required',
                'string',
                'size:3', // ISO currency codes are 3 characters
                'regex:/^[A-Z]{3}$/',
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
            'project_manager_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],
            'category' => [
                'required',
                'string',
                'max:100',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['fixed-price', 'time-and-materials', 'retainer', 'maintenance']),
            ],
            'completion_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'billing_type' => [
                'required',
                'string',
                Rule::in(['hourly', 'fixed', 'milestone', 'retainer']),
            ],
            'hourly_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999.99',
                'required_if:billing_type,hourly',
            ],
            'is_billable' => [
                'required',
                'boolean',
            ],
            'custom_attributes' => [
                'nullable',
                'array',
            ],
            'milestones' => [
                'nullable',
                'array',
            ],
            'milestones.*.name' => [
                'required_with:milestones',
                'string',
                'max:255',
            ],
            'milestones.*.due_date' => [
                'required_with:milestones',
                'date',
                'after_or_equal:start_date',
                'before_or_equal:end_date',
            ],
            'milestones.*.description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'deliverables' => [
                'nullable',
                'array',
            ],
            'deliverables.*' => [
                'string',
                'max:500',
            ],
            'risk_level' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'requirements' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'is_archived' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'end_date.after' => 'End date must be after start date.',
            'actual_end_date.after_or_equal' => 'Actual end date must be on or after start date.',
            'currency.regex' => 'Currency must be a valid 3-letter ISO code (e.g., USD, EUR).',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'hourly_rate.required_if' => 'Hourly rate is required when billing type is hourly.',
            'completion_percentage.max' => 'Completion percentage cannot exceed 100%.',
            'milestones.*.due_date.after_or_equal' => 'Milestone due date must be on or after project start date.',
            'milestones.*.due_date.before_or_equal' => 'Milestone due date must be on or before project end date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
            'actual_end_date' => 'actual end date',
            'actual_cost' => 'actual cost',
            'estimated_hours' => 'estimated hours',
            'actual_hours' => 'actual hours',
            'customer_id' => 'customer',
            'project_manager_id' => 'project manager',
            'completion_percentage' => 'completion percentage',
            'billing_type' => 'billing type',
            'hourly_rate' => 'hourly rate',
            'is_billable' => 'billable status',
            'custom_attributes' => 'custom attributes',
            'risk_level' => 'risk level',
            'is_archived' => 'archived status',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: Completed projects should have actual end date
            if ($this->status === 'completed' && !$this->actual_end_date) {
                $validator->errors()->add('actual_end_date', 'Actual end date is required for completed projects.');
            }

            // Custom validation: Completed projects should have 100% completion
            if ($this->status === 'completed' && $this->completion_percentage != 100) {
                $validator->errors()->add('completion_percentage', 'Completed projects must have 100% completion.');
            }

            // Custom validation: Actual cost should not exceed budget by more than 50% without warning
            if ($this->actual_cost && $this->budget && $this->actual_cost > ($this->budget * 1.5)) {
                $validator->errors()->add('actual_cost', 'Actual cost significantly exceeds budget. Please review.');
            }

            // Custom validation: Actual hours should not exceed estimated hours by more than 50% without warning
            if ($this->actual_hours && $this->estimated_hours && $this->actual_hours > ($this->estimated_hours * 1.5)) {
                $validator->errors()->add('actual_hours', 'Actual hours significantly exceed estimate. Please review.');
            }

            // Custom validation: Archived projects should be completed or cancelled
            if ($this->is_archived && !in_array($this->status, ['completed', 'cancelled'])) {
                $validator->errors()->add('is_archived', 'Only completed or cancelled projects can be archived.');
            }
        });
    }
} 