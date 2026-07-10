<?php

namespace App\Events;

use App\Models\DueCollect;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuePaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $due_collect;

    /**
     * Create a new event instance.
     */
    public function __construct(DueCollect $due_collect)
    {
        $this->due_collect = $due_collect;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
