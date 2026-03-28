<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;

class DashboardWidgetService
{
    public const WIDGETS = [
        'kpi_cards' => [
            'label' => 'KPI Cards',
            'roles' => ['admin', 'agent', 'acquisition_agent', 'disposition_agent', 'listing_agent', 'buyers_agent'],
        ],
        'charts_row' => [
            'label' => 'Charts',
            'roles' => ['admin', 'agent', 'acquisition_agent', 'disposition_agent', 'listing_agent', 'buyers_agent'],
        ],
        'pipeline_recent_tasks' => [
            'label' => 'Pipeline & Recent',
            'roles' => ['admin', 'agent', 'acquisition_agent', 'disposition_agent', 'listing_agent', 'buyers_agent'],
        ],
        'goals' => [
            'label' => 'Goals',
            'roles' => ['admin', 'agent', 'acquisition_agent', 'disposition_agent', 'listing_agent', 'buyers_agent'],
        ],
        'roi_bottleneck' => [
            'label' => 'ROI & Bottleneck',
            'roles' => ['admin'],
        ],
        'team_leaderboard' => [
            'label' => 'Team Leaderboard',
            'roles' => ['admin'],
        ],
        'ai_digest' => [
            'label' => 'AI Weekly Digest',
            'roles' => ['admin'],
        ],
        'pipeline_health' => [
            'label' => 'Pipeline Health',
            'roles' => ['admin', 'agent', 'acquisition_agent', 'disposition_agent', 'listing_agent', 'buyers_agent'],
        ],
    ];

    public static function getEligibleWidgets(User $user): array
    {
        $roleName = $user->role->name ?? 'agent';
        $eligible = [];

        foreach (self::WIDGETS as $key => $widget) {
            if (in_array($roleName, $widget['roles'])) {
                $eligible[$key] = $widget['label'];
            }
        }

        return $eligible;
    }

    public static function getActiveWidgets(User $user): array
    {
        // Priority: user override → tenant role default → all eligible
        if ($user->dashboard_widgets !== null) {
            return $user->dashboard_widgets;
        }

        $tenant = $user->tenant;
        $roleName = $user->role->name ?? 'agent';

        if ($tenant && $tenant->default_dashboard_widgets !== null) {
            $defaults = $tenant->default_dashboard_widgets;
            if (isset($defaults[$roleName])) {
                return $defaults[$roleName];
            }
        }

        // Default: all eligible widgets enabled
        return array_keys(self::getEligibleWidgets($user));
    }

    public static function isWidgetActive(User $user, string $widget): bool
    {
        return in_array($widget, self::getActiveWidgets($user));
    }
}
