<?php

namespace App\Models\Customer;

use App\Models\Project\Project;
use App\Models\Financial\FinancialRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
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
        'industry',
        'company_size',
        'tax_id',
        'annual_revenue',
        'status',
        'priority',
        'first_contact_date',
        'last_contact_date',
        'preferred_currency',
        'payment_terms',
        'credit_limit',
        'outstanding_balance',
        'additional_contacts',
        'communication_preferences',
        'notes',
        'requirements',
        'lead_source',
        'assigned_sales_rep',
        'contract_start_date',
        'contract_end_date',
        'auto_renewal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'annual_revenue' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'first_contact_date' => 'date',
        'last_contact_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'auto_renewal' => 'boolean',
        'additional_contacts' => 'array',
        'communication_preferences' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all projects for this customer.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get all financial records related to this customer.
     */
    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class, 'related_entity_id')
                    ->where('related_entity_type', 'customer');
    }

    /**
     * Get active projects for this customer.
     */
    public function activeProjects(): HasMany
    {
        return $this->projects()->where('status', 'active');
    }

    /**
     * Get completed projects for this customer.
     */
    public function completedProjects(): HasMany
    {
        return $this->projects()->where('status', 'completed');
    }

    /**
     * Check if customer is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if customer is a prospect.
     */
    public function isProspect(): bool
    {
        return $this->status === 'prospect';
    }

    /**
     * Check if customer is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if customer is a former client.
     */
    public function isFormer(): bool
    {
        return $this->status === 'former';
    }

    /**
     * Check if customer is VIP.
     */
    public function isVip(): bool
    {
        return $this->priority === 'vip';
    }

    /**
     * Check if customer has outstanding balance.
     */
    public function hasOutstandingBalance(): bool
    {
        return $this->outstanding_balance > 0;
    }

    /**
     * Check if customer is over credit limit.
     */
    public function isOverCreditLimit(): bool
    {
        if (!$this->credit_limit) {
            return false;
        }

        return $this->outstanding_balance > $this->credit_limit;
    }

    /**
     * Check if contract is expiring soon (within 30 days).
     */
    public function isContractExpiringSoon(): bool
    {
        if (!$this->contract_end_date) {
            return false;
        }

        return now()->diffInDays($this->contract_end_date, false) <= 30 && 
               now()->diffInDays($this->contract_end_date, false) >= 0;
    }

    /**
     * Check if contract has expired.
     */
    public function isContractExpired(): bool
    {
        if (!$this->contract_end_date) {
            return false;
        }

        return now()->gt($this->contract_end_date);
    }

    /**
     * Get customer's full address.
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
     * Get total project value for this customer.
     */
    public function getTotalProjectValueAttribute(): float
    {
        return $this->projects()->sum('budget') ?? 0;
    }

    /**
     * Get total revenue from this customer.
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->financialRecords()
                    ->where('type', 'revenue')
                    ->sum('amount');
    }

    /**
     * Get customer lifetime value.
     */
    public function getLifetimeValueAttribute(): float
    {
        return $this->total_revenue;
    }

    /**
     * Get number of active projects.
     */
    public function getActiveProjectsCountAttribute(): int
    {
        return $this->activeProjects()->count();
    }

    /**
     * Get number of completed projects.
     */
    public function getCompletedProjectsCountAttribute(): int
    {
        return $this->completedProjects()->count();
    }

    /**
     * Get customer relationship duration in days.
     */
    public function getRelationshipDurationAttribute(): ?int
    {
        if (!$this->first_contact_date) {
            return null;
        }

        return $this->first_contact_date->diffInDays(now());
    }

    /**
     * Update last contact date.
     */
    public function updateLastContact(): void
    {
        $this->update(['last_contact_date' => now()]);
    }

    /**
     * Convert prospect to active customer.
     */
    public function convertToCustomer(): void
    {
        $this->update([
            'status' => 'active',
            'first_contact_date' => $this->first_contact_date ?? now(),
        ]);
    }

    /**
     * Add payment to reduce outstanding balance.
     */
    public function addPayment(float $amount): void
    {
        $newBalance = max(0, $this->outstanding_balance - $amount);
        $this->update(['outstanding_balance' => $newBalance]);
    }

    /**
     * Add charge to increase outstanding balance.
     */
    public function addCharge(float $amount): void
    {
        $newBalance = $this->outstanding_balance + $amount;
        $this->update(['outstanding_balance' => $newBalance]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CustomerFactory::new();
    }

    /**
     * Generate a unique customer ID.
     */
    public static function generateCustomerId(): string
    {
        do {
            $id = 'CUS' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('customer_id', $id)->exists());

        return $id;
    }

    /**
     * Boot method to auto-generate customer ID.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (!$customer->customer_id) {
                $customer->customer_id = static::generateCustomerId();
            }
        });
    }
} 