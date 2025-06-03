<?php

namespace App\Services\Auth;

use App\Contracts\SocialAuthProviderInterface;
use Illuminate\Support\Facades\Log;

class SocialAuthProviderFactory
{
    /**
     * Available provider classes
     */
    private static array $providers = [
        'google' => GoogleAuthProvider::class,
        'github' => GitHubAuthProvider::class,
    ];

    /**
     * Cached provider instances
     */
    private static array $instances = [];

    /**
     * Get a provider instance by name
     */
    public static function make(string $providerName): SocialAuthProviderInterface
    {
        if (!isset(self::$providers[$providerName])) {
            throw new \InvalidArgumentException("Unknown social auth provider: {$providerName}");
        }

        if (!isset(self::$instances[$providerName])) {
            $providerClass = self::$providers[$providerName];
            self::$instances[$providerName] = new $providerClass();
            
            Log::debug("Created new instance of {$providerName} provider");
        }

        return self::$instances[$providerName];
    }

    /**
     * Get all available provider names
     */
    public static function getAvailableProviders(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * Get all enabled provider instances
     */
    public static function getEnabledProviders(): array
    {
        $enabledProviders = [];

        foreach (self::getAvailableProviders() as $providerName) {
            $provider = self::make($providerName);
            
            if ($provider->isEnabled()) {
                $enabledProviders[$providerName] = $provider;
            }
        }

        return $enabledProviders;
    }

    /**
     * Check if a provider is available
     */
    public static function hasProvider(string $providerName): bool
    {
        return isset(self::$providers[$providerName]);
    }

    /**
     * Check if a provider is enabled
     */
    public static function isProviderEnabled(string $providerName): bool
    {
        if (!self::hasProvider($providerName)) {
            return false;
        }

        return self::make($providerName)->isEnabled();
    }

    /**
     * Register a new provider class
     */
    public static function registerProvider(string $name, string $providerClass): void
    {
        if (!is_subclass_of($providerClass, SocialAuthProviderInterface::class)) {
            throw new \InvalidArgumentException(
                "Provider class must implement SocialAuthProviderInterface"
            );
        }

        self::$providers[$name] = $providerClass;
        
        // Clear cached instance if it exists
        unset(self::$instances[$name]);
        
        Log::info("Registered new social auth provider: {$name}");
    }

    /**
     * Get provider configuration status
     */
    public static function getProviderStatus(): array
    {
        $status = [];

        foreach (self::getAvailableProviders() as $providerName) {
            $provider = self::make($providerName);
            
            $status[$providerName] = [
                'name' => $provider->getName(),
                'display_name' => $provider->getDisplayName(),
                'enabled' => $provider->isEnabled(),
                'configured' => $provider->validateConfig(),
                'required_config' => $provider->getRequiredConfigKeys(),
                'optional_config' => $provider->getOptionalConfigKeys(),
            ];
        }

        return $status;
    }

    /**
     * Clear all cached provider instances
     */
    public static function clearCache(): void
    {
        self::$instances = [];
        Log::debug("Cleared social auth provider cache");
    }
} 