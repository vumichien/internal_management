<?php

namespace Tests\Feature;

use App\Contracts\SocialAuthProviderInterface;
use App\Models\User;
use App\Services\Auth\GoogleAuthProvider;
use App\Services\Auth\GitHubAuthProvider;
use App\Services\Auth\SocialAuthProviderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Tests\TestCase;

class ExtensibleAuthArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_google_provider()
    {
        $provider = SocialAuthProviderFactory::make('google');
        
        $this->assertInstanceOf(GoogleAuthProvider::class, $provider);
        $this->assertEquals('google', $provider->getName());
        $this->assertEquals('Google', $provider->getDisplayName());
    }

    public function test_factory_can_create_github_provider()
    {
        $provider = SocialAuthProviderFactory::make('github');
        
        $this->assertInstanceOf(GitHubAuthProvider::class, $provider);
        $this->assertEquals('github', $provider->getName());
        $this->assertEquals('GitHub', $provider->getDisplayName());
    }

    public function test_factory_throws_exception_for_unknown_provider()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown social auth provider: unknown');
        
        SocialAuthProviderFactory::make('unknown');
    }

    public function test_factory_returns_available_providers()
    {
        $providers = SocialAuthProviderFactory::getAvailableProviders();
        
        $this->assertIsArray($providers);
        $this->assertContains('google', $providers);
        $this->assertContains('github', $providers);
    }

    public function test_factory_can_register_new_provider()
    {
        // Create a mock provider class
        $mockProviderClass = new class extends \App\Services\Auth\AbstractSocialAuthProvider {
            public function __construct()
            {
                parent::__construct('mock', 'Mock Provider');
            }

            protected function findUserBySocialId(string $socialId): ?User
            {
                return null;
            }

            protected function linkSocialAccount(User $user, SocialiteUser $socialUser): void
            {
                // Mock implementation
            }

            protected function createUserFromSocialData(SocialiteUser $socialUser): User
            {
                return User::factory()->create();
            }

            protected function updateUserFromSocialData(User $user, SocialiteUser $socialUser): void
            {
                // Mock implementation
            }
        };

        SocialAuthProviderFactory::registerProvider('mock', get_class($mockProviderClass));
        
        $this->assertTrue(SocialAuthProviderFactory::hasProvider('mock'));
        
        $provider = SocialAuthProviderFactory::make('mock');
        $this->assertEquals('mock', $provider->getName());
        $this->assertEquals('Mock Provider', $provider->getDisplayName());
    }

    public function test_factory_validates_provider_interface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider class must implement SocialAuthProviderInterface');
        
        SocialAuthProviderFactory::registerProvider('invalid', \stdClass::class);
    }

    public function test_provider_status_includes_all_providers()
    {
        $status = SocialAuthProviderFactory::getProviderStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('google', $status);
        $this->assertArrayHasKey('github', $status);
        
        foreach ($status as $providerName => $providerStatus) {
            $this->assertArrayHasKey('name', $providerStatus);
            $this->assertArrayHasKey('display_name', $providerStatus);
            $this->assertArrayHasKey('enabled', $providerStatus);
            $this->assertArrayHasKey('configured', $providerStatus);
            $this->assertArrayHasKey('required_config', $providerStatus);
            $this->assertArrayHasKey('optional_config', $providerStatus);
        }
    }

    public function test_google_provider_has_correct_configuration()
    {
        $provider = new GoogleAuthProvider();
        
        $this->assertEquals(['client_id', 'client_secret', 'redirect'], $provider->getRequiredConfigKeys());
        $this->assertEquals(['enabled'], $provider->getOptionalConfigKeys());
    }

    public function test_github_provider_has_correct_configuration()
    {
        $provider = new GitHubAuthProvider();
        
        $this->assertEquals(['client_id', 'client_secret', 'redirect'], $provider->getRequiredConfigKeys());
        $this->assertEquals(['enabled'], $provider->getOptionalConfigKeys());
    }

    public function test_provider_validation_fails_without_required_config()
    {
        $provider = new GoogleAuthProvider();
        
        // Without proper configuration, validation should fail
        $this->assertFalse($provider->validateConfig());
    }

    public function test_provider_is_disabled_without_configuration()
    {
        $provider = new GoogleAuthProvider();
        
        // Without proper configuration, provider should be disabled
        $this->assertFalse($provider->isEnabled());
    }

    public function test_factory_caches_provider_instances()
    {
        $provider1 = SocialAuthProviderFactory::make('google');
        $provider2 = SocialAuthProviderFactory::make('google');
        
        $this->assertSame($provider1, $provider2);
    }

    public function test_factory_can_clear_cache()
    {
        $provider1 = SocialAuthProviderFactory::make('google');
        
        SocialAuthProviderFactory::clearCache();
        
        $provider2 = SocialAuthProviderFactory::make('google');
        
        $this->assertNotSame($provider1, $provider2);
    }

    public function test_enabled_providers_only_returns_configured_providers()
    {
        // Without proper configuration, no providers should be enabled
        $enabledProviders = SocialAuthProviderFactory::getEnabledProviders();
        
        $this->assertIsArray($enabledProviders);
        // Should be empty since we don't have proper config in test environment
        $this->assertEmpty($enabledProviders);
    }

    public function test_provider_interface_methods_exist()
    {
        $provider = new GoogleAuthProvider();
        
        $this->assertTrue(method_exists($provider, 'getName'));
        $this->assertTrue(method_exists($provider, 'getDisplayName'));
        $this->assertTrue(method_exists($provider, 'isEnabled'));
        $this->assertTrue(method_exists($provider, 'getRedirectUrl'));
        $this->assertTrue(method_exists($provider, 'handleCallback'));
        $this->assertTrue(method_exists($provider, 'findOrCreateUser'));
        $this->assertTrue(method_exists($provider, 'getConfig'));
        $this->assertTrue(method_exists($provider, 'validateConfig'));
        $this->assertTrue(method_exists($provider, 'getRequiredConfigKeys'));
        $this->assertTrue(method_exists($provider, 'getOptionalConfigKeys'));
    }

    public function test_abstract_provider_implements_interface()
    {
        $provider = new GoogleAuthProvider();
        
        $this->assertInstanceOf(SocialAuthProviderInterface::class, $provider);
    }

    public function test_architecture_supports_multiple_providers()
    {
        $availableProviders = SocialAuthProviderFactory::getAvailableProviders();
        
        $this->assertGreaterThanOrEqual(2, count($availableProviders));
        $this->assertContains('google', $availableProviders);
        $this->assertContains('github', $availableProviders);
    }
} 