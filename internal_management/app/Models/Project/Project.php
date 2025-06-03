<?php

namespace App\Models\Project;

use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use App\Models\Employee\TimeEntry;
use App\Models\Financial\FinancialRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'actual_end_date',
        'status',
        'priority',
        'budget',
        'actual_cost',
        'estimated_hours',
        'actual_hours',
        'currency',
        'customer_id',
        'project_manager_id',
        'category',
        'type',
        'completion_percentage',
        'billing_type',
        'hourly_rate',
        'is_billable',
        'custom_attributes',
        'milestones',
        'deliverables',
        'risk_level',
        'notes',
        'requirements',
        'is_archived',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_archived' => 'boolean',
        'custom_attributes' => 'array',
        'milestones' => 'array',
        'deliverables' => 'array',
        'archived_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the customer associated with the project.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the project manager (employee) for this project.
     */
    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    /**
     * Get all project assignments for this project.
     */
    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    /**
     * Get all time entries for this project.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get all financial records for this project.
     */
    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    /**
     * Get employees assigned to this project.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_assignments')
                    ->withPivot(['role_on_project', 'allocation_percentage', 'start_date', 'end_date', 'is_active'])
                    ->withTimestamps();
    }

    /**
     * Get active project assignments.
     */
    public function activeAssignments(): HasMany
    {
        return $this->projectAssignments()->where('is_active', true);
    }

    /**
     * Get revenue records for this project.
     */
    public function revenues(): HasMany
    {
        return $this->financialRecords()->where('type', 'revenue');
    }

    /**
     * Get expense records for this project.
     */
    public function expenses(): HasMany
    {
        return $this->financialRecords()->where('type', 'expense');
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if project is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if project is on hold.
     */
    public function isOnHold(): bool
    {
        return $this->status === 'on-hold';
    }

    /**
     * Check if project is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->end_date || $this->isCompleted()) {
            return false;
        }

        return now()->gt($this->end_date);
    }

    /**
     * Check if project is over budget.
     */
    public function isOverBudget(): bool
    {
        if (!$this->budget) {
            return false;
        }

        return $this->actual_cost > $this->budget;
    }

    /**
     * Get project duration in days.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get actual project duration in days.
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
     * Get budget variance (actual cost - budget).
     */
    public function getBudgetVarianceAttribute(): ?float
    {
        if (!$this->budget) {
            return null;
        }

        return $this->actual_cost - $this->budget;
    }

    /**
     * Get budget variance percentage.
     */
    public function getBudgetVariancePercentageAttribute(): ?float
    {
        if (!$this->budget || $this->budget == 0) {
            return null;
        }

        return (($this->actual_cost - $this->budget) / $this->budget) * 100;
    }

    /**
     * Get hours variance (actual hours - estimated hours).
     */
    public function getHoursVarianceAttribute(): ?float
    {
        if (!$this->estimated_hours) {
            return null;
        }

        return $this->actual_hours - $this->estimated_hours;
    }

    /**
     * Get total revenue for this project.
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->revenues()->sum('amount');
    }

    /**
     * Get total expenses for this project.
     */
    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }

    /**
     * Get project profit (revenue - expenses).
     */
    public function getProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_expenses;
    }

    /**
     * Get profit margin percentage.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if ($this->total_revenue == 0) {
            return null;
        }

        return ($this->profit / $this->total_revenue) * 100;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ProjectFactory::new();
    }

    /**
     * Generate a unique project ID.
     */
    public static function generateProjectId(): string
    {
        do {
            $id = 'PRJ' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('project_id', $id)->exists());

        return $id;
    }

    /**
     * Update project completion percentage based on completed milestones.
     */
    public function updateCompletionPercentage(): void
    {
        if (!$this->milestones || empty($this->milestones)) {
            return;
        }

        $totalMilestones = count($this->milestones);
        $completedMilestones = collect($this->milestones)->where('completed', true)->count();

        $this->update([
            'completion_percentage' => ($completedMilestones / $totalMilestones) * 100
        ]);
    }

    /**
     * Archive the project.
     */
    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Unarchive the project.
     */
    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    /**
     * Boot method to auto-generate project ID.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (!$project->project_id) {
                $project->project_id = static::generateProjectId();
            }
        });
    }
} 