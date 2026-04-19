<?php

namespace App\Events;

class SalaryProcessed extends EmployeeBroadcastEvent
{
    protected function eventName(): string
    {
        return 'salary.processed';
    }
}
