<?php

namespace App\Integrations;

use App\Contracts\Integrations\SmsProviderInterface;
use App\Contracts\Integrations\SsoProviderInterface;
use App\Contracts\Integrations\TwoFactorProviderInterface;
use App\Integrations\Sms\LogSmsProvider;
use App\Integrations\Sms\TwilioSmsProvider;
use App\Integrations\Sso\GoogleOAuthProvider;
use App\Integrations\Sso\MicrosoftOAuthProvider;
use App\Integrations\Sso\OktaOAuthProvider;
use App\Integrations\TwoFactor\TotpProvider;
use App\Models\Integration;

class IntegrationManager
{
    /**
     * Registered drivers: ['2fa' => ['totp' => ClassName], 'sso' => [...]]
     */
    protected array $drivers = [];

    /**
     * Resolved driver instances cache.
     */
    protected array $instances = [];

    public function __construct()
    {
        // Register built-in drivers
        $this->registerDriver('2fa', 'totp', TotpProvider::class);
        $this->registerDriver('sms', 'log', LogSmsProvider::class);
        $this->registerDriver('sms', 'twilio', TwilioSmsProvider::class);
        $this->registerDriver('sso', 'google-oauth', GoogleOAuthProvider::class);
        $this->registerDriver('sso', 'microsoft-oauth', MicrosoftOAuthProvider::class);
        $this->registerDriver('sso', 'okta-oauth', OktaOAuthProvider::class);
    }

    /**
     * Register a driver class for a category.
     */
    public function registerDriver(string $category, string $driver, string $class): void
    {
        $this->drivers[$category][$driver] = $class;
    }

    /**
     * Get all registered driver names for a category.
     */
    public function getAvailableDrivers(string $category): array
    {
        $drivers = [];
        foreach ($this->drivers[$category] ?? [] as $driverName => $class) {
            $instance = $this->resolveDriver($category, $driverName);
            $drivers[$driverName] = [
                'driver' => $driverName,
                'name' => $instance->name(),
                'requires_config' => $instance->requiresConfig(),
                'config_fields' => $instance->configFields(),
            ];
        }
        return $drivers;
    }

    /**
     * Get active integrations for a tenant in a category.
     */
    public function getActiveIntegrations(string $category, int $tenantId): \Illuminate\Support\Collection
    {
        return Integration::where('tenant_id', $tenantId)
            ->where('category', $category)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Resolve and return a driver instance.
     */
    public function resolveDriver(string $category, string $driver): object
    {
        $key = "{$category}.{$driver}";

        if (!isset($this->instances[$key])) {
            $class = $this->drivers[$category][$driver] ?? null;
            if (!$class || !class_exists($class)) {
                throw new \InvalidArgumentException("Integration driver [{$driver}] not found for category [{$category}].");
            }
            $this->instances[$key] = new $class();
        }

        return $this->instances[$key];
    }

    /**
     * Get the 2FA provider for a tenant/user.
     * Falls back to the built-in TOTP if no custom provider is configured.
     */
    public function get2faProvider(?int $tenantId = null, ?string $driver = null): TwoFactorProviderInterface
    {
        $driver = $driver ?? 'totp';

        // Check if this driver is registered
        if (!isset($this->drivers['2fa'][$driver])) {
            $driver = 'totp'; // fallback to default
        }

        return $this->resolveDriver('2fa', $driver);
    }

    /**
     * Get an SSO provider by driver name.
     */
    public function getSsoProvider(string $driver): SsoProviderInterface
    {
        return $this->resolveDriver('sso', $driver);
    }

    /**
     * Get all active 2FA provider names for a tenant.
     * Always includes 'totp' as the default.
     */
    public function getAvailable2faProviders(?int $tenantId = null): array
    {
        $providers = ['totp']; // always available

        if ($tenantId) {
            $active = Integration::where('tenant_id', $tenantId)
                ->where('category', '2fa')
                ->where('is_active', true)
                ->pluck('driver')
                ->toArray();

            $providers = array_unique(array_merge($providers, $active));
        }

        return $providers;
    }

    /**
     * Get all active SSO providers for a tenant.
     */
    public function getActiveSsoProviders(int $tenantId): array
    {
        return Integration::where('tenant_id', $tenantId)
            ->where('category', 'sso')
            ->where('is_active', true)
            ->get()
            ->map(function ($integration) {
                $driver = $this->drivers['sso'][$integration->driver] ?? null;
                if (!$driver) {
                    return null;
                }
                $instance = $this->resolveDriver('sso', $integration->driver);
                return [
                    'driver' => $integration->driver,
                    'name' => $instance->name(),
                    'config' => $integration->config,
                ];
            })
            ->filter()
            ->toArray();
    }

    /**
     * Get the SMS provider for a tenant.
     * Falls back to the log provider if no custom provider is configured.
     */
    public function getSmsProvider(?int $tenantId = null): SmsProviderInterface
    {
        $driver = 'log';
        $config = [];

        if ($tenantId) {
            $active = Integration::where('tenant_id', $tenantId)
                ->where('category', 'sms')
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($active && isset($this->drivers['sms'][$active->driver])) {
                $driver = $active->driver;
                $config = $active->config;
            }
        }

        $provider = $this->resolveDriver('sms', $driver);
        $provider->setConfig($config);

        return $provider;
    }

    /**
     * Check if a driver is registered for a category.
     */
    public function hasDriver(string $category, string $driver): bool
    {
        return isset($this->drivers[$category][$driver]);
    }
}
