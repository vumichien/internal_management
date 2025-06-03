<?php

namespace Tests\Unit;

use App\Contracts\SocialAuthProviderInterface;
use App\Services\Auth\SocialAuthProviderFactory;
use App\Services\Auth\GoogleAuthProvider;
use App\Services\Auth\GitHubAuthProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAuthProviderFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any cached instances before each test
        SocialAuthProviderFactory::clearCache();
    }

    /** @test */
    public function it_can_create_google_provider_instance()
    {
        $provider = SocialAuthProviderFactory::make('google');
        
        $this->assertInstanceOf(GoogleAuthProvider::class, $provider);
        $this->assertInstanceOf(SocialAuthProviderInterface::class, $provider);
        $this->assertEquals('google', $provider->getName());
        $this->assertEquals('Google', $provider->getDisplayName());
    }

    /** @test */
    public function it_can_create_github_provider_instance()
    {
        $provider = SocialAuthProviderFactory::make('github');
        
        $this->assertInstanceOf(GitHubAuthProvider::class, $provider);
        $this->assertInstanceOf(SocialAuthProviderInterface::class, $provider);
        $this->assertEquals('github', $provider->getName());
        $this->assertEquals('GitHub', $provider->getDisplayName());
    }

    /** @test */
    public function it_throws_exception_for_unknown_provider()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown social auth provider: unknown');
        
        SocialAuthProviderFactory::make('unknown');
    }

    /** @test */
    public function it_caches_provider_instances()
    {
        $provider1 = SocialAuthProviderFactory::make('google');
        $provider2 = SocialAuthProviderFactory::make('google');
        
        $this->assertSame($provider1, $provider2);
    }

    /** @test */
    public function it_returns_available_providers()
    {
        $providers = SocialAuthProviderFactory::getAvailableProviders();
        
        $this->assertIsArray($providers);
        $this->assertContains('google', $providers);
        $this->assertContains('github', $providers);
    }

    /** @test */
    public function it_checks_if_provider_exists()
    {
        $this->assertTrue(SocialAuthProviderFactory::hasProvider('google'));
        $this->assertTrue(SocialAuthProviderFactory::hasProvider('github'));
        $this->assertFalse(SocialAuthProviderFactory::hasProvider('unknown'));
    }

    /** @test */
    public function it_can_register_new_provider()
    {
        // Create a mock provider class
        $mockProviderClass = new class extends \App\Services\Auth\AbstractSocialAuthProvider {
            public function __construct()
            {
                parent::__construct('mock', 'Mock Provider');
            }

            protected function findUserBySocialId(string $socialId): ?\App\Models\User
            {
                return null;
            }

            protected function linkSocialAccount(\App\Models\User $user, \Laravel\Socialite\Contracts\User $socialUser): void
            {
                // Mock implementation
            }

            protected function createUserFromSocialData(\Laravel\Socialite\Contracts\User $socialUser): \App\Models\User
            {
                return new \App\Models\User();
            }

            protected function updateUserFromSocialData(\App\Models\User $user, \Laravel\Socialite\Contracts\User $socialUser): void
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

    /** @test */
    public function it_throws_exception_when_registering_invalid_provider_class()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider class must implement SocialAuthProviderInterface');
        
        SocialAuthProviderFactory::registerProvider('invalid', \stdClass::class);
    }

    /** @test */
    public function it_returns_enabled_providers_only()
    {
        // Mock configuration to enable Google and disable GitHub
        config([
            'services.google.enabled' => true,
            'services.google.client_id' => 'test_google_client_id',
            'services.google.client_secret' => 'test_google_client_secret',
            'services.github.enabled' => false,
            'services.github.client_id' => 'test_github_client_id',
            'services.github.client_secret' => 'test_github_client_secret',
        ]);
        
        $enabledProviders = SocialAuthProviderFactory::getEnabledProviders();
        
        $this->assertArrayHasKey('google', $enabledProviders);
        $this->assertArrayNotHasKey('github', $enabledProviders);
    }

    /** @test */
    public function it_checks_provider_enabled_status()
    {
        // Mock configuration
        config([
            'services.google.enabled' => true,
            'services.google.client_id' => 'test_google_client_id',
            'services.google.client_secret' => 'test_google_client_secret',
            'services.github.enabled' => false,
            'services.github.client_id' => 'test_github_client_id',
            'services.github.client_secret' => 'test_github_client_secret',
        ]);
        
        $this->assertTrue(SocialAuthProviderFactory::isProviderEnabled('google'));
        $this->assertFalse(SocialAuthProviderFactory::isProviderEnabled('github'));
        $this->assertFalse(SocialAuthProviderFactory::isProviderEnabled('unknown'));
    }

    /** @test */
    public function it_returns_provider_status_information()
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

    /** @test */
    public function it_clears_cached_instances()
    {
        // Create an instance to cache it
        $provider1 = SocialAuthProviderFactory::make('google');
        
        // Clear cache
        SocialAuthProviderFactory::clearCache();
        
        // Create another instance - should be a new instance
        $provider2 = SocialAuthProviderFactory::make('google');
        
        // They should be different instances (not cached)
        $this->assertNotSame($provider1, $provider2);
    }
} 