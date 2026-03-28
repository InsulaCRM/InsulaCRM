<?php

namespace App\Integrations\Sso;

use App\Contracts\Integrations\SsoProviderInterface;
use App\Contracts\Integrations\SsoResult;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleOAuthProvider implements SsoProviderInterface
{
    public function driver(): string
    {
        return 'google-oauth';
    }

    public function name(): string
    {
        return 'Google';
    }

    public function redirectUrl(Tenant $tenant, array $config): string
    {
        $params = http_build_query([
            'client_id' => $config['client_id'] ?? '',
            'redirect_uri' => $this->callbackUrl(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => csrf_token(),
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public function handleCallback(Tenant $tenant, array $config, Request $request): SsoResult
    {
        if ($request->filled('error')) {
            throw new \RuntimeException('Google login was cancelled or denied.');
        }

        $code = $request->input('code');
        if (! $code) {
            throw new \RuntimeException('No authorization code received from Google.');
        }

        // Exchange code for access token
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callbackUrl(),
        ]);

        if (! $tokenResponse->successful()) {
            $error = $tokenResponse->json('error_description', 'Token exchange failed.');
            throw new \RuntimeException($error);
        }

        $accessToken = $tokenResponse->json('access_token');

        // Fetch user info
        $userResponse = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (! $userResponse->successful()) {
            throw new \RuntimeException('Failed to fetch user info from Google.');
        }

        $user = $userResponse->json();

        if (empty($user['email'])) {
            throw new \RuntimeException('Google did not return an email address.');
        }

        return new SsoResult(
            email: $user['email'],
            name: $user['name'] ?? $user['email'],
            attributes: [
                'google_id' => $user['sub'] ?? null,
                'avatar' => $user['picture'] ?? null,
            ]
        );
    }

    public function requiresConfig(): bool
    {
        return true;
    }

    public function configFields(): array
    {
        return [
            [
                'name' => 'client_id',
                'label' => 'Client ID',
                'type' => 'text',
                'placeholder' => '123456789-abc.apps.googleusercontent.com',
                'required' => true,
            ],
            [
                'name' => 'client_secret',
                'label' => 'Client Secret',
                'type' => 'password',
                'placeholder' => '',
                'required' => true,
            ],
        ];
    }

    protected function callbackUrl(): string
    {
        return route('sso.callback', 'google-oauth');
    }
}
