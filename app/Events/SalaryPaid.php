<?php

namespace App\Events;

class SalaryPaid extends EmployeeBroadcastEvent
{
    protected function eventName(): string
    {
        return 'salary.paid';
    }
}
