<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Ensure the authenticated user belongs to an active tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Check tenant is active
            if (!$user->tenant || $user->tenant->status !== 'active') {
                auth()->logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended. Please contact support.',
                ]);
            }

            // Set application locale from tenant preference
            $locale = $user->tenant->locale ?? 'en';
            if (file_exists(lang_path("{$locale}.json")) || $locale === 'en') {
                App::setLocale($locale);
            }

            // Share business mode with all Blade views
            $businessMode = $user->tenant->business_mode ?? 'wholesale';
            view()->share('businessMode', $businessMode);
            view()->share('modeTerms', \App\Services\BusinessModeService::getTerminology($user->tenant));

            // Apply tenant mail settings if configured — overrides .env defaults
            $mail = $user->tenant->mail_settings ?? [];
            if (!empty($mail['mail_host'])) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $mail['mail_host'],
                    'mail.mailers.smtp.port' => $mail['mail_port'] ?? 587,
                    'mail.mailers.smtp.encryption' => $mail['mail_encryption'] ?? 'tls',
                    'mail.mailers.smtp.username' => $mail['mail_username'] ?? '',
                    'mail.mailers.smtp.password' => $this->decryptMailPassword($mail['mail_password'] ?? ''),
                ]);
                if (!empty($mail['mail_from_address'])) {
                    config(['mail.from.address' => $mail['mail_from_address']]);
                }
                if (!empty($mail['mail_from_name'])) {
                    config(['mail.from.name' => $mail['mail_from_name']]);
                }

                // Purge the cached mailer so it picks up the new config
                app('mail.manager')->purge('smtp');
            }
        }

        return $next($request);
    }

    /**
     * Decrypt an encrypted mail password, returning the original string if decryption fails.
     */
    private function decryptMailPassword(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return $value;
        }
    }
}
