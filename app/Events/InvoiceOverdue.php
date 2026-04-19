<?php

namespace App\Events;

class InvoiceOverdue extends AdminBroadcastEvent
{
    protected function eventName(): string
    {
        return 'invoice.overdue';
    }
}
