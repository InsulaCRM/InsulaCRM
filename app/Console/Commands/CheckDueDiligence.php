<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Models\Role;
use App\Models\User;
use App\Notifications\DueDiligenceWarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckDueDiligence extends Command
{
    protected $signature = 'deals:check-due-diligence';
    protected $description = 'Check deals approaching due diligence deadline and log warnings';

    public function handle(): int
    {
        $deals = Deal::withoutGlobalScopes()
            ->where('stage', 'under_contract')
            ->whereNotNull('due_diligence_end_date')
            ->where('due_diligence_end_date', '<=', now()->addDays(3))
            ->where('due_diligence_end_date', '>', now())
            ->with(['lead.tenant', 'agent'])
            ->get();

        foreach ($deals as $deal) {
            $daysLeft = now()->diffInDays($deal->due_diligence_end_date, false);
            $leadName = $deal->lead ? "{$deal->lead->first_name} {$deal->lead->last_name}" : "Unknown";

            $this->warn("Deal #{$deal->id} ({$leadName}): Due diligence ends in {$daysLeft} day(s) on {$deal->due_diligence_end_date->format('M j, Y')}");

            // Send email notifications
            $tenant = $deal->lead?->tenant;
            if ($tenant && $tenant->wantsNotification('due_diligence_warning')) {
                $recipients = collect();

                // Notify the deal's agent
                if ($deal->agent) {
                    $recipients->push($deal->agent);
                }

                // Notify tenant admins
                $adminRoleId = Role::where('name', 'admin')->value('id');
                $admins = User::where('tenant_id', $tenant->id)
                    ->where('role_id', $adminRoleId)
                    ->where('is_active', true)
                    ->get();
                $recipients = $recipients->merge($admins)->unique('id');

                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, new DueDiligenceWarning($deal, (int) $daysLeft, $tenant));
                }
            }
        }

        // Also flag expired due diligence
        $expired = Deal::withoutGlobalScopes()
            ->where('stage', 'under_contract')
            ->whereNotNull('due_diligence_end_date')
            ->where('due_diligence_end_date', '<', now())
            ->count();

        if ($expired > 0) {
            $this->error("{$expired} deal(s) have EXPIRED due diligence periods!");
        }

        $this->info("Checked " . $deals->count() . " deals approaching deadline, {$expired} expired.");
        return Command::SUCCESS;
    }
}
