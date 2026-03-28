<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $hidden = [
        'api_key',
        'ai_api_key',
        'mail_settings',
    ];

    protected $fillable = [
        'name',
        'business_mode',
        'slug',
        'email',
        'logo_path',
        'timezone',
        'currency',
        'date_format',
        'country',
        'measurement_system',
        'locale',
        'distribution_method',
        'claim_window_minutes',
        'round_robin_index',
        'timezone_restriction_enabled',
        'custom_lead_sources',
        'custom_options',
        'status',
        'api_key',
        'api_enabled',
        'ai_enabled',
        'buyer_portal_enabled',
        'buyer_portal_headline',
        'buyer_portal_description',
        'buyer_portal_config',
        'ai_provider',
        'ai_api_key',
        'ai_model',
        'ai_ollama_url',
        'ai_custom_url',
        'ai_briefings_enabled',
        'notification_preferences',
        'default_dashboard_widgets',
        'mail_settings',
        'require_2fa',
        'sso_default_driver',
        'storage_disk',
    ];

    protected function casts(): array
    {
        return [
            'timezone_restriction_enabled' => 'boolean',
            'custom_lead_sources' => 'array',
            'custom_options' => 'array',
            'api_enabled' => 'boolean',
            'ai_enabled' => 'boolean',
            'ai_briefings_enabled' => 'boolean',
            'ai_api_key' => 'encrypted',
            'buyer_portal_enabled' => 'boolean',
            'buyer_portal_config' => 'array',
            'notification_preferences' => 'array',
            'default_dashboard_widgets' => 'array',
            'mail_settings' => 'array',
            'require_2fa' => 'boolean',
        ];
    }

    /**
     * Check if the tenant wants a specific notification type.
     * Defaults to true if the key is missing from preferences.
     */
    public function wantsNotification(string $type): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return $prefs[$type] ?? true;
    }

    public function isWholesale(): bool
    {
        return ($this->business_mode ?? 'wholesale') === 'wholesale';
    }

    public function isRealEstate(): bool
    {
        return $this->business_mode === 'realestate';
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function buyers()
    {
        return $this->hasMany(Buyer::class);
    }
}
