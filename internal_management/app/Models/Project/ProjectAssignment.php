<?php

namespace App\Models\Project;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAssignment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'assignment_id',
        'project_id',
        'employee_id',
        'role_on_project',
        'allocation_percentage',
        'hourly_rate',
        'start_date',
        'end_date',
        'actual_end_date',
        'status',
        'is_billable',
        'is_primary_assignment',
        'estimated_hours',
        'actual_hours',
        'completion_percentage',
        'responsibilities',
        'notes',
        'skills_required',
        'deliverables',
        'assigned_by',
        'assigned_at',
        'approved_by',
        'approved_at',
        'performance_rating',
        'last_performance_review',
        'performance_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allocation_percentage' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'last_performance_review' => 'date',
        'assigned_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_billable' => 'boolean',
        'is_primary_assignment' => 'boolean',
        'skills_required' => 'array',
        'responsibilities' => 'array',
        'deliverables' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ProjectAssignmentFactory::new();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assignment) {
            if (empty($assignment->assignment_id)) {
                $assignment->assignment_id = self::generateAssignmentId();
            }
            
            if (empty($assignment->assigned_at)) {
                $assignment->assigned_at = now();
            }
        });
    }

    /**
     * Generate a unique assignment ID.
     */
    private static function generateAssignmentId(): string
    {
        do {
            $lastAssignment = self::withTrashed()->orderBy('id', 'desc')->first();
            $nextNumber = $lastAssignment ? (int) substr($lastAssignment->assignment_id, 3) + 1 : 1;
            $assignmentId = 'ASG' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        } while (self::withTrashed()->where('assignment_id', $assignmentId)->exists());

        return $assignmentId;
    }

    /**
     * Get the project that this assignment belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the employee assigned to this project.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who assigned this employee to the project.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who approved this assignment.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the assignment is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the assignment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the assignment is on hold.
     */
    public function isOnHold(): bool
    {
        return $this->status === 'on-hold';
    }

    /**
     * Check if the assignment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the assignment is billable.
     */
    public function isBillable(): bool
    {
        return $this->is_billable;
    }

    /**
     * Check if this is the employee's primary assignment.
     */
    public function isPrimaryAssignment(): bool
    {
        return $this->is_primary_assignment;
    }

    /**
     * Check if the assignment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->end_date && $this->end_date->isPast() && !$this->isCompleted();
    }

    /**
     * Check if the assignment is approved.
     */
    public function isApproved(): bool
    {
        return !is_null($this->approved_by) && !is_null($this->approved_at);
    }

    /**
     * Get the assignment duration in days.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get the actual duration in days.
     */
    public function getActualDurationAttribute(): ?int
    {
        if (!$this->start_date) {
            return null;
        }

        $endDate = $this->actual_end_date ?? ($this->isCompleted() ? now() : null);
        
        if (!$endDate) {
            return null;
        }

        return $this->start_date->diffInDays($endDate);
    }

    /**
     * Get the hours variance (actual vs estimated).
     */
    public function getHoursVarianceAttribute(): ?float
    {
        if (!$this->estimated_hours) {
            return null;
        }

        return $this->actual_hours - $this->estimated_hours;
    }

    /**
     * Get the hours variance percentage.
     */
    public function getHoursVariancePercentageAttribute(): ?float
    {
        if (!$this->estimated_hours || $this->estimated_hours == 0) {
            return null;
        }

        return (($this->actual_hours - $this->estimated_hours) / $this->estimated_hours) * 100;
    }

    /**
     * Calculate total billable amount based on actual hours and hourly rate.
     */
    public function getTotalBillableAmountAttribute(): float
    {
        if (!$this->is_billable || !$this->hourly_rate) {
            return 0;
        }

        return $this->actual_hours * $this->hourly_rate;
    }

    /**
     * Approve the assignment.
     */
    public function approve(User $approver): bool
    {
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        return $this->save();
    }

    /**
     * Complete the assignment.
     */
    public function complete(?string $notes = null): bool
    {
        $this->status = 'completed';
        $this->actual_end_date = now();
        $this->completion_percentage = 100;
        
        if ($notes) {
            $this->notes = $this->notes ? $this->notes . "\n\nCompleted: " . $notes : $notes;
        }
        
        return $this->save();
    }

    /**
     * Update the assignment progress.
     */
    public function updateProgress(float $percentage, ?float $actualHours = null): bool
    {
        $this->completion_percentage = min(100, max(0, $percentage));
        
        if ($actualHours !== null) {
            $this->actual_hours = $actualHours;
        }
        
        if ($this->completion_percentage >= 100) {
            $this->status = 'completed';
            $this->actual_end_date = now();
        }
        
        return $this->save();
    }

    /**
     * Update performance rating.
     */
    public function updatePerformanceRating(float $rating, ?string $notes = null): bool
    {
        $this->performance_rating = $rating;
        $this->last_performance_review = now();
        
        if ($notes) {
            $this->performance_notes = $notes;
        }
        
        return $this->save();
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
     * Scope to filter by employee.
     */
    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get billable assignments.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope to get primary assignments.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary_assignment', true);
    }

    /**
     * Scope to get overdue assignments.
     */
    public function scopeOverdue($query)
    {
        return $query->where('end_date', '<', now())
                    ->where('status', '!=', 'completed');
    }

    /**
     * Scope to get assignments within date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}
