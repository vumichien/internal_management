<?php

namespace App\Models\Employee;

use App\Models\Project\Project;
use App\Models\Project\ProjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'entry_id',
        'employee_id',
        'project_id',
        'project_assignment_id',
        'date',
        'hours_worked',
        'start_time',
        'end_time',
        'break_duration',
        'description',
        'task_category',
        'activity_type',
        'tags',
        'is_billable',
        'hourly_rate',
        'billable_amount',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'location',
        'metadata',
        'created_by',
        'updated_by',
        'locked_at',
        'locked_by',
        'external_reference',
        'synced_to_payroll',
        'payroll_sync_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'decimal:2',
        'break_duration' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'billable_amount' => 'decimal:2',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'payroll_sync_at' => 'datetime',
        'is_billable' => 'boolean',
        'synced_to_payroll' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($timeEntry) {
            if (empty($timeEntry->entry_id)) {
                $timeEntry->entry_id = self::generateEntryId();
            }
            
            // Auto-calculate billable amount if hourly rate is provided
            if ($timeEntry->is_billable && $timeEntry->hourly_rate && $timeEntry->hours_worked) {
                $timeEntry->billable_amount = $timeEntry->hours_worked * $timeEntry->hourly_rate;
            }
        });

        static::updating(function ($timeEntry) {
            // Recalculate billable amount if relevant fields change
            if ($timeEntry->is_billable && $timeEntry->hourly_rate && $timeEntry->hours_worked) {
                $timeEntry->billable_amount = $timeEntry->hours_worked * $timeEntry->hourly_rate;
            } elseif (!$timeEntry->is_billable) {
                $timeEntry->billable_amount = 0;
            }
        });
    }

    /**
     * Generate a unique entry ID.
     */
    private static function generateEntryId(): string
    {
        do {
            $lastEntry = self::withTrashed()->orderBy('id', 'desc')->first();
            $nextNumber = $lastEntry ? (int) substr($lastEntry->entry_id, 2) + 1 : 1;
            $entryId = 'TE' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        } while (self::withTrashed()->where('entry_id', $entryId)->exists());

        return $entryId;
    }

    /**
     * Get the employee that this time entry belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the project that this time entry is for.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the project assignment associated with this time entry.
     */
    public function projectAssignment(): BelongsTo
    {
        return $this->belongsTo(ProjectAssignment::class);
    }

    /**
     * Get the user who approved this time entry.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this time entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this time entry.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who locked this time entry.
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Check if the time entry is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the time entry is submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if the time entry is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the time entry is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the time entry is locked.
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /**
     * Check if the time entry is billable.
     */
    public function isBillable(): bool
    {
        return $this->is_billable;
    }

    /**
     * Check if the time entry is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if the time entry can be submitted.
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->hours_worked > 0;
    }

    /**
     * Check if the time entry can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if the time entry can be rejected.
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Get the actual work duration (end_time - start_time - break_duration).
     */
    public function getActualWorkDurationAttribute(): ?float
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $totalHours = $end->diffInMinutes($start) / 60;
        
        return $totalHours - $this->break_duration;
    }

    /**
     * Get the time variance (hours_worked vs actual_work_duration).
     */
    public function getTimeVarianceAttribute(): ?float
    {
        $actualDuration = $this->actual_work_duration;
        
        if ($actualDuration === null) {
            return null;
        }

        return $this->hours_worked - $actualDuration;
    }

    /**
     * Submit the time entry for approval.
     */
    public function submit(): bool
    {
        if (!$this->canBeSubmitted()) {
            return false;
        }

        $this->status = 'submitted';
        $this->submitted_at = now();
        return $this->save();
    }

    /**
     * Approve the time entry.
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->rejection_reason = null; // Clear any previous rejection reason
        
        return $this->save();
    }

    /**
     * Reject the time entry.
     */
    public function reject(User $rejector, string $reason): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->status = 'rejected';
        $this->approved_by = null;
        $this->approved_at = null;
        $this->approval_notes = null;
        $this->rejection_reason = $reason;
        
        return $this->save();
    }

    /**
     * Lock the time entry (typically for payroll processing).
     */
    public function lock(User $locker): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        $this->status = 'locked';
        $this->locked_by = $locker->id;
        $this->locked_at = now();
        
        return $this->save();
    }

    /**
     * Mark as synced to payroll.
     */
    public function markSyncedToPayroll(): bool
    {
        $this->synced_to_payroll = true;
        $this->payroll_sync_at = now();
        
        return $this->save();
    }

    /**
     * Calculate overtime hours (if applicable).
     */
    public function getOvertimeHoursAttribute(): float
    {
        // This is a simple implementation - you might want to make this configurable
        $regularHoursPerDay = 8;
        return max(0, $this->hours_worked - $regularHoursPerDay);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by employee.
     */
    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get billable entries.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope to get non-billable entries.
     */
    public function scopeNonBillable($query)
    {
        return $query->where('is_billable', false);
    }

    /**
     * Scope to get entries pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope to get approved entries.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get locked entries.
     */
    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    /**
     * Scope to get entries by task category.
     */
    public function scopeByTaskCategory($query, string $category)
    {
        return $query->where('task_category', $category);
    }

    /**
     * Scope to get entries by activity type.
     */
    public function scopeByActivityType($query, string $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    /**
     * Scope to get entries by location.
     */
    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TimeEntryFactory::new();
    }
}
