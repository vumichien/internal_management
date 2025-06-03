<?php

namespace App\Models\Financial;

use App\Models\Project\Project;
use App\Models\Customer\Customer;
use App\Models\Vendor\Vendor;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialRecord extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'record_id',
        'project_id',
        'type',
        'amount',
        'currency',
        'exchange_rate',
        'amount_usd',
        'description',
        'category',
        'subcategory',
        'reference_number',
        'external_reference',
        'transaction_date',
        'due_date',
        'paid_date',
        'related_entity_type',
        'related_entity_id',
        'status',
        'is_billable',
        'is_recurring',
        'recurring_frequency',
        'next_occurrence',
        'tax_amount',
        'tax_rate',
        'tax_type',
        'account_code',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'payment_method',
        'payment_reference',
        'discount_amount',
        'discount_percentage',
        'attachments',
        'metadata',
        'synced_to_accounting',
        'accounting_sync_at',
        'accounting_system_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_usd' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'next_occurrence' => 'date',
        'approved_at' => 'datetime',
        'accounting_sync_at' => 'datetime',
        'is_billable' => 'boolean',
        'is_recurring' => 'boolean',
        'synced_to_accounting' => 'boolean',
        'attachments' => 'array',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($record) {
            if (empty($record->record_id)) {
                $record->record_id = self::generateRecordId();
            }
            
            // Auto-calculate USD amount if different currency
            if ($record->currency !== 'USD' && $record->exchange_rate && $record->amount) {
                $record->amount_usd = $record->amount * $record->exchange_rate;
            } elseif ($record->currency === 'USD') {
                $record->amount_usd = $record->amount;
                $record->exchange_rate = 1.000000;
            }
        });

        static::updating(function ($record) {
            // Recalculate USD amount if relevant fields change
            if ($record->currency !== 'USD' && $record->exchange_rate && $record->amount) {
                $record->amount_usd = $record->amount * $record->exchange_rate;
            } elseif ($record->currency === 'USD') {
                $record->amount_usd = $record->amount;
                $record->exchange_rate = 1.000000;
            }
        });
    }

    /**
     * Generate a unique record ID.
     */
    private static function generateRecordId(): string
    {
        do {
            $lastRecord = self::withTrashed()->orderBy('id', 'desc')->first();
            $nextNumber = $lastRecord ? (int) substr($lastRecord->record_id, 2) + 1 : 1;
            $recordId = 'FR' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        } while (self::withTrashed()->where('record_id', $recordId)->exists());

        return $recordId;
    }

    /**
     * Get the project that this financial record belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this financial record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this financial record.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the related entity (polymorphic relationship).
     */
    public function relatedEntity(): MorphTo
    {
        return $this->morphTo('related_entity', 'related_entity_type', 'related_entity_id');
    }

    /**
     * Check if the record is revenue.
     */
    public function isRevenue(): bool
    {
        return in_array($this->type, ['revenue', 'invoice', 'payment']);
    }

    /**
     * Check if the record is expense.
     */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Check if the record is an invoice.
     */
    public function isInvoice(): bool
    {
        return $this->type === 'invoice';
    }

    /**
     * Check if the record is a payment.
     */
    public function isPayment(): bool
    {
        return $this->type === 'payment';
    }

    /**
     * Check if the record is a refund.
     */
    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    /**
     * Check if the record is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the record is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the record is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the record is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the record is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || 
               ($this->due_date && $this->due_date->isPast() && !$this->isPaid());
    }

    /**
     * Check if the record is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the record is billable.
     */
    public function isBillable(): bool
    {
        return $this->is_billable;
    }

    /**
     * Check if the record is recurring.
     */
    public function isRecurring(): bool
    {
        return $this->is_recurring;
    }

    /**
     * Get the net amount (amount - discount + tax).
     */
    public function getNetAmountAttribute(): float
    {
        return $this->amount - $this->discount_amount + $this->tax_amount;
    }

    /**
     * Get the gross amount (amount + tax).
     */
    public function getGrossAmountAttribute(): float
    {
        return $this->amount + $this->tax_amount;
    }

    /**
     * Get the total discount amount (percentage + fixed).
     */
    public function getTotalDiscountAttribute(): float
    {
        $percentageDiscount = ($this->amount * $this->discount_percentage) / 100;
        return $this->discount_amount + $percentageDiscount;
    }

    /**
     * Get the final amount after all calculations.
     */
    public function getFinalAmountAttribute(): float
    {
        $baseAmount = $this->amount;
        $discountAmount = $this->total_discount;
        $taxAmount = $this->tax_amount;
        
        return $baseAmount - $discountAmount + $taxAmount;
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get days since transaction date.
     */
    public function getDaysSinceTransactionAttribute(): int
    {
        return $this->transaction_date->diffInDays(now());
    }

    /**
     * Approve the financial record.
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        
        return $this->save();
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(?string $paymentMethod = null, ?string $paymentReference = null): bool
    {
        if (!$this->isApproved() && !$this->isPending()) {
            return false;
        }

        $this->status = 'paid';
        $this->paid_date = now();
        
        if ($paymentMethod) {
            $this->payment_method = $paymentMethod;
        }
        
        if ($paymentReference) {
            $this->payment_reference = $paymentReference;
        }
        
        return $this->save();
    }

    /**
     * Cancel the financial record.
     */
    public function cancel(): bool
    {
        if ($this->isPaid()) {
            return false; // Cannot cancel paid records
        }

        $this->status = 'cancelled';
        return $this->save();
    }

    /**
     * Mark as synced to accounting system.
     */
    public function markSyncedToAccounting(?string $accountingSystemId = null): bool
    {
        $this->synced_to_accounting = true;
        $this->accounting_sync_at = now();
        
        if ($accountingSystemId) {
            $this->accounting_system_id = $accountingSystemId;
        }
        
        return $this->save();
    }

    /**
     * Create next recurring record.
     */
    public function createNextRecurrence(): ?self
    {
        if (!$this->is_recurring || !$this->next_occurrence) {
            return null;
        }

        $nextRecord = $this->replicate();
        $nextRecord->record_id = null; // Will be auto-generated
        $nextRecord->transaction_date = $this->next_occurrence;
        $nextRecord->status = 'draft';
        $nextRecord->paid_date = null;
        $nextRecord->approved_by = null;
        $nextRecord->approved_at = null;
        $nextRecord->approval_notes = null;
        
        // Calculate next occurrence
        $nextRecord->next_occurrence = $this->calculateNextOccurrence($this->next_occurrence);
        
        $nextRecord->save();
        
        return $nextRecord;
    }

    /**
     * Calculate next occurrence date based on frequency.
     */
    private function calculateNextOccurrence($currentDate): ?\Carbon\Carbon
    {
        if (!$this->recurring_frequency) {
            return null;
        }

        $date = \Carbon\Carbon::parse($currentDate);
        
        switch ($this->recurring_frequency) {
            case 'weekly':
                return $date->addWeek();
            case 'monthly':
                return $date->addMonth();
            case 'quarterly':
                return $date->addMonths(3);
            case 'yearly':
                return $date->addYear();
            default:
                return null;
        }
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get revenue records.
     */
    public function scopeRevenue($query)
    {
        return $query->whereIn('type', ['revenue', 'invoice', 'payment']);
    }

    /**
     * Scope to get expense records.
     */
    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope to get billable records.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope to get overdue records.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['paid', 'cancelled']);
    }

    /**
     * Scope to get pending approval records.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved records.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get paid records.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get recurring records.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope to get records by currency.
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope to get records related to specific entity.
     */
    public function scopeRelatedTo($query, string $entityType, int $entityId)
    {
        return $query->where('related_entity_type', $entityType)
                    ->where('related_entity_id', $entityId);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\FinancialRecordFactory::new();
    }
}
