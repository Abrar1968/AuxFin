<?php

namespace App\Events;

class LeaveDecision extends EmployeeBroadcastEvent
{
    protected function eventName(): string
    {
        return 'leave.decision';
    }
}
