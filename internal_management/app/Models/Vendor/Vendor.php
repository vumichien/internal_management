<?php

namespace App\Models\Vendor;

use App\Models\Project\Project;
use App\Models\Financial\FinancialRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vendor_id',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'website',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'service_type',
        'industry',
        'company_size',
        'tax_id',
        'business_license',
        'vendor_type',
        'status',
        'priority',
        'preferred_currency',
        'payment_terms',
        'credit_limit',
        'outstanding_balance',
        'bank_account_info',
        'first_contact_date',
        'last_contact_date',
        'contract_start_date',
        'contract_end_date',
        'auto_renewal',
        'assigned_procurement_rep',
        'performance_rating',
        'last_performance_review',
        'delivery_success_rate',
        'average_delivery_time',
        'services_provided',
        'certifications',
        'capabilities',
        'additional_contacts',
        'communication_preferences',
        'notes',
        'requirements',
        'compliance_notes',
        'lead_source',
        'insurance_verified',
        'insurance_expiry_date',
        'background_check_completed',
        'background_check_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'average_delivery_time' => 'decimal:2',
        'first_contact_date' => 'date',
        'last_contact_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'last_performance_review' => 'date',
        'insurance_expiry_date' => 'date',
        'background_check_date' => 'date',
        'auto_renewal' => 'boolean',
        'insurance_verified' => 'boolean',
        'background_check_completed' => 'boolean',
        'services_provided' => 'array',
        'certifications' => 'array',
        'capabilities' => 'array',
        'additional_contacts' => 'array',
        'communication_preferences' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'bank_account_info', // Sensitive financial information
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\VendorFactory::new();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->vendor_id)) {
                $vendor->vendor_id = self::generateVendorId();
            }
        });
    }

    /**
     * Generate a unique vendor ID.
     */
    private static function generateVendorId(): string
    {
        do {
            $lastVendor = self::withTrashed()->orderBy('id', 'desc')->first();
            $nextNumber = $lastVendor ? (int) substr($lastVendor->vendor_id, 3) + 1 : 1;
            $vendorId = 'VEN' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        } while (self::withTrashed()->where('vendor_id', $vendorId)->exists());

        return $vendorId;
    }

    /**
     * Get the projects associated with this vendor.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the financial records associated with this vendor.
     */
    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class, 'related_entity_id')
                    ->where('related_entity_type', 'vendor');
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Check if the vendor is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the vendor is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the vendor is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if the vendor is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if the vendor is terminated.
     */
    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    /**
     * Check if the vendor is a critical priority.
     */
    public function isCritical(): bool
    {
        return $this->priority === 'critical';
    }

    /**
     * Check if the vendor's contract is expiring soon.
     */
    public function isContractExpiringSoon(int $days = 30): bool
    {
        if (!$this->contract_end_date) {
            return false;
        }

        return $this->contract_end_date->diffInDays(now()) <= $days && $this->contract_end_date->isFuture();
    }

    /**
     * Check if the vendor's insurance is expiring soon.
     */
    public function isInsuranceExpiringSoon(int $days = 30): bool
    {
        if (!$this->insurance_expiry_date) {
            return false;
        }

        return $this->insurance_expiry_date->diffInDays(now()) <= $days && $this->insurance_expiry_date->isFuture();
    }

    /**
     * Check if the vendor needs a performance review.
     */
    public function needsPerformanceReview(int $months = 12): bool
    {
        if (!$this->last_performance_review) {
            return true; // Never reviewed
        }

        return $this->last_performance_review->diffInMonths(now()) >= $months;
    }

    /**
     * Get the vendor's performance status based on rating.
     */
    public function getPerformanceStatusAttribute(): string
    {
        if (!$this->performance_rating) {
            return 'not_rated';
        }

        if ($this->performance_rating >= 4.5) {
            return 'excellent';
        } elseif ($this->performance_rating >= 3.5) {
            return 'good';
        } elseif ($this->performance_rating >= 2.5) {
            return 'average';
        } else {
            return 'poor';
        }
    }

    /**
     * Calculate the relationship duration in months.
     */
    public function getRelationshipDurationAttribute(): ?int
    {
        if (!$this->first_contact_date) {
            return null;
        }

        return $this->first_contact_date->diffInMonths(now());
    }

    /**
     * Get the total amount spent with this vendor.
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->financialRecords()
                    ->where('type', 'expense')
                    ->sum('amount');
    }

    /**
     * Activate the vendor.
     */
    public function activate(): bool
    {
        $this->status = 'active';
        return $this->save();
    }

    /**
     * Suspend the vendor.
     */
    public function suspend(): bool
    {
        $this->status = 'suspended';
        return $this->save();
    }

    /**
     * Terminate the vendor relationship.
     */
    public function terminate(): bool
    {
        $this->status = 'terminated';
        return $this->save();
    }

    /**
     * Update the vendor's performance rating.
     */
    public function updatePerformanceRating(float $rating, ?string $notes = null): bool
    {
        $this->performance_rating = $rating;
        $this->last_performance_review = now();
        
        if ($notes) {
            $this->notes = $this->notes ? $this->notes . "\n\nPerformance Review (" . now()->format('Y-m-d') . "): " . $notes : $notes;
        }
        
        return $this->save();
    }

    /**
     * Update the last contact date.
     */
    public function updateLastContact(): bool
    {
        $this->last_contact_date = now();
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
     * Scope to filter by vendor type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('vendor_type', $type);
    }

    /**
     * Scope to filter by service type.
     */
    public function scopeByServiceType($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope to get vendors with expiring contracts.
     */
    public function scopeWithExpiringContracts($query, int $days = 30)
    {
        return $query->whereNotNull('contract_end_date')
                    ->where('contract_end_date', '<=', now()->addDays($days))
                    ->where('contract_end_date', '>', now());
    }

    /**
     * Scope to get vendors with expiring insurance.
     */
    public function scopeWithExpiringInsurance($query, int $days = 30)
    {
        return $query->whereNotNull('insurance_expiry_date')
                    ->where('insurance_expiry_date', '<=', now()->addDays($days))
                    ->where('insurance_expiry_date', '>', now());
    }

    /**
     * Scope to get vendors needing performance review.
     */
    public function scopeNeedingPerformanceReview($query, int $months = 12)
    {
        return $query->where(function ($q) use ($months) {
            $q->whereNull('last_performance_review')
              ->orWhere('last_performance_review', '<=', now()->subMonths($months));
        });
    }
}
