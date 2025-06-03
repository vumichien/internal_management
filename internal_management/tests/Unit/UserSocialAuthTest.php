<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_social_provider_returns_true_when_provider_id_exists()
    {
        $user = User::factory()->create([
            'google_id' => '123456789',
            'github_id' => null,
        ]);

        $this->assertTrue($user->hasSocialProvider('google'));
        $this->assertFalse($user->hasSocialProvider('github'));
    }

    public function test_has_google_auth_returns_correct_status()
    {
        $userWithGoogle = User::factory()->create(['google_id' => '123456789']);
        $userWithoutGoogle = User::factory()->create(['google_id' => null]);

        $this->assertTrue($userWithGoogle->hasGoogleAuth());
        $this->assertFalse($userWithoutGoogle->hasGoogleAuth());
    }

    public function test_has_github_auth_returns_correct_status()
    {
        $userWithGithub = User::factory()->create(['github_id' => '987654321']);
        $userWithoutGithub = User::factory()->create(['github_id' => null]);

        $this->assertTrue($userWithGithub->hasGithubAuth());
        $this->assertFalse($userWithoutGithub->hasGithubAuth());
    }

    public function test_linked_providers_attribute_returns_correct_array()
    {
        $userWithBoth = User::factory()->create([
            'google_id' => '123456789',
            'github_id' => '987654321',
        ]);

        $userWithGoogle = User::factory()->create([
            'google_id' => '123456789',
            'github_id' => null,
        ]);

        $userWithNone = User::factory()->create([
            'google_id' => null,
            'github_id' => null,
        ]);

        $this->assertEquals(['google', 'github'], $userWithBoth->linked_providers);
        $this->assertEquals(['google'], $userWithGoogle->linked_providers);
        $this->assertEquals([], $userWithNone->linked_providers);
    }

    public function test_can_login_with_password_returns_correct_status()
    {
        $userWithPassword = User::factory()->create(['password' => Hash::make('password')]);
        $userWithoutPassword = User::factory()->create(['password' => null]);

        $this->assertTrue($userWithPassword->canLoginWithPassword());
        $this->assertFalse($userWithoutPassword->canLoginWithPassword());
    }

    public function test_is_social_only_returns_correct_status()
    {
        $socialOnlyUser = User::factory()->create([
            'password' => null,
            'google_id' => '123456789',
        ]);

        $traditionalUser = User::factory()->create([
            'password' => Hash::make('password'),
            'google_id' => null,
        ]);

        $hybridUser = User::factory()->create([
            'password' => Hash::make('password'),
            'google_id' => '123456789',
        ]);

        $this->assertTrue($socialOnlyUser->isSocialOnly());
        $this->assertFalse($traditionalUser->isSocialOnly());
        $this->assertFalse($hybridUser->isSocialOnly());
    }

    public function test_link_social_provider_updates_user()
    {
        $user = User::factory()->create(['google_id' => null]);

        $result = $user->linkSocialProvider('google', '123456789');

        $this->assertTrue($result);
        $this->assertEquals('123456789', $user->fresh()->google_id);
    }

    public function test_link_social_provider_fails_for_invalid_provider()
    {
        $user = User::factory()->create();

        $result = $user->linkSocialProvider('invalid', '123456789');

        $this->assertFalse($result);
    }

    public function test_unlink_social_provider_removes_provider_id()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'google_id' => '123456789',
        ]);

        $result = $user->unlinkSocialProvider('google');

        $this->assertTrue($result);
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_unlink_social_provider_fails_for_social_only_user_with_one_provider()
    {
        $user = User::factory()->create([
            'password' => null,
            'google_id' => '123456789',
            'github_id' => null,
        ]);

        $result = $user->unlinkSocialProvider('google');

        $this->assertFalse($result);
        $this->assertEquals('123456789', $user->fresh()->google_id);
    }

    public function test_unlink_social_provider_succeeds_for_social_only_user_with_multiple_providers()
    {
        $user = User::factory()->create([
            'password' => null,
            'google_id' => '123456789',
            'github_id' => '987654321',
        ]);

        $result = $user->unlinkSocialProvider('google');

        $this->assertTrue($result);
        $this->assertNull($user->fresh()->google_id);
        $this->assertEquals('987654321', $user->fresh()->github_id);
    }

    public function test_find_by_social_provider_returns_correct_user()
    {
        $user = User::factory()->create(['google_id' => '123456789']);
        User::factory()->create(['google_id' => '987654321']);

        $foundUser = User::findBySocialProvider('google', '123456789');

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    public function test_find_by_social_provider_returns_null_when_not_found()
    {
        User::factory()->create(['google_id' => '123456789']);

        $foundUser = User::findBySocialProvider('google', 'nonexistent');

        $this->assertNull($foundUser);
    }

    public function test_create_from_social_provider_creates_user_with_correct_data()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('random'),
        ];

        $user = User::createFromSocialProvider($userData, 'google', '123456789');

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'google_id' => '123456789',
            'role' => 'employee',
            'status' => 'active',
            'is_verified' => true,
        ]);

        $this->assertNotNull($user->email_verified_at);
    }

    public function test_create_from_social_provider_respects_provided_role_and_status()
    {
        $userData = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('random'),
            'role' => 'admin',
            'status' => 'inactive',
        ];

        $user = User::createFromSocialProvider($userData, 'google', '123456789');

        $this->assertDatabaseHas('users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'google_id' => '123456789',
            'role' => 'admin',
            'status' => 'inactive',
            'is_verified' => true,
        ]);
    }
}
