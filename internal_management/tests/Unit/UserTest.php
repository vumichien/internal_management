<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Employee\Employee;
use App\Models\Employee\TimeEntry;
use App\Models\Financial\FinancialRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'email',
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

        $this->assertEquals($fillable, $this->user->getFillable());
    }

    /** @test */
    public function it_has_hidden_attributes()
    {
        $hidden = [
            'password',
            'remember_token',
            'two_factor_secret',
        ];

        $this->assertEquals($hidden, $this->user->getHidden());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
            'is_verified' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'deleted_at' => 'datetime',
        ];

        foreach ($casts as $attribute => $cast) {
            $this->assertEquals($cast, $this->user->getCasts()[$attribute]);
        }
    }

    /** @test */
    public function it_hashes_password_automatically()
    {
        $password = 'test-password';
        $user = User::factory()->create(['password' => $password]);

        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNotEquals($password, $user->password);
    }

    /** @test */
    public function it_casts_preferences_to_array()
    {
        $preferences = ['theme' => 'dark', 'notifications' => true];
        $user = User::factory()->create(['preferences' => $preferences]);

        $this->assertIsArray($user->preferences);
        $this->assertEquals($preferences, $user->preferences);
    }

    /** @test */
    public function it_casts_boolean_attributes()
    {
        $user = User::factory()->create([
            'is_verified' => 1,
            'two_factor_enabled' => 0,
        ]);

        $this->assertIsBool($user->is_verified);
        $this->assertIsBool($user->two_factor_enabled);
        $this->assertTrue($user->is_verified);
        $this->assertFalse($user->two_factor_enabled);
    }

    /** @test */
    public function it_has_one_employee()
    {
        $employee = Employee::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(Employee::class, $this->user->employee);
        $this->assertEquals($employee->id, $this->user->employee->id);
    }

    /** @test */
    public function it_has_many_approved_time_entries()
    {
        $timeEntries = TimeEntry::factory()->count(3)->create(['approved_by' => $this->user->id]);

        $this->assertCount(3, $this->user->approvedTimeEntries);
        $this->assertInstanceOf(TimeEntry::class, $this->user->approvedTimeEntries->first());
    }

    /** @test */
    public function it_has_many_created_financial_records()
    {
        $records = FinancialRecord::factory()->count(2)->create(['created_by' => $this->user->id]);

        $this->assertCount(2, $this->user->createdFinancialRecords);
        $this->assertInstanceOf(FinancialRecord::class, $this->user->createdFinancialRecords->first());
    }

    /** @test */
    public function it_checks_if_user_has_specific_role()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $managerUser = User::factory()->create(['role' => 'manager']);
        $employeeUser = User::factory()->create(['role' => 'employee']);

        $this->assertTrue($adminUser->hasRole('admin'));
        $this->assertFalse($adminUser->hasRole('manager'));
        
        $this->assertTrue($managerUser->hasRole('manager'));
        $this->assertFalse($managerUser->hasRole('admin'));
        
        $this->assertTrue($employeeUser->hasRole('employee'));
        $this->assertFalse($employeeUser->hasRole('admin'));
    }

    /** @test */
    public function it_checks_if_user_is_admin()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $nonAdminUser = User::factory()->create(['role' => 'employee']);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($nonAdminUser->isAdmin());
    }

    /** @test */
    public function it_checks_if_user_is_manager()
    {
        $managerUser = User::factory()->create(['role' => 'manager']);
        $nonManagerUser = User::factory()->create(['role' => 'employee']);

        $this->assertTrue($managerUser->isManager());
        $this->assertFalse($nonManagerUser->isManager());
    }

    /** @test */
    public function it_checks_if_user_is_employee()
    {
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $nonEmployeeUser = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($employeeUser->isEmployee());
        $this->assertFalse($nonEmployeeUser->isEmployee());
    }

    /** @test */
    public function it_checks_if_user_is_active()
    {
        $activeUser = User::factory()->create(['status' => 'active']);
        $inactiveUser = User::factory()->create(['status' => 'inactive']);

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
    }

    /** @test */
    public function it_returns_profile_image_url()
    {
        $userWithImage = User::factory()->create(['profile_image_path' => 'profiles/test.jpg']);
        $userWithoutImage = User::factory()->create(['profile_image_path' => null]);

        $this->assertEquals(asset('storage/profiles/test.jpg'), $userWithImage->profile_image_url);
        $this->assertNull($userWithoutImage->profile_image_url);
    }

    /** @test */
    public function it_updates_last_login_information()
    {
        $ip = '192.168.1.1';
        $originalLoginAt = $this->user->last_login_at;
        $originalLoginIp = $this->user->last_login_ip;

        $this->user->updateLastLogin($ip);

        $this->user->refresh();
        $this->assertNotEquals($originalLoginAt, $this->user->last_login_at);
        $this->assertEquals($ip, $this->user->last_login_ip);
        $this->assertNotNull($this->user->last_login_at);
    }

    /** @test */
    public function it_updates_last_login_with_request_ip_when_no_ip_provided()
    {
        // Mock request IP
        request()->merge(['REMOTE_ADDR' => '127.0.0.1']);
        
        $this->user->updateLastLogin();

        $this->user->refresh();
        $this->assertNotNull($this->user->last_login_at);
        $this->assertNotNull($this->user->last_login_ip);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $this->user->delete();

        $this->assertSoftDeleted($this->user);
        $this->assertNotNull($this->user->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete()
    {
        $this->user->delete();
        $this->user->restore();

        $this->assertNull($this->user->deleted_at);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'deleted_at' => null]);
    }
} 