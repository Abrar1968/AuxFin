<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

abstract class AdminBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, string>|string|null  $connections
     */
    public function __construct(
        public readonly array $payload = [],
        array|string|null $connections = ['pusher', 'pusher_notifications'],
    ) {
        $this->broadcastVia($connections);
    }

    abstract protected function eventName(): string;

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin-broadcast');
    }

    public function broadcastAs(): string
    {
        return $this->eventName();
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
