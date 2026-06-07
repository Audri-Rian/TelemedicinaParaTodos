<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public const RESULT_COMPLETE_PROFILE = 'complete_profile';

    public const RESULT_TWO_FACTOR = 'two_factor';

    public const RESULT_LOGIN = 'login';

    public const RESULT_DOCTOR_BLOCKED = 'doctor_blocked';

    public const RESULT_EMAIL_EXISTS = 'email_exists';

    public const RESULT_EMAIL_NOT_VERIFIED = 'email_not_verified';

    public function handleGoogleCallback(SocialiteUser $googleUser, string $ip, string $userAgent): array
    {
        if (! $googleUser->user['email_verified'] ?? false) {
            Log::warning('Social login: e-mail Google não verificado', ['email' => $googleUser->getEmail(), 'ip' => $ip]);

            return ['result' => self::RESULT_EMAIL_NOT_VERIFIED, 'user' => null];
        }

        $existingByEmail = User::where('email', $googleUser->getEmail())->first();

        if ($existingByEmail && $existingByEmail->isDoctor()) {
            Log::warning('Social login: bloqueado para médico', ['email' => $googleUser->getEmail(), 'ip' => $ip]);

            return ['result' => self::RESULT_DOCTOR_BLOCKED, 'user' => null];
        }

        // E-mail existe como conta email/senha sem vínculo Google → opção B
        if ($existingByEmail && ! $existingByEmail->socialAccounts()->where('provider', 'google')->exists()) {
            return ['result' => self::RESULT_EMAIL_EXISTS, 'user' => null];
        }

        $user = DB::transaction(function () use ($googleUser) {
            $socialAccount = SocialAccount::where('provider', 'google')
                ->where('provider_user_id', $googleUser->getId())
                ->first();

            if ($socialAccount) {
                $socialAccount->update([
                    'provider_email' => $googleUser->getEmail(),
                    'avatar_url' => $googleUser->getAvatar(),
                ]);

                return $socialAccount->user;
            }

            // Cria User novo (sem patient ainda)
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                ['name' => $googleUser->getName(), 'password' => null, 'email_verified_at' => now()]
            );

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_user_id' => $googleUser->getId(),
                'provider_email' => $googleUser->getEmail(),
                'avatar_url' => $googleUser->getAvatar(),
                'linked_at' => now(),
            ]);

            if ($googleUser->getAvatar() && ! $user->avatar_path) {
                try {
                    $avatarPath = app(AvatarService::class)->storeFromUrl($user->id, $googleUser->getAvatar());
                    $user->update(['avatar_path' => $avatarPath]);
                } catch (\Exception $e) {
                    Log::warning('Social login: falha ao importar avatar', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            }

            return $user;
        });

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'social_login',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'metadata' => ['provider' => 'google'],
        ]);

        if ($user->hasTwoFactorEnabled()) {
            return ['result' => self::RESULT_TWO_FACTOR, 'user' => $user];
        }

        if (! $user->isPatient()) {
            return ['result' => self::RESULT_COMPLETE_PROFILE, 'user' => $user];
        }

        return ['result' => self::RESULT_LOGIN, 'user' => $user];
    }

    public function linkGoogle(User $user, SocialiteUser $googleUser, string $ip, string $userAgent): void
    {
        // Verificar conflito: provider_user_id já vinculado a outro user
        $conflict = SocialAccount::where('provider', 'google')
            ->where('provider_user_id', $googleUser->getId())
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($conflict) {
            Log::warning('Social link: provider_user_id já vinculado a outro usuário', [
                'user_id' => $user->id,
                'provider_user_id' => $googleUser->getId(),
            ]);
            throw new \RuntimeException('Esta conta Google já está vinculada a outro usuário.');
        }

        SocialAccount::updateOrCreate(
            ['user_id' => $user->id, 'provider' => 'google'],
            [
                'provider_user_id' => $googleUser->getId(),
                'provider_email' => $googleUser->getEmail(),
                'avatar_url' => $googleUser->getAvatar(),
                'linked_at' => now(),
            ]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'social_link',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'metadata' => ['provider' => 'google', 'provider_user_id' => $googleUser->getId()],
        ]);
    }
}
