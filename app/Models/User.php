<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'role_id',
        'name',
        'email',
        'password',
        'is_active',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_enabled',
        'two_factor_provider',
        'onboarding_completed',
        'theme',
        'calendar_feed_token',
        'email_from_name',
        'email_reply_to',
        'email_mode',
        'dashboard_widgets',
        'notification_delivery',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'calendar_feed_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'onboarding_completed' => 'boolean',
            'dashboard_widgets' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // TenantScope is not applied to User because the auth guard must load
        // the user before any scope can resolve auth()->user(), which would
        // cause infinite recursion and memory exhaustion.
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    /**
     * Check if user has a specific permission via their role.
     */
    public function hasPermission(string $key): bool
    {
        // Admin system role always has all permissions
        if ($this->role && $this->role->is_system && $this->role->name === 'admin') {
            return true;
        }

        return $this->role && $this->role->hasPermission($key);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAgent(): bool
    {
        return $this->hasRole('agent') || $this->isAcquisitionAgent()
            || $this->isListingAgent() || $this->isBuyersAgent();
    }

    public function isAcquisitionAgent(): bool
    {
        return $this->hasRole('acquisition_agent');
    }

    public function isDispositionAgent(): bool
    {
        return $this->hasRole('disposition_agent');
    }

    public function isFieldScout(): bool
    {
        return $this->hasRole('field_scout');
    }

    public function isListingAgent(): bool
    {
        return $this->hasRole('listing_agent');
    }

    public function isBuyersAgent(): bool
    {
        return $this->hasRole('buyers_agent');
    }

    /**
     * Check if user can access lead management.
     */
    public function canManageLeads(): bool
    {
        return $this->isAdmin() || $this->isAgent() || $this->isAcquisitionAgent() || $this->isListingAgent();
    }

    /**
     * Check if user can access buyer/client database.
     */
    public function canManageBuyers(): bool
    {
        return $this->isAdmin() || $this->isDispositionAgent() || $this->isBuyersAgent();
    }
}
