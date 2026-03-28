<?php

namespace App\Contracts\Integrations;

use App\Models\Tenant;
use Illuminate\Http\Request;

interface SsoProviderInterface
{
    /**
     * Unique driver identifier (e.g., 'google-oauth', 'microsoft-oauth', 'saml').
     */
    public function driver(): string;

    /**
     * Human-readable provider name.
     */
    public function name(): string;

    /**
     * Return the redirect URL to initiate SSO login.
     */
    public function redirectUrl(Tenant $tenant, array $config): string;

    /**
     * Handle the SSO callback. Returns an SsoResult with user info.
     */
    public function handleCallback(Tenant $tenant, array $config, Request $request): SsoResult;

    /**
     * Whether this provider needs tenant-level config.
     */
    public function requiresConfig(): bool;

    /**
     * Config field definitions for admin settings UI.
     */
    public function configFields(): array;
}
