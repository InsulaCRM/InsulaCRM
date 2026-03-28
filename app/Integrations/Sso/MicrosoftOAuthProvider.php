<?php

namespace App\Integrations\Sso;

use App\Contracts\Integrations\SsoProviderInterface;
use App\Contracts\Integrations\SsoResult;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MicrosoftOAuthProvider implements SsoProviderInterface
{
    public function driver(): string
    {
        return 'microsoft-oauth';
    }

    public function name(): string
    {
        return 'Microsoft';
    }

    public function redirectUrl(Tenant $tenant, array $config): string
    {
        $tenantId = $config['azure_tenant_id'] ?? 'common';

        $params = http_build_query([
            'client_id' => $config['client_id'] ?? '',
            'redirect_uri' => $this->callbackUrl(),
            'response_type' => 'code',
            'scope' => 'openid email profile User.Read',
            'response_mode' => 'query',
            'prompt' => 'select_account',
            'state' => csrf_token(),
        ]);

        return "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?" . $params;
    }

    public function handleCallback(Tenant $tenant, array $config, Request $request): SsoResult
    {
        if ($request->filled('error')) {
            $desc = $request->input('error_description', 'Microsoft login was cancelled or denied.');
            throw new \RuntimeException($desc);
        }

        $code = $request->input('code');
        if (! $code) {
            throw new \RuntimeException('No authorization code received from Microsoft.');
        }

        $azureTenantId = $config['azure_tenant_id'] ?? 'common';

        // Exchange code for access token
        $tokenResponse = Http::asForm()->post(
            "https://login.microsoftonline.com/{$azureTenantId}/oauth2/v2.0/token",
            [
                'client_id' => $config['client_id'] ?? '',
                'client_secret' => $config['client_secret'] ?? '',
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->callbackUrl(),
                'scope' => 'openid email profile User.Read',
            ]
        );

        if (! $tokenResponse->successful()) {
            $error = $tokenResponse->json('error_description', 'Token exchange failed.');
            throw new \RuntimeException($error);
        }

        $accessToken = $tokenResponse->json('access_token');

        // Fetch user info from Microsoft Graph
        $userResponse = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me', [
                '$select' => 'id,displayName,mail,userPrincipalName',
            ]);

        if (! $userResponse->successful()) {
            throw new \RuntimeException('Failed to fetch user info from Microsoft.');
        }

        $user = $userResponse->json();

        // Microsoft may return email in 'mail' or 'userPrincipalName'
        $email = $user['mail'] ?? $user['userPrincipalName'] ?? null;

        if (empty($email)) {
            throw new \RuntimeException('Microsoft did not return an email address.');
        }

        return new SsoResult(
            email: strtolower($email),
            name: $user['displayName'] ?? $email,
            attributes: [
                'microsoft_id' => $user['id'] ?? null,
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
                'label' => 'Application (Client) ID',
                'type' => 'text',
                'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                'required' => true,
            ],
            [
                'name' => 'client_secret',
                'label' => 'Client Secret',
                'type' => 'password',
                'placeholder' => '',
                'required' => true,
            ],
            [
                'name' => 'azure_tenant_id',
                'label' => 'Directory (Tenant) ID',
                'type' => 'text',
                'placeholder' => 'common (or your Azure AD tenant ID)',
                'required' => false,
                'hint' => 'Use "common" to allow any Microsoft account, or enter a specific tenant ID to restrict to your organization.',
            ],
        ];
    }

    protected function callbackUrl(): string
    {
        return route('sso.callback', 'microsoft-oauth');
    }
}
