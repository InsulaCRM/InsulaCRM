<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\DigestAwareNotification;

class StaleDealAlert extends Notification implements ShouldQueue
{
    use Queueable;
    use DigestAwareNotification;

    public function __construct(
        protected Deal $deal,
        protected int $daysStuck,
        protected string $advice,
        protected Tenant $tenant,
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stale_deal_alert',
            'icon' => 'alert-triangle',
            'color' => 'orange',
            'title' => __('Stale Deal: :title', ['title' => $this->deal->title]),
            'body' => __('Deal ":title" has been stuck for :days days. AI has recommendations.', [
                'title' => $this->deal->title,
                'days' => $this->daysStuck,
            ]),
            'url' => url("/pipeline/{$this->deal->id}"),
            'deal_id' => $this->deal->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stageLabel = Deal::stageLabel($this->deal->stage);

        return (new MailMessage)
            ->subject("[{$this->tenant->name}] Stale Deal Alert: {$this->deal->title}")
            ->greeting("Deal Stuck for {$this->daysStuck} Days")
            ->line("The deal \"{$this->deal->title}\" has been in the {$stageLabel} stage for {$this->daysStuck} days.")
            ->line('**AI Recommendation:**')
            ->line($this->advice)
            ->action('View Deal', url("/pipeline/{$this->deal->id}"))
            ->line('Please review and take action to move this deal forward.');
    }
}
