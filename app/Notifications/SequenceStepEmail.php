<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SequenceStepEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Lead $lead,
        protected string $messageTemplate,
        protected ?string $subject,
        protected Tenant $tenant,
        protected ?User $agent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = $this->tenant->name;
        $agentName = $this->agent?->name ?? $tenantName;

        // Replace merge tags in the message template
        $body = str_replace(
            ['{first_name}', '{last_name}', '{company_name}'],
            [$this->lead->first_name, $this->lead->last_name, $tenantName],
            $this->messageTemplate
        );

        $subject = $this->subject ?: "[{$tenantName}] Message from {$agentName}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$this->lead->first_name},")
            ->line($body);
    }
}
