<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CacheInvalidationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The action that triggered the event
     */
    public string $action;

    /**
     * The model class name
     */
    public string $modelClass;

    /**
     * The model ID
     */
    public ?int $modelId;

    /**
     * The business ID
     */
    public ?int $businessId;

    /**
     * Additional data for cache invalidation
     */
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $action,
        string $modelClass,
        ?int $modelId = null,
        ?int $businessId = null,
        array $data = []
    ) {
        $this->action = $action;
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->businessId = $businessId;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cache-invalidation'),
        ];
    }
}
