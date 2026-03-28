<?php

namespace App\Integrations\Sso;

use App\Contracts\Integrations\SsoProviderInterface;
use App\Contracts\Integrations\SsoResult;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OktaOAuthProvider implements SsoProviderInterface
{
    public function driver(): string
    {
        return 'okta-oauth';
    }

    public function name(): string
    {
        return 'Okta';
    }

    public function redirectUrl(Tenant $tenant, array $config): string
    {
        $domain = rtrim($config['okta_domain'] ?? '', '/');

        $params = http_build_query([
            'client_id' => $config['client_id'] ?? '',
            'redirect_uri' => $this->callbackUrl(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'prompt' => 'login',
            'state' => csrf_token(),
        ]);

        return "{$domain}/oauth2/default/v1/authorize?" . $params;
    }

    public function handleCallback(Tenant $tenant, array $config, Request $request): SsoResult
    {
        if ($request->filled('error')) {
            $desc = $request->input('error_description', 'Okta login was cancelled or denied.');
            throw new \RuntimeException($desc);
        }

        $code = $request->input('code');
        if (! $code) {
            throw new \RuntimeException('No authorization code received from Okta.');
        }

        $domain = rtrim($config['okta_domain'] ?? '', '/');

        // Exchange code for access token
        $tokenResponse = Http::asForm()->post(
            "{$domain}/oauth2/default/v1/token",
            [
                'client_id' => $config['client_id'] ?? '',
                'client_secret' => $config['client_secret'] ?? '',
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->callbackUrl(),
            ]
        );

        if (! $tokenResponse->successful()) {
            $error = $tokenResponse->json('error_description', 'Token exchange failed.');
            throw new \RuntimeException($error);
        }

        $accessToken = $tokenResponse->json('access_token');

        // Fetch user info from Okta
        $userResponse = Http::withToken($accessToken)
            ->get("{$domain}/oauth2/default/v1/userinfo");

        if (! $userResponse->successful()) {
            throw new \RuntimeException('Failed to fetch user info from Okta.');
        }

        $user = $userResponse->json();

        $email = $user['email'] ?? null;

        if (empty($email)) {
            throw new \RuntimeException('Okta did not return an email address.');
        }

        return new SsoResult(
            email: strtolower($email),
            name: $user['name'] ?? $email,
            attributes: [
                'okta_id' => $user['sub'] ?? null,
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
                'name' => 'okta_domain',
                'label' => 'Okta Domain',
                'type' => 'text',
                'placeholder' => 'https://your-org.okta.com',
                'required' => true,
                'hint' => 'Your full Okta domain URL (e.g. https://your-org.okta.com).',
            ],
            [
                'name' => 'client_id',
                'label' => 'Client ID',
                'type' => 'text',
                'placeholder' => '0oaxxxxxxxxxxxxxxxxx',
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
        return route('sso.callback', 'okta-oauth');
    }
}
