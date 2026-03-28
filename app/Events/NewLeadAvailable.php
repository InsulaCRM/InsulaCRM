<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewLeadAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public ?Tenant $tenant = null,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
