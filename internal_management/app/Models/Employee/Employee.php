<?php

namespace App\Models\Employee;

use App\Models\User;
use App\Models\Project\Project;
use App\Models\Project\ProjectAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'user_id',
        'job_title',
        'department',
        'hire_date',
        'termination_date',
        'salary',
        'employment_type',
        'manager_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'notes',
        'benefits',
        'skills',
        'last_review_date',
        'next_review_date',
        'performance_rating',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
        'salary' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'benefits' => 'array',
        'skills' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user associated with the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager of this employee.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the employees that report to this employee.
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get all project assignments for this employee.
     */
    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    /**
     * Get all time entries for this employee.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get projects this employee is assigned to.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_assignments')
                    ->withPivot(['role_on_project', 'allocation_percentage', 'start_date', 'end_date', 'is_active'])
                    ->withTimestamps();
    }

    /**
     * Get active project assignments.
     */
    public function activeProjectAssignments(): HasMany
    {
        return $this->projectAssignments()->where('is_active', true);
    }

    /**
     * Check if employee is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if employee is terminated.
     */
    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    /**
     * Check if employee is on leave.
     */
    public function isOnLeave(): bool
    {
        return $this->status === 'on-leave';
    }

    /**
     * Get employee's full name from associated user.
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    /**
     * Get employee's email from associated user.
     */
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    /**
     * Get employee's full address.
     */
    public function getFullAddressAttribute(): string
    {
        $address = collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');

        return $address;
    }

    /**
     * Calculate years of service.
     */
    public function getYearsOfServiceAttribute(): float
    {
        $endDate = $this->termination_date ?? now();
        return $this->hire_date->diffInYears($endDate, true);
    }

    /**
     * Check if employee is due for review.
     */
    public function isDueForReview(): bool
    {
        if (!$this->next_review_date) {
            return false;
        }

        return now()->gte($this->next_review_date);
    }

    /**
     * Get total allocation percentage across all active projects.
     */
    public function getTotalAllocationAttribute(): float
    {
        return $this->activeProjectAssignments()->sum('allocation_percentage');
    }

    /**
     * Check if employee is over-allocated.
     */
    public function isOverAllocated(): bool
    {
        return $this->total_allocation > 100;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\EmployeeFactory::new();
    }

    /**
     * Generate a unique employee ID.
     */
    public static function generateEmployeeId(): string
    {
        do {
            $id = 'EMP' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('employee_id', $id)->exists());

        return $id;
    }

    /**
     * Boot method to auto-generate employee ID.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (!$employee->employee_id) {
                $employee->employee_id = static::generateEmployeeId();
            }
        });
    }
} 