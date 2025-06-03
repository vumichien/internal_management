<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'role_on_project' => ['required', 'string', 'max:100'],
            'allocation_percentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'actual_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', Rule::in(['pending', 'active', 'completed', 'on-hold', 'cancelled'])],
            'is_billable' => ['required', 'boolean'],
            'is_primary_assignment' => ['nullable', 'boolean'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'completion_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'responsibilities' => ['nullable', 'array'],
            'responsibilities.*' => ['string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'skills_required' => ['nullable', 'array'],
            'skills_required.*' => ['string', 'max:100'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*' => ['string', 'max:500'],
            'assigned_by' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_at' => ['nullable', 'date', 'before_or_equal:now'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date', 'before_or_equal:now', 'after_or_equal:assigned_at'],
            'performance_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'last_performance_review' => ['nullable', 'date', 'before_or_equal:today'],
            'performance_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'allocation_percentage.min' => 'Allocation percentage must be at least 1%.',
            'allocation_percentage.max' => 'Allocation percentage cannot exceed 100%.',
            'end_date.after' => 'End date must be after start date.',
            'actual_end_date.after_or_equal' => 'Actual end date must be on or after start date.',
            'completion_percentage.max' => 'Completion percentage cannot exceed 100%.',
            'performance_rating.min' => 'Performance rating must be between 1 and 5.',
            'performance_rating.max' => 'Performance rating must be between 1 and 5.',
            'approved_at.after_or_equal' => 'Approval date must be on or after assignment date.',
        ];
    }
} 