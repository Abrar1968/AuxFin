<?php

namespace App\Events;

class LoanRejected extends EmployeeBroadcastEvent
{
    protected function eventName(): string
    {
        return 'loan.rejected';
    }
}
