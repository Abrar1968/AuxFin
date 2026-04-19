<?php

namespace App\Events;

class LeaveApplied extends AdminBroadcastEvent
{
    protected function eventName(): string
    {
        return 'leave.applied';
    }
}
