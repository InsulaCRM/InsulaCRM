<?php

namespace App\Contracts\Integrations;

use App\Models\User;

interface TwoFactorProviderInterface
{
    /**
     * Unique driver identifier (e.g., 'totp', 'duo', 'authy').
     */
    public function driver(): string;

    /**
     * Human-readable provider name.
     */
    public function name(): string;

    /**
     * Begin 2FA setup for a user. Returns data for the setup view.
     */
    public function beginSetup(User $user): array;

    /**
     * Confirm setup with user-provided input.
     */
    public function confirmSetup(User $user, array $input): bool;

    /**
     * Generate recovery codes (or empty array if provider handles externally).
     */
    public function generateRecoveryCodes(): array;

    /**
     * Verify a 2FA code during login challenge.
     */
    public function verify(User $user, string $code): bool;

    /**
     * Blade view name for the challenge form.
     */
    public function challengeView(): string;

    /**
     * Blade view name for the setup form.
     */
    public function setupView(): string;

    /**
     * Whether this provider needs tenant-level config (API keys, etc.).
     */
    public function requiresConfig(): bool;

    /**
     * Config field definitions for the admin settings UI.
     * Each entry: ['key' => 'field_name', 'label' => 'Display Label', 'type' => 'text|password|url']
     */
    public function configFields(): array;
}
