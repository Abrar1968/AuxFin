<?php

namespace App\Http\Controllers\Admin;

use App\Events\LeaveDecision;
use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = Leave::query()
            ->with(['employee.user', 'employee.department', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function decision(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'min:3', 'required_if:status,rejected'],
        ]);

        $leave = Leave::query()->findOrFail($id);

        $leave->update([
            'status' => $payload['status'],
            'admin_note' => $payload['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        event(new LeaveDecision($leave->employee_id, [
            'leave_id' => $leave->id,
            'status' => $leave->status,
            'admin_note' => $leave->admin_note,
        ]));

        return response()->json([
            'message' => 'Leave decision submitted successfully.',
            'leave' => $leave->fresh(['employee.user', 'reviewer']),
        ]);
    }
}
