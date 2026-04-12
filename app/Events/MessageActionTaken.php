<?php

namespace App\Events;

class MessageActionTaken extends EmployeeBroadcastEvent
{
    public function __construct(int $employeeId, array $payload = [])
    {
        parent::__construct($employeeId, $payload, ['pusher_chat', 'pusher_notifications']);
    }

    protected function eventName(): string
    {
        return 'message.action_taken';
    }
}
