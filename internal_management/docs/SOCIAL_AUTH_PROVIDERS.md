# Social Authentication Providers

This comprehensive guide explains how to add new social authentication providers to the Laravel Internal Management System.

## Architecture Overview

The social authentication system uses an extensible architecture that allows easy addition of new providers without modifying existing code. The architecture consists of:

1. **SocialAuthProviderInterface** - Contract that all providers must implement
2. **AbstractSocialAuthProvider** - Base class providing common functionality
3. **SocialAuthProviderFactory** - Factory for creating and managing provider instances
4. **Provider-specific classes** - Individual implementations for each provider (Google, GitHub, etc.)
5. **SocialiteController** - Controller that uses the factory to handle authentication

## Adding a New Provider

### Step 1: Create the Provider Class

Create a new provider class that extends `AbstractSocialAuthProvider`:

```php
<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class LinkedInAuthProvider extends AbstractSocialAuthProvider
{
    public function __construct()
    {
        parent::__construct('linkedin', 'LinkedIn');
    }

    protected function findUserBySocialId(string $socialId): ?User
    {
        return User::where('linkedin_id', $socialId)->first();
    }

    protected function linkSocialAccount(User $user, SocialiteUser $socialUser): void
    {
        $user->update([
            'linkedin_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        Log::info('Linked LinkedIn account to existing user', [
            'user_id' => $user->id,
            'linkedin_id' => $socialUser->getId(),
            'email' => $user->email,
        ]);
    }

    protected function createUserFromSocialData(SocialiteUser $socialUser): User
    {
        $user = User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'linkedin_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'password' => null, // Social users don't have passwords initially
            'role' => 'employee',
            'status' => 'active',
            'is_verified' => true, // LinkedIn accounts are pre-verified
            'email_verified_at' => now(),
        ]);

        Log::info('Created new user from LinkedIn authentication', [
            'user_id' => $user->id,
            'linkedin_id' => $socialUser->getId(),
            'email' => $user->email,
            'name' => $user->name,
        ]);

        return $user;
    }

    protected function updateUserFromSocialData(User $user, SocialiteUser $socialUser): void
    {
        $updates = [];

        // Update name if it has changed
        if ($user->name !== $socialUser->getName()) {
            $updates['name'] = $socialUser->getName();
        }

        // Update avatar if it has changed
        if ($user->avatar !== $socialUser->getAvatar()) {
            $updates['avatar'] = $socialUser->getAvatar();
        }

        // Update email if it has changed (rare but possible)
        if ($user->email !== $socialUser->getEmail()) {
            $updates['email'] = $socialUser->getEmail();
            $updates['email_verified_at'] = now();
        }

        if (!empty($updates)) {
            $user->update($updates);
            
            Log::info('Updated user data from LinkedIn', [
                'user_id' => $user->id,
                'linkedin_id' => $socialUser->getId(),
                'updates' => array_keys($updates),
            ]);
        }
    }

    public function getRequiredConfigKeys(): array
    {
        return ['client_id', 'client_secret', 'redirect'];
    }

    public function getOptionalConfigKeys(): array
    {
        return ['enabled'];
    }
}
```

### Step 2: Add Database Column

Create a migration to add the provider ID column to the users table:

```bash
php artisan make:migration add_linkedin_id_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('linkedin_id')->nullable()->unique()->after('github_id');
            $table->index('linkedin_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['linkedin_id']);
            $table->dropColumn('linkedin_id');
        });
    }
};
```

### Step 3: Update User Model

