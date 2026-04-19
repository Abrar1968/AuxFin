<?php

namespace App\Events;

class LoanApplied extends AdminBroadcastEvent
{
    protected function eventName(): string
    {
        return 'loan.applied';
    }
}
