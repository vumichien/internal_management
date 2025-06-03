<?php

namespace App\Services\Auth;

use App\Contracts\SocialAuthProviderInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

abstract class AbstractSocialAuthProvider implements SocialAuthProviderInterface
{
    protected string $name;
    protected string $displayName;
    protected array $config;

    public function __construct(string $name, string $displayName, array $config = [])
    {
        $this->name = $name;
        $this->displayName = $displayName;
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function isEnabled(): bool
    {
        return $this->validateConfig() && 
               config("services.{$this->name}.enabled", false);
    }

    public function getRedirectUrl(): string
    {
        if (!$this->isEnabled()) {
            throw new \Exception("Provider {$this->name} is not enabled");
        }

        Log::info("Redirecting to {$this->name} for authentication");
        
        return Socialite::driver($this->name)->redirect()->getTargetUrl();
    }

    public function handleCallback(): SocialiteUser
    {
        if (!$this->isEnabled()) {
            throw new \Exception("Provider {$this->name} is not enabled");
        }

        Log::info("Handling {$this->name} callback");
        
        return Socialite::driver($this->name)->user();
    }

    public function findOrCreateUser(SocialiteUser $socialUser): User
    {
        Log::info("Finding or creating user from {$this->name}", [
            'social_id' => $socialUser->getId(),
            'email' => $socialUser->getEmail(),
        ]);

        // Try to find existing user by social ID
        $user = $this->findUserBySocialId($socialUser->getId());
        
        if ($user) {
            $this->updateUserFromSocialData($user, $socialUser);
            return $user;
        }

        // Try to find existing user by email
        $user = User::where('email', $socialUser->getEmail())->first();
        
        if ($user) {
            $this->linkSocialAccount($user, $socialUser);
            return $user;
        }

        // Create new user
        return $this->createUserFromSocialData($socialUser);
    }

    public function getConfig(): array
    {
        return array_merge(
            config("services.{$this->name}", []),
            $this->config
        );
    }

    public function validateConfig(): bool
    {
        $config = $this->getConfig();
        $requiredKeys = $this->getRequiredConfigKeys();

        foreach ($requiredKeys as $key) {
            if (empty($config[$key])) {
                Log::warning("Missing required config key for {$this->name}: {$key}");
                return false;
            }
        }

        return true;
    }

    public function getRequiredConfigKeys(): array
    {
        return ['client_id', 'client_secret'];
    }

    public function getOptionalConfigKeys(): array
    {
        return ['redirect', 'enabled'];
    }

    /**
     * Find user by social ID (provider-specific implementation)
     */
    abstract protected function findUserBySocialId(string $socialId): ?User;

    /**
     * Link social account to existing user (provider-specific implementation)
     */
    abstract protected function linkSocialAccount(User $user, SocialiteUser $socialUser): void;

    /**
     * Create new user from social data (provider-specific implementation)
     */
    abstract protected function createUserFromSocialData(SocialiteUser $socialUser): User;

    /**
     * Update existing user with fresh social data (provider-specific implementation)
     */
    abstract protected function updateUserFromSocialData(User $user, SocialiteUser $socialUser): void;
} 