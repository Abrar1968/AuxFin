<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InsightStreamed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $stream,
        public array $payload = [],
    )
    {
        if (app()->environment('testing')) {
            return;
        }

        $connections = collect(['pusher_insights', 'pusher_notifications'])
            ->filter(function (string $connection): bool {
                return filled(config("broadcasting.connections.{$connection}.key"))
                    && filled(config("broadcasting.connections.{$connection}.app_id"));
            })
            ->values()
            ->all();

        if ($connections !== []) {
            $this->broadcastVia($connections);
        }
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin-broadcast');
    }

    public function broadcastAs(): string
    {
        return $this->stream;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
