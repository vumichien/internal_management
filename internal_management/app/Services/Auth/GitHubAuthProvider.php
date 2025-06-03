<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GitHubAuthProvider extends AbstractSocialAuthProvider
{
    public function __construct()
    {
        parent::__construct('github', 'GitHub');
    }

    protected function findUserBySocialId(string $socialId): ?User
    {
        return User::where('github_id', $socialId)->first();
    }

    protected function linkSocialAccount(User $user, SocialiteUser $socialUser): void
    {
        $user->update([
            'github_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        Log::info('Linked GitHub account to existing user', [
            'user_id' => $user->id,
            'github_id' => $socialUser->getId(),
            'email' => $user->email,
        ]);
    }

    protected function createUserFromSocialData(SocialiteUser $socialUser): User
    {
        $user = User::create([
            'name' => $socialUser->getName() ?: $socialUser->getNickname(),
            'email' => $socialUser->getEmail(),
            'github_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'password' => null, // Social users don't have passwords initially
            'role' => 'employee',
            'status' => 'active',
            'is_verified' => true, // GitHub accounts are pre-verified
            'email_verified_at' => now(),
        ]);

        Log::info('Created new user from GitHub authentication', [
            'user_id' => $user->id,
            'github_id' => $socialUser->getId(),
            'email' => $user->email,
            'name' => $user->name,
        ]);

        return $user;
    }

    protected function updateUserFromSocialData(User $user, SocialiteUser $socialUser): void
    {
        $updates = [];

        // Update name if it has changed
        $newName = $socialUser->getName() ?: $socialUser->getNickname();
        if ($user->name !== $newName) {
            $updates['name'] = $newName;
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
            
            Log::info('Updated user data from GitHub', [
                'user_id' => $user->id,
                'github_id' => $socialUser->getId(),
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