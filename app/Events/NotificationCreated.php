<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $notificationId;
    public int $userId;
    public string $type;
    public array $data;
    public string $createdAt;

    public function __construct(string $notificationId, int $userId, string $type, array $data, string $createdAt)
    {
        $this->notificationId = $notificationId;
        $this->userId = $userId;
        $this->type = $type;
        $this->data = $data;
        $this->createdAt = $createdAt;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->notificationId,
            'type'       => $this->type,
            'data'       => $this->data,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