Add the new column to the User model's fillable array:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'google_id',
    'github_id',
    'linkedin_id', // Add this line
    'avatar',
    'role',
    'status',
    'is_verified',
    'email_verified_at',
];
```

### Step 4: Register the Provider

Register the new provider in the `SocialAuthProviderFactory`:

```php
// In SocialAuthProviderFactory.php
private static array $providers = [
    'google' => GoogleAuthProvider::class,
    'github' => GitHubAuthProvider::class,
    'linkedin' => LinkedInAuthProvider::class, // Add this line
];
```

### Step 5: Configure Services

Add the provider configuration to `config/services.php`:

```php
'linkedin' => [
    'client_id' => env('LINKEDIN_CLIENT_ID'),
    'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
    'redirect' => env('APP_URL') . '/auth/linkedin/callback',
    'enabled' => env('LINKEDIN_AUTH_ENABLED', false),
],
```

### Step 6: Install Socialite Provider (if needed)

If the provider is not natively supported by Laravel Socialite, install the appropriate Socialite provider package:

```bash
composer require socialiteproviders/linkedin
```

Then add the provider to your `config/app.php`:

```php
'providers' => [
    // Other providers...
    \SocialiteProviders\LinkedIn\LinkedInExtendSocialite::class,
],
```

### Step 7: Add Environment Variables

Add the required environment variables to your `.env` file:

```env
LINKEDIN_CLIENT_ID=your_linkedin_client_id
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret
LINKEDIN_AUTH_ENABLED=true
```

### Step 8: Update Routes (Optional)

The existing routes in `routes/web.php` should work automatically:

```php
Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->name('socialite.redirect')
    ->where('provider', 'google|github|linkedin');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->name('socialite.callback')
    ->where('provider', 'google|github|linkedin');
```

### Step 9: Update UI (Optional)

Add the provider button to your login form:

```blade
<!-- In resources/views/auth/login.blade.php -->
@if(config('services.linkedin.enabled'))
    <a href="{{ route('socialite.redirect', 'linkedin') }}" 
       class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
            <!-- LinkedIn icon SVG -->
        </svg>
        Continue with LinkedIn
    </a>
@endif
```

## Provider Interface Methods

When creating a new provider, you must implement these abstract methods:

### Required Abstract Methods

#### `findUserBySocialId(string $socialId): ?User`
Find an existing user by their social provider ID.

#### `linkSocialAccount(User $user, SocialiteUser $socialUser): void`
Link a social account to an existing user account.

#### `createUserFromSocialData(SocialiteUser $socialUser): User`
Create a new user from social provider data.

#### `updateUserFromSocialData(User $user, SocialiteUser $socialUser): void`
Update existing user data with fresh information from the social provider.

### Configuration Methods

You can override these methods to customize configuration requirements:

#### `getRequiredConfigKeys(): array`
Return an array of required configuration keys for the provider.

#### `getOptionalConfigKeys(): array`
Return an array of optional configuration keys for the provider.

## Available Interface Methods

### SocialAuthProviderInterface Methods

- `getName()` - Get the provider name (e.g., 'linkedin')
- `getDisplayName()` - Get the human-readable name (e.g., 'LinkedIn')
- `isEnabled()` - Check if the provider is enabled
- `getRedirectUrl()` - Get the OAuth redirect URL
- `handleCallback()` - Handle the OAuth callback
- `findOrCreateUser()` - Find or create user from social data
- `getConfig()` - Get provider configuration
- `validateConfig()` - Validate provider configuration
- `getRequiredConfigKeys()` - Get required config keys
- `getOptionalConfigKeys()` - Get optional config keys

### Factory Methods

- `SocialAuthProviderFactory::make($provider)` - Create provider instance
- `SocialAuthProviderFactory::getAvailableProviders()` - Get all provider names
- `SocialAuthProviderFactory::getEnabledProviders()` - Get enabled providers
- `SocialAuthProviderFactory::hasProvider($provider)` - Check if provider exists
- `SocialAuthProviderFactory::isProviderEnabled($provider)` - Check if provider is enabled
- `SocialAuthProviderFactory::registerProvider($name, $class)` - Register new provider
- `SocialAuthProviderFactory::getProviderStatus()` - Get status of all providers
- `SocialAuthProviderFactory::clearCache()` - Clear cached instances

## Dynamic Provider Registration

You can also register providers dynamically at runtime:

```php
SocialAuthProviderFactory::registerProvider('custom', CustomAuthProvider::class);
```

This is useful for plugins or modules that need to add authentication providers.

## Testing Your Provider

Create comprehensive tests for your new provider:

```php
<?php

namespace Tests\Unit;

use App\Services\Auth\LinkedInAuthProvider;
use App\Services\Auth\SocialAuthProviderFactory;
use Tests\TestCase;

