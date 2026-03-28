<?php

namespace App\Console\Commands;

use App\Facades\Hooks;
use App\Models\Activity;
use App\Models\SequenceEnrollment;
use App\Notifications\SequenceStepEmail;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ProcessSequences extends Command
{
    protected $signature = 'sequences:process';
    protected $description = 'Process drip sequence enrollments and execute due steps';

    public function handle(): int
    {
        $enrollments = SequenceEnrollment::withoutGlobalScopes()
            ->where('status', 'active')
            ->with(['sequence.steps', 'lead.tenant', 'lead.agent'])
            ->get();

        $processed = 0;

        foreach ($enrollments as $enrollment) {
            $steps = $enrollment->sequence->steps->sortBy('order')->values();
            $currentStepIndex = $enrollment->current_step;

            if ($currentStepIndex >= $steps->count()) {
                $enrollment->update(['status' => 'completed']);
                continue;
            }

            $step = $steps[$currentStepIndex];
            $daysSinceLastStep = $enrollment->last_step_at
                ? now()->diffInDays($enrollment->last_step_at)
                : now()->diffInDays($enrollment->created_at);

            if ($daysSinceLastStep >= $step->delay_days) {
                // Check DNC before executing
                $dncService = app(\App\Services\DncService::class);
                if ($dncService->check($enrollment->lead)) {
                    $this->line("Skipping lead #{$enrollment->lead_id} (DNC blocked)");
                    continue;
                }

                // Execute step: create activity
                Activity::create([
                    'tenant_id' => $enrollment->tenant_id,
                    'lead_id' => $enrollment->lead_id,
                    'agent_id' => $enrollment->lead->agent_id,
                    'type' => $step->action_type ?? 'note',
                    'subject' => "[Sequence] {$enrollment->sequence->name} - Step " . ($currentStepIndex + 1),
                    'body' => $step->message_template,
                    'logged_at' => now(),
                ]);

                Hooks::doAction('sequence.step_executed', $enrollment->lead, $step->toArray());

                // Send email notification for email-type steps
                if (($step->action_type ?? '') === 'email' && $enrollment->lead->email) {
                    $tenant = $enrollment->lead->tenant;
                    if ($tenant && $tenant->wantsNotification('sequence_email')) {
                        NotificationFacade::route('mail', $enrollment->lead->email)
                            ->notify(new SequenceStepEmail(
                                $enrollment->lead,
                                $step->message_template ?? '',
                                null,
                                $tenant,
                                $enrollment->lead->agent,
                            ));
                    }
                }

                // Send SMS for sms-type steps
                if (($step->action_type ?? '') === 'sms' && $enrollment->lead->phone) {
                    try {
                        $message = $this->replaceMergeTags(
                            $step->message_template ?? '',
                            $enrollment->lead
                        );
                        app(SmsService::class)->send($enrollment->lead->phone, $message);
                    } catch (\Throwable $e) {
                        $this->error("SMS failed for lead #{$enrollment->lead_id}: {$e->getMessage()}");
                    }
                }

                $nextStep = $currentStepIndex + 1;
                $enrollment->update([
                    'current_step' => $nextStep,
                    'last_step_at' => now(),
                    'status' => $nextStep >= $steps->count() ? 'completed' : 'active',
                ]);

                $processed++;
            }
        }

        $this->info("Processed {$processed} sequence steps.");
        return Command::SUCCESS;
    }

    private function replaceMergeTags(string $content, $lead): string
    {
        return str_replace(
            ['{first_name}', '{last_name}', '{full_name}', '{email}', '{phone}'],
            [
                $lead->first_name ?? '',
                $lead->last_name ?? '',
                $lead->full_name ?? '',
                $lead->email ?? '',
                $lead->phone ?? '',
            ],
            $content
        );
    }
}
