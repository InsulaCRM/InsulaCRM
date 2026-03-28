<?php

namespace App\Console\Commands;

use App\Mail\DigestEmail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendNotificationDigest extends Command
{
    protected $signature = 'notifications:send-digest';
    protected $description = 'Send daily notification digest to users who opted in';

    public function handle(): int
    {
        $users = User::where('notification_delivery', 'daily_digest')
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $notifications = $user->unreadNotifications()
                ->where('created_at', '>=', now()->subDay())
                ->get();

            if ($notifications->isEmpty()) {
                continue;
            }

            $grouped = $notifications->groupBy(fn ($n) => $n->data['type'] ?? 'other');

            $sections = [];
            foreach ($grouped as $type => $items) {
                $sections[] = [
                    'title' => ucwords(str_replace('_', ' ', $type)) . " ({$items->count()})",
                    'items' => $items->map(fn ($n) => ($n->data['title'] ?? '') . ' — ' . ($n->data['body'] ?? ''))->toArray(),
                ];
            }

            try {
                Mail::to($user->email)->send(new DigestEmail(
                    digestTitle: __('Your Daily Notification Digest'),
                    sections: $sections,
                    recipientName: $user->name,
                ));
                // Mark included notifications as read so they aren't re-sent next digest
                $notifications->markAsRead();
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to send digest to {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$count} digest email(s).");
        return self::SUCCESS;
    }
}
