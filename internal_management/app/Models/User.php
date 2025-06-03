<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Employee\Employee;
use App\Models\Employee\TimeEntry;
use App\Models\Financial\FinancialRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'github_id',
        'google_id',
        'role',
        'status',
        'profile_image_path',
        'phone',
        'bio',
        'last_login_at',
        'last_login_ip',
        'preferences',
        'timezone',
        'locale',
        'is_verified',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'preferences' => 'array',
        'is_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the employee record associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the time entries created by this user (for approval).
     */
    public function approvedTimeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'approved_by');
    }

    /**
     * Get the financial records created by this user.
     */
    public function createdFinancialRecords()
    {
        return $this->hasMany(FinancialRecord::class, 'created_by');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee(): bool
    {
        return $this->hasRole('employee');
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get user's full profile image URL.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (!$this->profile_image_path) {
            return null;
        }

        return asset('storage/' . $this->profile_image_path);
    }

    /**
     * Update user's last login information.
     */
    public function updateLastLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Check if user has a social provider linked.
     */
    public function hasSocialProvider(string $provider): bool
    {
        return !empty($this->{$provider . '_id'});
    }

    /**
     * Check if user has Google authentication linked.
     */
    public function hasGoogleAuth(): bool
    {
        return $this->hasSocialProvider('google');
    }

    /**
     * Check if user has GitHub authentication linked.
     */
    public function hasGithubAuth(): bool
    {
        return $this->hasSocialProvider('github');
    }

    /**
     * Get all linked social providers for this user.
     */
    public function getLinkedProvidersAttribute(): array
    {
        $providers = [];
        
        if ($this->hasGoogleAuth()) {
            $providers[] = 'google';
        }
        
        if ($this->hasGithubAuth()) {
            $providers[] = 'github';
        }
        
        return $providers;
    }

    /**
     * Check if user can login with traditional password.
     */
    public function canLoginWithPassword(): bool
    {
        return !empty($this->password);
    }

    /**
     * Check if user is social-only (no password set).
     */
    public function isSocialOnly(): bool
    {
        return !$this->canLoginWithPassword() && !empty($this->linked_providers);
    }

    /**
     * Link a social provider to this user.
     */
    public function linkSocialProvider(string $provider, string $providerId): bool
    {
        $column = $provider . '_id';
        
        if (!in_array($column, $this->fillable)) {
            return false;
        }
        
        return $this->update([$column => $providerId]);
    }

    /**
     * Unlink a social provider from this user.
     */
    public function unlinkSocialProvider(string $provider): bool
    {
        $column = $provider . '_id';
        
        if (!in_array($column, $this->fillable)) {
            return false;
        }
        
        // Don't allow unlinking if it's the only authentication method
        if ($this->isSocialOnly() && count($this->linked_providers) === 1) {
            return false;
        }
        
        return $this->update([$column => null]);
    }

    /**
     * Find user by social provider ID.
     */
    public static function findBySocialProvider(string $provider, string $providerId): ?self
    {
        $column = $provider . '_id';
        return static::where($column, $providerId)->first();
    }

    /**
     * Create user from social provider data.
     */
    public static function createFromSocialProvider(array $userData, string $provider, string $providerId): self
    {
        $userData[$provider . '_id'] = $providerId;
        $userData['email_verified_at'] = now(); // Social accounts are considered verified
        $userData['is_verified'] = true;
        $userData['role'] = $userData['role'] ?? 'employee';
        $userData['status'] = $userData['status'] ?? 'active';
        
        return static::create($userData);
    }
}
