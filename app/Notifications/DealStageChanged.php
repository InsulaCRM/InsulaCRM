<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\DigestAwareNotification;

class DealStageChanged extends Notification implements ShouldQueue
{
    use Queueable;
    use DigestAwareNotification;

    public function __construct(
        protected Deal $deal,
        protected string $oldStage,
        protected Tenant $tenant,
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deal_stage_changed',
            'icon' => 'arrow-right',
            'color' => 'purple',
            'title' => __('Deal stage changed'),
            'body' => __(':title moved from :old to :new', [
                'title' => $this->deal->title,
                'old' => Deal::stageLabel($this->oldStage),
                'new' => Deal::stageLabel($this->deal->stage),
            ]),
            'url' => url('/pipeline'),
            'deal_id' => $this->deal->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deal = $this->deal;
        $tenantName = $this->tenant->name;
        $oldLabel = Deal::stageLabel($this->oldStage);
        $newLabel = Deal::stageLabel($deal->stage);
        $leadName = $deal->lead ? "{$deal->lead->first_name} {$deal->lead->last_name}" : 'Unknown';

        return (new MailMessage)
            ->subject("[{$tenantName}] Deal stage changed: {$deal->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A deal stage has been updated.")
            ->line("**Deal:** {$deal->title}")
            ->line("**Lead:** {$leadName}")
            ->line("**Stage:** {$oldLabel} → {$newLabel}")
            ->action('View Pipeline', url('/pipeline'))
            ->line('Review the deal and take any necessary action.');
    }
}