class LinkedInAuthProviderTest extends TestCase
{
    /** @test */
    public function it_has_correct_provider_details()
    {
        $provider = new LinkedInAuthProvider();
        
        $this->assertEquals('linkedin', $provider->getName());
        $this->assertEquals('LinkedIn', $provider->getDisplayName());
    }

    /** @test */
    public function it_has_required_config_keys()
    {
        $provider = new LinkedInAuthProvider();
        $requiredKeys = $provider->getRequiredConfigKeys();
        
        $this->assertContains('client_id', $requiredKeys);
        $this->assertContains('client_secret', $requiredKeys);
        $this->assertContains('redirect', $requiredKeys);
    }

    /** @test */
    public function it_can_be_created_via_factory()
    {
        $provider = SocialAuthProviderFactory::make('linkedin');
        
        $this->assertInstanceOf(LinkedInAuthProvider::class, $provider);
    }

    // Add more tests as needed...
}
```

## Provider Configuration

### Required Configuration Keys

All providers must have these configuration keys:
- `client_id` - OAuth application client ID
- `client_secret` - OAuth application client secret
- `redirect` - OAuth callback URL

### Optional Configuration Keys

- `enabled` - Whether the provider is enabled (default: false)
- Any provider-specific configuration

## Security Considerations

1. **Environment Variables**: Store sensitive credentials in environment variables, never in code
2. **HTTPS**: Always use HTTPS for OAuth redirects in production
3. **Validation**: Validate all data received from social providers
4. **Logging**: Log authentication events for security monitoring
5. **Rate Limiting**: Consider implementing rate limiting for authentication attempts
6. **State Parameter**: Laravel Socialite handles CSRF protection automatically
7. **Token Storage**: Never store access tokens in the database unless absolutely necessary
8. **User Verification**: Consider email verification requirements for social accounts

## Best Practices

1. **Consistent Naming**: Use consistent naming conventions for provider classes and database columns
2. **Error Handling**: Implement comprehensive error handling in your provider methods
3. **Logging**: Add appropriate logging for debugging and security monitoring
4. **Testing**: Write comprehensive tests for your provider implementation
5. **Documentation**: Document any provider-specific configuration or behavior
6. **Data Validation**: Validate social user data before creating/updating users
7. **Security**: Never store sensitive provider data in the database

## Troubleshooting

### Common Issues

1. **Provider not found**: Ensure the provider is registered in the factory
2. **Configuration errors**: Check that all required config keys are set
3. **Socialite driver not found**: Install the appropriate Socialite provider package
4. **Database errors**: Ensure the provider ID column exists in the users table
5. **Authentication failures**: Check Laravel logs for detailed error messages
6. **Callback URL mismatch**: Verify the redirect URI matches your application's callback URL

### Debugging

Enable debug logging to troubleshoot issues:

```php
Log::debug('Provider configuration', [
    'provider' => $providerName,
    'config' => $provider->getConfig(),
    'enabled' => $provider->isEnabled(),
    'configured' => $provider->validateConfig(),
]);
```

### Provider Status

Check the status of all providers:

```php
$status = SocialAuthProviderFactory::getProviderStatus();
```

This returns information about each provider's configuration and availability.

## Example Providers

The system includes these example providers:

- **GoogleAuthProvider**: Google OAuth integration
- **GitHubAuthProvider**: GitHub OAuth integration

Use these as references when implementing new providers.

## Advanced Features

### Custom User Creation Logic

You can customize user creation logic by overriding the `createUserFromSocialData` method:

```php
protected function createUserFromSocialData(SocialiteUser $socialUser): User
{
    // Custom logic for your provider
    $userData = [
        'name' => $this->extractName($socialUser),
        'email' => $socialUser->getEmail(),
        'linkedin_id' => $socialUser->getId(),
        'avatar' => $socialUser->getAvatar(),
        'role' => $this->determineUserRole($socialUser),
        // ... other fields
    ];

    return User::create($userData);
}
```

### Provider-Specific Configuration

Add provider-specific configuration options:

```php
public function getOptionalConfigKeys(): array
{
    return [
        'enabled',
        'auto_verify_email',
        'default_role',
        'allowed_domains',
    ];
}
```

This comprehensive guide should help you successfully add and manage social authentication providers in your Laravel application. 