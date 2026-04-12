<?php

namespace App\Http\Controllers\Employee;

use App\Events\LeaveApplied;
use App\Http\Controllers\Controller;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $rows = Leave::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($rows);
    }

    public function apply(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $payload = $request->validate([
            'leave_type' => ['required', 'in:casual,sick,earned,unpaid'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $from = Carbon::parse($payload['from_date']);
        $to = Carbon::parse($payload['to_date']);
        $days = $from->diffInDays($to) + 1;

        $leave = Leave::query()->create([
            'employee_id' => $employee->id,
            'leave_type' => $payload['leave_type'],
            'from_date' => $payload['from_date'],
            'to_date' => $payload['to_date'],
            'days' => $days,
            'reason' => $payload['reason'],
            'status' => 'pending',
        ]);

        event(new LeaveApplied([
            'employee_id' => $employee->id,
            'leave_id' => $leave->id,
            'days' => $days,
            'type' => $leave->leave_type,
        ]));

        return response()->json([
            'message' => 'Leave application submitted.',
            'leave' => $leave,
        ], 201);
    }
}
