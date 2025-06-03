<?php

namespace App\Contracts;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

interface SocialAuthProviderInterface
{
    /**
     * Get the provider name
     */
    public function getName(): string;

    /**
     * Get the provider display name
     */
    public function getDisplayName(): string;

    /**
     * Check if the provider is enabled
     */
    public function isEnabled(): bool;

    /**
     * Get the redirect URL for the provider
     */
    public function getRedirectUrl(): string;

    /**
     * Handle the callback from the provider
     */
    public function handleCallback(): SocialiteUser;

    /**
     * Find or create a user from the social provider data
     */
    public function findOrCreateUser(SocialiteUser $socialUser): User;

    /**
     * Get the configuration for this provider
     */
    public function getConfig(): array;

    /**
     * Validate the provider configuration
     */
    public function validateConfig(): bool;

    /**
     * Get the required configuration keys for this provider
     */
    public function getRequiredConfigKeys(): array;

    /**
     * Get the optional configuration keys for this provider
     */
    public function getOptionalConfigKeys(): array;
} 