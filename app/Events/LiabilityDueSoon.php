<?php

namespace App\Events;

class LiabilityDueSoon extends AdminBroadcastEvent
{
    protected function eventName(): string
    {
        return 'liability.due_soon';
    }
}
