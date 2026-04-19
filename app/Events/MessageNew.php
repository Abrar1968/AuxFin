<?php

namespace App\Events;

class MessageNew extends AdminBroadcastEvent
{
    public function __construct(array $payload = [])
    {
        parent::__construct($payload, ['pusher', 'pusher_chat', 'pusher_notifications']);
    }

    protected function eventName(): string
    {
        return 'message.new';
    }
}
