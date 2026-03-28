<?php

namespace App\Traits;

trait DigestAwareNotification
{
    public function via(object $notifiable): array
    {
        if (($notifiable->notification_delivery ?? 'instant') === 'daily_digest') {
            return ['database'];
        }

        return ['mail', 'database'];
    }
}
