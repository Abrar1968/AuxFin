<?php

namespace App\Events;

class LoanApproved extends EmployeeBroadcastEvent
{
    protected function eventName(): string
    {
        return 'loan.approved';
    }
}
