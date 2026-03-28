<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\DigestAwareNotification;

class LeadAssigned extends Notification implements ShouldQueue
{
    use Queueable;
    use DigestAwareNotification;

    public function __construct(
        protected Lead $lead,
        protected Tenant $tenant,
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lead_assigned',
            'icon' => 'user-plus',
            'color' => 'blue',
            'title' => __('New lead assigned'),
            'body' => __(':name has been assigned to you.', ['name' => $this->lead->first_name . ' ' . $this->lead->last_name]),
            'url' => url("/leads/{$this->lead->id}"),
            'lead_id' => $this->lead->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead;
        $tenantName = $this->tenant->name;

        return (new MailMessage)
            ->subject("[{$tenantName}] New lead assigned: {$lead->first_name} {$lead->last_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new lead has been assigned to you.")
            ->line("**Name:** {$lead->first_name} {$lead->last_name}")
            ->line("**Phone:** " . ($lead->phone ?: 'N/A'))
            ->line("**Email:** " . ($lead->email ?: 'N/A'))
            ->line("**Source:** " . ucwords(str_replace('_', ' ', $lead->lead_source ?? 'N/A')))
            ->line("**Temperature:** " . ucfirst($lead->temperature ?? 'N/A'))
            ->action('View Lead', url("/leads/{$lead->id}"))
            ->line('Please follow up with this lead promptly.');
    }
}
