<?php

namespace App\Services;

use Illuminate\Support\Facades\Event;

class NotificationService
{
    public function dispatch(object $event): void
    {
        Event::dispatch($event);
    }
}
