<?php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealStageChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Deal $deal,
        public string $oldStage,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
