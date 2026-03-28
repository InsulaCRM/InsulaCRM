<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\Tenant;
use App\Services\BusinessModeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\DigestAwareNotification;

class DueDiligenceWarning extends Notification implements ShouldQueue
{
    use Queueable;
    use DigestAwareNotification;

    public function __construct(
        protected Deal $deal,
        protected int $daysLeft,
        protected Tenant $tenant,
    ) {}

    public function toArray(object $notifiable): array
    {
        $dayWord = $this->daysLeft === 1 ? __('day') : __('days');
        $periodLabel = BusinessModeService::isRealEstate($this->tenant)
            ? __('Contingency deadline')
            : __('Due diligence deadline');

        return [
            'type' => 'due_diligence_warning',
            'icon' => 'alert-triangle',
            'color' => 'orange',
            'title' => $periodLabel,
            'body' => __(':title — :days :word left (ends :date)', [
                'title' => $this->deal->title,
                'days' => $this->daysLeft,
                'word' => $dayWord,
                'date' => $this->deal->due_diligence_end_date->format('M j'),
            ]),
            'url' => url('/pipeline'),
            'deal_id' => $this->deal->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deal = $this->deal;
        $tenantName = $this->tenant->name;
        $leadName = $deal->lead ? "{$deal->lead->first_name} {$deal->lead->last_name}" : 'Unknown';
        $deadline = $deal->due_diligence_end_date->format('M j, Y');
        $dayWord = $this->daysLeft === 1 ? 'day' : 'days';
        $isRE = BusinessModeService::isRealEstate($this->tenant);
        $periodLabel = $isRE ? 'Contingency' : 'Due diligence';

        return (new MailMessage)
            ->subject("[{$tenantName}] {$periodLabel} deadline: {$this->daysLeft} {$dayWord} left")
            ->greeting("Hello {$notifiable->name},")
            ->line("A {$periodLabel} deadline is approaching.")
            ->line("**Deal:** {$deal->title}")
            ->line("**Lead:** {$leadName}")
            ->line("**Deadline:** {$deadline}")
            ->line("**Days Remaining:** {$this->daysLeft}")
            ->action('View Pipeline', url('/pipeline'))
            ->line("Please ensure all {$periodLabel} tasks are completed before the deadline.");
    }
}
