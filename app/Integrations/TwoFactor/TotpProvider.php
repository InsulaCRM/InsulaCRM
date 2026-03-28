<?php

namespace App\Integrations\TwoFactor;

use App\Contracts\Integrations\TwoFactorProviderInterface;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Support\Facades\Crypt;

class TotpProvider implements TwoFactorProviderInterface
{
    protected TotpService $totp;

    public function __construct()
    {
        $this->totp = new TotpService();
    }

    public function driver(): string
    {
        return 'totp';
    }

    public function name(): string
    {
        return 'Authenticator App (TOTP)';
    }

    public function beginSetup(User $user): array
    {
        $secret = $this->totp->generateSecret();
        session(['2fa_setup_secret' => $secret]);

        return [
            'secret' => $secret,
            'qrUri' => $this->totp->getQrUri($secret, $user->email),
        ];
    }

    public function confirmSetup(User $user, array $input): bool
    {
        $secret = session('2fa_setup_secret');
        if (!$secret) {
            return false;
        }

        $code = $input['code'] ?? '';
        if (!$this->totp->verify($secret, $code)) {
            return false;
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => encrypt($recoveryCodes),
            'two_factor_enabled' => true,
            'two_factor_provider' => 'totp',
        ]);

        session()->forget('2fa_setup_secret');
        session(['2fa_recovery_codes' => $recoveryCodes]);

        return true;
    }

    public function generateRecoveryCodes(): array
    {
        return $this->totp->generateRecoveryCodes();
    }

    public function verify(User $user, string $code): bool
    {
        $secret = Crypt::decryptString($user->two_factor_secret);
        return $this->totp->verify($secret, $code);
    }

    public function challengeView(): string
    {
        return 'auth.two-factor-challenge';
    }

    public function setupView(): string
    {
        return 'auth.two-factor-setup';
    }

    public function requiresConfig(): bool
    {
        return false;
    }

    public function configFields(): array
    {
        return [];
    }
}
