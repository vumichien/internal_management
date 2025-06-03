<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee') ? $this->route('employee')->id : null;
        
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('employees', 'user_id')->ignore($employeeId),
            ],
            'job_title' => [
                'required',
                'string',
                'max:255',
            ],
            'department' => [
                'required',
                'string',
                'max:255',
            ],
            'hire_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'termination_date' => [
                'nullable',
                'date',
                'after:hire_date',
            ],
            'salary' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'employment_type' => [
                'required',
                'string',
                Rule::in(['full-time', 'part-time', 'contract', 'intern', 'temporary']),
            ],
            'manager_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
                'different:' . $employeeId, // Employee cannot be their own manager
            ],
            'emergency_contact_name' => [
                'required',
                'string',
                'max:255',
            ],
            'emergency_contact_phone' => [
                'required',
                'string',
                'regex:/^[\+]?[1-9][\d]{0,15}$/', // International phone format
            ],
            'emergency_contact_relationship' => [
                'required',
                'string',
                'max:100',
            ],
            'address_line_1' => [
                'required',
                'string',
                'max:255',
            ],
            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'required',
                'string',
                'max:100',
            ],
            'state' => [
                'required',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'required',
                'string',
                'max:20',
            ],
            'country' => [
                'required',
                'string',
                'max:100',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive', 'terminated', 'on-leave']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'benefits' => [
                'nullable',
                'array',
            ],
            'benefits.*' => [
                'string',
                'max:255',
            ],
            'skills' => [
                'nullable',
                'array',
            ],
            'skills.*' => [
                'string',
                'max:255',
            ],
            'last_review_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'next_review_date' => [
                'nullable',
                'date',
                'after:last_review_date',
            ],
            'performance_rating' => [
                'nullable',
                'numeric',
                'min:1',
                'max:5',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'This user is already associated with an employee record.',
            'manager_id.different' => 'An employee cannot be their own manager.',
            'termination_date.after' => 'Termination date must be after hire date.',
            'next_review_date.after' => 'Next review date must be after last review date.',
            'emergency_contact_phone.regex' => 'Please enter a valid phone number.',
            'performance_rating.min' => 'Performance rating must be between 1 and 5.',
            'performance_rating.max' => 'Performance rating must be between 1 and 5.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'job_title' => 'job title',
            'hire_date' => 'hire date',
            'termination_date' => 'termination date',
            'employment_type' => 'employment type',
            'manager_id' => 'manager',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
            'emergency_contact_relationship' => 'emergency contact relationship',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
            'last_review_date' => 'last review date',
            'next_review_date' => 'next review date',
            'performance_rating' => 'performance rating',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: If employee is terminated, termination_date is required
            if ($this->status === 'terminated' && !$this->termination_date) {
                $validator->errors()->add('termination_date', 'Termination date is required when status is terminated.');
            }

            // Custom validation: Active employees should not have termination date
            if ($this->status === 'active' && $this->termination_date) {
                $validator->errors()->add('termination_date', 'Active employees should not have a termination date.');
            }

            // Custom validation: Performance rating should be provided if last review date exists
            if ($this->last_review_date && !$this->performance_rating) {
                $validator->errors()->add('performance_rating', 'Performance rating is required when last review date is provided.');
            }
        });
    }
} 