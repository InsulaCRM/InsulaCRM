<?php

namespace App\Console\Commands;

use App\Mail\DigestEmail;
use App\Models\Activity;
use App\Models\Task;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMorningSummary extends Command
{
    protected $signature = 'digest:morning-summary';
    protected $description = 'Send morning summary digest to each user';

    public function handle(): int
    {
        $users = User::where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('name', '!=', 'field_scout'))
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $sections = [];

            // Overdue tasks
            $overdueCount = Task::where('agent_id', $user->id)
                ->where('is_completed', false)
                ->where('due_date', '<', now())
                ->count();

            if ($overdueCount > 0) {
                $overdueTasks = Task::where('agent_id', $user->id)
                    ->where('is_completed', false)
                    ->where('due_date', '<', now())
                    ->with('lead')
                    ->orderBy('due_date')
                    ->limit(10)
                    ->get();

                $sections[] = [
                    'title' => __('Overdue Tasks (:count)', ['count' => $overdueCount]),
                    'items' => $overdueTasks->map(fn ($t) =>
                        "<strong>{$t->title}</strong>" .
                        ($t->lead ? " — {$t->lead->first_name} {$t->lead->last_name}" : '') .
                        " (due " . ($t->due_date ? $t->due_date->format('M d') : 'N/A') . ")"
                    )->toArray(),
                ];
            }

            // Today's tasks
            $todayTasks = Task::where('agent_id', $user->id)
                ->where('is_completed', false)
                ->whereDate('due_date', today())
                ->with('lead')
                ->orderBy('due_date')
                ->get();

            if ($todayTasks->isNotEmpty()) {
                $sections[] = [
                    'title' => __("Today's Tasks (:count)", ['count' => $todayTasks->count()]),
                    'items' => $todayTasks->map(fn ($t) =>
                        "<strong>{$t->title}</strong>" .
                        ($t->lead ? " — {$t->lead->first_name} {$t->lead->last_name}" : '')
                    )->toArray(),
                ];
            }

            // Stale leads (no activity 14+ days)
            $leadQuery = Lead::where('tenant_id', $user->tenant_id)
                ->whereNotIn('status', ['closed', 'dead']);

            if (!$user->isAdmin()) {
                $leadQuery->where('agent_id', $user->id);
            }

            $staleLeads = $leadQuery->get()->filter(function ($lead) {
                $lastActivity = Activity::where('lead_id', $lead->id)
                    ->orderByDesc('logged_at')
                    ->first();
                return !$lastActivity || $lastActivity->logged_at->lt(now()->subDays(14));
            })->take(10);

            if ($staleLeads->isNotEmpty()) {
                $sections[] = [
                    'title' => __('Stale Leads (No Activity 14+ Days)'),
                    'items' => $staleLeads->map(fn ($l) =>
                        "<strong>{$l->first_name} {$l->last_name}</strong> — " .
                        ucwords(str_replace('_', ' ', $l->status))
                    )->toArray(),
                ];
            }

            if (empty($sections)) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new DigestEmail(
                    digestTitle: __('Your Morning Summary'),
                    sections: $sections,
                    recipientName: $user->name,
                ));
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed: {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$count} morning summary email(s).");
        return self::SUCCESS;
    }
}
