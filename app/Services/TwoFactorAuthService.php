<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return $secret;
    }

    public function getQrCodeSvg(User $user): string
    {
        $url = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(192),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function confirmActivation(User $user, string $code): array
    {
        $valid = $this->google2fa->verifyKey(
            $user->two_factor_secret,
            $code,
            (int) config('telemedicine.auth.two_factor.window', 1),
        );

        if (! $valid) {
            return ['confirmed' => false, 'recovery_codes' => []];
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        return ['confirmed' => true, 'recovery_codes' => $recoveryCodes];
    }

    public function verify(User $user, string $code): bool
    {
        // Tentar TOTP
        if ($this->isNumericCode($code)) {
            return (bool) $this->google2fa->verifyKey(
                $user->two_factor_secret,
                $code,
                (int) config('telemedicine.auth.two_factor.window', 1),
            );
        }

        // Recovery code single-use
        $codes = $user->two_factor_recovery_codes ?? [];
        $index = array_search($code, $codes, true);

        if ($index === false) {
            return false;
        }

        array_splice($codes, $index, 1);
        $user->update(['two_factor_recovery_codes' => $codes]);

        return true;
    }

    public function disable(User $user, string $ip, string $userAgent): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => '2fa_disabled',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->update(['two_factor_recovery_codes' => $codes]);

        return $codes;
    }

    private function generateRecoveryCodes(): array
    {
        $count = (int) config('telemedicine.auth.two_factor.recovery_code_count', 10);

        return array_map(fn () => Str::random(10).'-'.Str::random(10), range(1, $count));
    }

    private function isNumericCode(string $code): bool
    {
        return ctype_digit($code) && strlen($code) === 6;
    }
}
