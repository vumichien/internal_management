<?php

namespace App\Http\Requests\Financial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FinancialRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'type' => ['required', 'string', Rule::in(['revenue', 'expense', 'invoice', 'payment', 'refund'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001', 'max:999999.999999'],
            'amount_usd' => ['nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
            'category' => ['required', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after_or_equal:transaction_date'],
            'paid_date' => ['nullable', 'date', 'after_or_equal:transaction_date'],
            'related_entity_type' => ['nullable', 'string', Rule::in(['customer', 'vendor'])],
            'related_entity_id' => ['nullable', 'integer'],
            'status' => ['required', 'string', Rule::in(['draft', 'pending', 'approved', 'paid', 'overdue', 'cancelled'])],
            'is_billable' => ['required', 'boolean'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_frequency' => ['nullable', 'string', Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])],
            'next_occurrence' => ['nullable', 'date', 'after:transaction_date'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_type' => ['nullable', 'string', 'max:50'],
            'account_code' => ['nullable', 'string', 'max:20'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date', 'before_or_equal:now'],
            'approval_notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'synced_to_accounting' => ['nullable', 'boolean'],
            'accounting_sync_at' => ['nullable', 'date'],
            'accounting_system_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Amount must be at least $0.01.',
            'currency.regex' => 'Currency must be a valid 3-letter ISO code (e.g., USD, EUR).',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'description.min' => 'Description must be at least 5 characters.',
            'due_date.after_or_equal' => 'Due date must be on or after transaction date.',
            'paid_date.after_or_equal' => 'Paid date must be on or after transaction date.',
            'next_occurrence.after' => 'Next occurrence must be after transaction date.',
            'tax_rate.max' => 'Tax rate cannot exceed 100%.',
            'discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: Recurring records need frequency and next occurrence
            if ($this->is_recurring) {
                if (!$this->recurring_frequency) {
                    $validator->errors()->add('recurring_frequency', 'Recurring frequency is required for recurring records.');
                }
                if (!$this->next_occurrence) {
                    $validator->errors()->add('next_occurrence', 'Next occurrence date is required for recurring records.');
                }
            }

            // Custom validation: Paid records should have paid date and payment method
            if ($this->status === 'paid') {
                if (!$this->paid_date) {
                    $validator->errors()->add('paid_date', 'Paid date is required for paid records.');
                }
                if (!$this->payment_method) {
                    $validator->errors()->add('payment_method', 'Payment method is required for paid records.');
                }
            }

            // Custom validation: Approved records should have approver and approval date
            if ($this->status === 'approved' && !$this->approved_by) {
                $validator->errors()->add('approved_by', 'Approver is required for approved records.');
            }

            // Custom validation: Related entity validation
            if ($this->related_entity_type && $this->related_entity_id) {
                $table = $this->related_entity_type === 'customer' ? 'customers' : 'vendors';
                if (!DB::table($table)->where('id', $this->related_entity_id)->exists()) {
                    $validator->errors()->add('related_entity_id', 'Related entity does not exist.');
                }
            }

            // Custom validation: Tax amount should not exceed total amount
            if ($this->tax_amount && $this->amount && $this->tax_amount > $this->amount) {
                $validator->errors()->add('tax_amount', 'Tax amount cannot exceed total amount.');
            }

            // Custom validation: Discount amount should not exceed total amount
            if ($this->discount_amount && $this->amount && $this->discount_amount > $this->amount) {
                $validator->errors()->add('discount_amount', 'Discount amount cannot exceed total amount.');
            }
        });
    }
} 