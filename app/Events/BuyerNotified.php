<?php

namespace App\Events;

use App\Models\Buyer;
use App\Models\Deal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BuyerNotified implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Buyer $buyer,
        public Deal $deal,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
