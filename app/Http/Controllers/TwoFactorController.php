<?php

namespace App\Http\Controllers;

use App\Integrations\IntegrationManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA setup page.
     */
    public function setup(IntegrationManager $integrations)
    {
        $user = auth()->user();

        if ($user->two_factor_enabled) {
            return redirect()->route('profile.edit')->with('error', __('2FA is already enabled.'));
        }

        $provider = $integrations->get2faProvider($user->tenant_id);
        $setupData = $provider->beginSetup($user);

        return view($provider->setupView(), $setupData);
    }

    /**
     * Confirm and enable 2FA.
     */
    public function enable(Request $request, IntegrationManager $integrations)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = auth()->user();
        $provider = $integrations->get2faProvider($user->tenant_id);

        if (!$provider->confirmSetup($user, $request->only('code'))) {
            return back()->withErrors(['code' => __('Invalid verification code.')]);
        }

        $recoveryCodes = session('2fa_recovery_codes', []);
        session()->forget('2fa_recovery_codes');

        return view('auth.two-factor-recovery', compact('recoveryCodes'));
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $tenant = auth()->user()->tenant;

        // Block disable if tenant enforces 2FA
        if ($tenant->require_2fa) {
            return back()->with('error', __('Your organization requires two-factor authentication. You cannot disable it.'));
        }

        auth()->user()->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_provider' => 'totp',
        ]);

        return redirect()->route('profile.edit')->with('success', __('Two-factor authentication has been disabled.'));
    }

    /**
     * Show the 2FA challenge form (during login).
     */
    public function challenge(IntegrationManager $integrations)
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        $providerName = session('2fa_provider', 'totp');
        $provider = $integrations->get2faProvider(null, $providerName);

        return view($provider->challengeView());
    }

    /**
     * Verify 2FA code during login.
     */
    public function verify(Request $request, IntegrationManager $integrations)
    {
        $request->validate(['code' => 'required|string']);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $providerName = session('2fa_provider', $user->two_factor_provider ?? 'totp');
        $provider = $integrations->get2faProvider($user->tenant_id, $providerName);

        $code = $request->code;

        // Try provider verification first
        if ($provider->verify($user, $code)) {
            session()->forget(['2fa_user_id', '2fa_remember', '2fa_provider']);
            auth()->login($user, session('2fa_remember', false));
            return redirect()->intended('/dashboard');
        }

        // Try recovery code
        $recoveryCodes = decrypt($user->two_factor_recovery_codes);
        if (is_array($recoveryCodes) && in_array($code, $recoveryCodes)) {
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
            $user->update(['two_factor_recovery_codes' => encrypt($recoveryCodes)]);

            session()->forget(['2fa_user_id', '2fa_remember', '2fa_provider']);
            auth()->login($user, session('2fa_remember', false));
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['code' => __('Invalid verification code or recovery code.')]);
    }
}
