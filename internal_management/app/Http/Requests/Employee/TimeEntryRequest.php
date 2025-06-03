<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimeEntryRequest extends FormRequest
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
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
            ],
            'project_assignment_id' => [
                'nullable',
                'integer',
                'exists:project_assignments,id',
            ],
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'hours_worked' => [
                'required',
                'numeric',
                'min:0.25',
                'max:24',
            ],
            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
            ],
            'break_duration' => [
                'nullable',
                'numeric',
                'min:0',
                'max:8',
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'task_category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'activity_type' => [
                'required',
                'string',
                Rule::in(['development', 'testing', 'design', 'meeting', 'documentation', 'research', 'support', 'training', 'other']),
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
            'is_billable' => [
                'required',
                'boolean',
            ],
            'hourly_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999.99',
                'required_if:is_billable,true',
            ],
            'billable_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['draft', 'submitted', 'approved', 'rejected']),
            ],
            'submitted_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],
            'approved_by' => [
                'nullable',
                'integer',
                'exists:users,id',
                'required_if:status,approved',
            ],
            'approved_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
                'after_or_equal:submitted_at',
            ],
            'approval_notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'rejection_reason' => [
                'nullable',
                'string',
                'max:500',
                'required_if:status,rejected',
            ],
            'location' => [
                'nullable',
                'string',
                'max:255',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
            'created_by' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'updated_by' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'locked_at' => [
                'nullable',
                'date',
            ],
            'locked_by' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'external_reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'synced_to_payroll' => [
                'nullable',
                'boolean',
            ],
            'payroll_sync_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hours_worked.min' => 'Hours worked must be at least 15 minutes (0.25 hours).',
            'hours_worked.max' => 'Hours worked cannot exceed 24 hours in a single day.',
            'end_time.after' => 'End time must be after start time.',
            'break_duration.max' => 'Break duration cannot exceed 8 hours.',
            'description.min' => 'Description must be at least 10 characters.',
            'hourly_rate.required_if' => 'Hourly rate is required for billable time entries.',
            'approved_by.required_if' => 'Approver is required for approved time entries.',
            'rejection_reason.required_if' => 'Rejection reason is required for rejected time entries.',
            'approved_at.after_or_equal' => 'Approval date must be on or after submission date.',
            'date.before_or_equal' => 'Time entry date cannot be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'employee',
            'project_id' => 'project',
            'project_assignment_id' => 'project assignment',
            'hours_worked' => 'hours worked',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'break_duration' => 'break duration',
            'task_category' => 'task category',
            'activity_type' => 'activity type',
            'is_billable' => 'billable status',
            'hourly_rate' => 'hourly rate',
            'billable_amount' => 'billable amount',
            'submitted_at' => 'submission date',
            'approved_by' => 'approver',
            'approved_at' => 'approval date',
            'approval_notes' => 'approval notes',
            'rejection_reason' => 'rejection reason',
            'created_by' => 'creator',
            'updated_by' => 'updater',
            'locked_at' => 'lock date',
            'locked_by' => 'locked by',
            'external_reference' => 'external reference',
            'synced_to_payroll' => 'payroll sync status',
            'payroll_sync_at' => 'payroll sync date',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: If start and end times are provided, calculate duration
            if ($this->start_time && $this->end_time) {
                $start = \Carbon\Carbon::createFromFormat('H:i', $this->start_time);
                $end = \Carbon\Carbon::createFromFormat('H:i', $this->end_time);
                
                // Handle overnight work (end time next day)
                if ($end->lt($start)) {
                    $end->addDay();
                }
                
                $calculatedHours = $end->diffInMinutes($start) / 60;
                
                // Subtract break duration if provided
                if ($this->break_duration) {
                    $calculatedHours -= $this->break_duration;
                }
                
                // Allow 15-minute tolerance for rounding
                $tolerance = 0.25;
                if (abs($calculatedHours - $this->hours_worked) > $tolerance) {
                    $validator->errors()->add('hours_worked', 'Hours worked does not match calculated time from start/end times.');
                }
            }

            // Custom validation: Submitted entries should have submission date
            if ($this->status === 'submitted' && !$this->submitted_at) {
                $validator->errors()->add('submitted_at', 'Submission date is required for submitted time entries.');
            }

            // Custom validation: Approved entries should have approval date and approver
            if ($this->status === 'approved') {
                if (!$this->approved_at) {
                    $validator->errors()->add('approved_at', 'Approval date is required for approved time entries.');
                }
                if (!$this->approved_by) {
                    $validator->errors()->add('approved_by', 'Approver is required for approved time entries.');
                }
            }

            // Custom validation: Billable amount should match hours * rate if both provided
            if ($this->is_billable && $this->hourly_rate && $this->hours_worked) {
                $expectedAmount = $this->hours_worked * $this->hourly_rate;
                if ($this->billable_amount && abs($this->billable_amount - $expectedAmount) > 0.01) {
                    $validator->errors()->add('billable_amount', 'Billable amount does not match hours worked × hourly rate.');
                }
            }

            // Custom validation: Non-billable entries should not have hourly rate or billable amount
            if (!$this->is_billable) {
                if ($this->hourly_rate) {
                    $validator->errors()->add('hourly_rate', 'Hourly rate should not be set for non-billable time entries.');
                }
                if ($this->billable_amount) {
                    $validator->errors()->add('billable_amount', 'Billable amount should not be set for non-billable time entries.');
                }
            }

            // Custom validation: Locked entries should have lock date and locker
            if ($this->locked_at && !$this->locked_by) {
                $validator->errors()->add('locked_by', 'Locker is required when time entry is locked.');
            }

            // Custom validation: Payroll sync date required if synced to payroll
            if ($this->synced_to_payroll && !$this->payroll_sync_at) {
                $validator->errors()->add('payroll_sync_at', 'Payroll sync date is required when synced to payroll.');
            }
        });
    }
} 