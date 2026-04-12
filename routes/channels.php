<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin-broadcast', function (User $user): bool {
    return $user->isAdmin();
});

Broadcast::channel('employee.{employeeId}', function (User $user, int $employeeId): bool {
    return $user->isAdmin() || ((int) optional($user->employee)->id === (int) $employeeId);
});
